<?php

namespace App\Http\Controllers\Api;

use App\Enums\FastApiStatus;
use App\Http\Controllers\Controller;
use App\Models\{DailyReport, UserStatistic, OcrProcessLog};
use App\Enums\StatusVerifikasi;
use App\Events\DailyReportUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OcrResultController extends Controller
{
    public function store(Request $request)
    {
        Log::info('OCR Result received', ['payload' => $request->all()]);

        $validated = $request->validate([
            'report_id' => 'required|integer|exists:daily_reports,id',
            'user_id' => 'required|integer|exists:users,id',
            'raw_ocr' => 'nullable',
            'extracted_data' => 'nullable',
        ]);

        $rawOcr = $validated['raw_ocr'] ?? '';
        $extractedData = $validated['extracted_data'] ?? [];

        if (is_string($extractedData)) {
            $extractedData = json_decode($extractedData, true) ?? [];
        }

        // Simpan OcrProcessLog terlebih dahulu
        $ocrLog = OcrProcessLog::create([
            'report_id' => $validated['report_id'],
            'request_id' => uniqid('ocr_'),
            'fastapi_status' => FastApiStatus::DONE,
            'ocr_raw' => is_string($rawOcr) ? $rawOcr : json_encode($rawOcr),
            'ocr_text_result' => json_encode($extractedData),
            'received_at' => now(),
        ]);

        Log::info('OcrProcessLog saved', ['log_id' => $ocrLog->id, 'report_id' => $validated['report_id']]);

        try {
            $report = DailyReport::find($validated['report_id']);
            $steps = is_array($extractedData) ? ($extractedData['steps'] ?? 0) : 0;

            if (empty($extractedData) || !isset($extractedData['steps']) || $steps == 0) {
                Log::warning('Steps not found or zero in extracted_data', ['extracted_data' => $extractedData]);

                $report->update([
                    'langkah' => 0,
                    'co2e_reduction_kg' => 0,
                    'pohon' => 0,
                    'poin' => 0,
                    'ocr_result' => json_encode($extractedData),
                    'status_verifikasi' => StatusVerifikasi::DITOLAK,
                    'verified_at' => now(),
                ]);

                Log::info('DailyReport rejected - no valid steps', ['report_id' => $report->id]);
                
                broadcast(new DailyReportUpdated($validated['user_id'], $report->id, 'rejected'));
                
                return response()->json(['success' => true, 'report_id' => $report->id, 'status' => 'rejected']);
            }

            $co2e = round($steps * 0.000064, 2);
            $pohon = floor($steps / 10000);

            Log::info('Calculated values', ['steps' => $steps, 'co2e' => $co2e, 'pohon' => $pohon]);

            $report->update([
                'langkah' => $steps,
                'co2e_reduction_kg' => $co2e,
                'pohon' => $pohon,
                'poin' => $steps,
                'ocr_result' => json_encode($extractedData),
                'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                'verified_at' => now(),
            ]);

            Log::info('DailyReport updated', ['report_id' => $report->id, 'status' => $report->status_verifikasi->value]);
            
            broadcast(new DailyReportUpdated($validated['user_id'], $report->id, 'verified'));

            $stats = UserStatistic::firstOrCreate(['user_id' => $validated['user_id']]);
            $stats->increment('total_langkah', $steps);
            $stats->increment('total_co2e_kg', $co2e);
            $stats->increment('total_pohon', $pohon);
            $stats->update(['last_update' => now()]);

            Log::info('UserStatistic updated', [
                'user_id' => $validated['user_id'],
                'total_langkah' => $stats->total_langkah,
                'total_co2e_kg' => $stats->total_co2e_kg,
                'total_pohon' => $stats->total_pohon
            ]);

            return response()->json(['success' => true, 'report_id' => $report->id]);
        } catch (\Exception $e) {
            Log::error('Error processing OCR result', [
                'report_id' => $validated['report_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'report_id' => $validated['report_id'],
                'error' => 'Processing failed but OCR data saved'
            ], 500);
        }
    }
}

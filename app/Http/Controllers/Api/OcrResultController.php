<?php

namespace App\Http\Controllers\Api;

use App\Enums\FastApiStatus;
use App\Enums\StatusVerifikasi;
use App\Enums\VerifiedBy;
use App\Enums\WalkAppSupport;
use App\Events\DailyReportUpdated;
use App\Http\Controllers\Controller;
use App\Models\{DailyReport, UserStatistic, OcrProcessLog};
use App\Services\ReportCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

class OcrResultController extends Controller
{
    public function store(Request $request)
    {
        Log::info('OCR Result received', ['payload' => $request->all()]);

        $validated = $request->validate([
            'report_id' => 'required|integer|exists:daily_reports,id',
            'user_id' => 'required|integer|exists:users,id',
            'img_url' => 'nullable',
            'raw_ocr' => 'nullable',
            'extracted_data' => 'nullable',
            'app_class' => 'nullable',
            'processing_time_ms' => 'nullable',
        ]);

        $rawOcr = $validated['raw_ocr'] ?? '';
        $extractedData = $validated['extracted_data'] ?? [];
        $appClass = $validated['app_class'] ?? 'Other';

        if (is_string($extractedData)) {
            $extractedData = json_decode($extractedData, true) ?? [];
        }

        $detectAplicationId = match($appClass) {
            'Apple Health' => WalkAppSupport::APPLE_HEALTH,
            'Google Fit' => WalkAppSupport::GOOGLE_FIT,
            'Huawei Health' => WalkAppSupport::HUAWEI_HEALTH,
            'Samsung Health' => WalkAppSupport::SAMSUNG_HEALTH,
            default => WalkAppSupport::OTHER,
        };

        try {
            $ocrLog = OcrProcessLog::create([
                'report_id' => $validated['report_id'],
                'request_id' => uniqid('ocr_'),
                'fastapi_status' => FastApiStatus::DONE,
                'ocr_raw' => is_string($rawOcr) ? $rawOcr : json_encode($rawOcr),
                'ocr_text_result' => json_encode($extractedData),
                'detect_aplication_id' => $detectAplicationId,
                'received_at' => now(),
                'img_url' => $validated['img_url'] ?? null,
                'verified_id' => VerifiedBy::SISTEM,
                'ocr_process_time_ms' => $validated['processing_time_ms'] ?? null,
            ]);

            Log::info('OcrProcessLog saved', ['log_id' => $ocrLog->id, 'report_id' => $validated['report_id']]);
        } catch (\Exception $e) {
            Log::error('Failed to save OcrProcessLog', [
                'report_id' => $validated['report_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save OCR log'
            ], 500);
        }

        try {
            return DB::transaction(function () use ($validated, $extractedData) {
                $report = DailyReport::lockForUpdate()->find($validated['report_id']);
                $steps = is_array($extractedData) ? ($extractedData['steps'] ?? 0) : 0;

                if (empty($extractedData) || !isset($extractedData['steps']) || $steps == 0) {
                    Log::warning('Steps not found or zero in extracted_data', ['extracted_data' => $extractedData]);

                    $report->update([
                        'status_verifikasi' => StatusVerifikasi::DITOLAK,
                        'verified_at' => now(),
                        'verified_id' => VerifiedBy::SISTEM,
                    ]);

                    Log::info('DailyReport rejected - no valid steps', ['report_id' => $report->id]);

                    // Auto Update to Manual Verification
                    $report->update([
                        'status_verifikasi' => StatusVerifikasi::PENDING,
                        'manual_verification_requested' => true,
                        'manual_verification_requested_at' => now(),
                    ]);

                    broadcast(new DailyReportUpdated($validated['user_id'], $report->id, 'rejected'));

                    return response()->json(['success' => true, 'report_id' => $report->id, 'status' => 'rejected']);
                }

                $report->update([
                    'ocr_result' => json_encode($extractedData),
                    'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                    'verified_at' => now(),
                    'verified_id' => VerifiedBy::SISTEM,
                ]);

                Log::info('DailyReport updated', ['report_id' => $report->id, 'status' => $report->status_verifikasi->value]);

                app(ReportCalculationService::class)->recalculate($report->id, $steps);

                Log::info('Report recalculated', ['report_id' => $report->id, 'user_id' => $validated['user_id']]);

                broadcast(new DailyReportUpdated($validated['user_id'], $report->id, 'verified'));

                return response()->json(['success' => true, 'report_id' => $report->id]);
            });
        } catch (\Exception $e) {
            Log::error('Error processing OCR result', [
                'report_id' => $validated['report_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'report_id' => $validated['report_id'],
                'error' => 'Processing failed'
            ], 500);
        }
    }


}

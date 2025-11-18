<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Services\OcrApiService;
use App\Models\OcrProcessLog;

new #[Layout('components.layouts.app')] #[Title('System Info')] class extends Component
{
    public $systemInfo = null;
    public $loading = true;
    public $error = null;
    public $avgProcessingTime = null;

    public function mount()
    {
        $this->fetchSystemInfo();
        $this->calculateAvgProcessingTime();
    }

    public function fetchSystemInfo()
    {
        try {
            $this->loading = true;
            $this->error = null;

            $ocrService = new OcrApiService();
            $this->systemInfo = $ocrService->getSystemInfo();
            
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function calculateAvgProcessingTime()
    {
        $avgMs = OcrProcessLog::where('ocr_process_time_ms', '>', 0)
            ->avg('ocr_process_time_ms');

        if ($avgMs) {
            $this->avgProcessingTime = round($avgMs / 1000, 2) . 's';
        } else {
            $this->avgProcessingTime = 'N/A';
        }
    }

    public function refresh()
    {
        $this->fetchSystemInfo();
        $this->calculateAvgProcessingTime();
    }
}; ?>

<div wire:poll.5s="refresh">
    <flux:header>
        <flux:heading size="xl">System Information</flux:heading>
        <flux:spacer />
        <flux:button wire:click="refresh" icon="arrow-path" :disabled="$loading" size="sm" variant="ghost">
            Refresh
        </flux:button>
    </flux:header>

    @if($loading)
        <div class="flex items-center justify-center py-12">
            <flux:icon.arrow-path class="size-8 animate-spin text-zinc-500" />
            <span class="ml-2 text-zinc-600 dark:text-zinc-400">Loading system information...</span>
        </div>
    @elseif($error)
        <div class="border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950 rounded-lg p-4">
            <div class="flex items-center">
                <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                <span class="ml-2 text-red-700 dark:text-red-300">{{ $error }}</span>
            </div>
        </div>
    @elseif($systemInfo)
        <div class="space-y-6">
            <!-- Queue Status -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                <div class="flex items-center mb-6">
                    <flux:icon.queue-list class="size-6 text-zinc-600 dark:text-zinc-400 mr-3" />
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Queue Status</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Waiting in Queue</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['queue']['waiting_in_queue'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Currently Processing</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['queue']['currently_processing'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Total Reports Tracked</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['queue']['total_reports_tracked'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Queue Capacity</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ ucfirst($systemInfo['queue']['queue_capacity']) }}</p>
                    </div>
                </div>

                @if(!empty($systemInfo['queue']['reports_in_queue']))
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3">OCR on Process</h3>
                        <div class="space-y-2">
                            @foreach($systemInfo['queue']['reports_in_queue'] as $report)
                                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Report #{{ $report['report_id'] }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">User: {{ $report['user_id'] }}</span>
                                    </div>
                                    <a href="{{ $report['img_url'] }}"
                                        data-fancybox="gallery-{{ $report['report_id'] }}"
                                        data-caption="Bukti Screenshot">
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                            Processing image: {{ basename($report['img_url']) }}
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- System Resources -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                <div class="flex items-center mb-6">
                    <flux:icon.chart-bar class="size-6 text-zinc-600 dark:text-zinc-400 mr-3" />
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">System Resources</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">CPU Usage</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['system']['cpu_usage'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Memory Usage</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['system']['memory_usage'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Disk Usage</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['system']['disk_usage'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Python Version</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['system']['python_version'] }}</p>
                    </div>
                </div>
            </div>

            <!-- OCR Engine & Workers -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                    <div class="flex items-center mb-6">
                        <flux:icon.eye class="size-6 text-zinc-600 dark:text-zinc-400 mr-3" />
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">OCR Engine</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Engine</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['ocr_engine']['engine'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Status</span>
                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full {{ $systemInfo['ocr_engine']['status'] === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                {{ ucfirst($systemInfo['ocr_engine']['status']) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">GPU Device</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['ocr_engine']['gpu_device'] ?? 'None' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Languages</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ implode(', ', $systemInfo['ocr_engine']['languages']) }}</span>
                        </div>
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Total Workers</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['workers']['total_workers'] }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Busy Workers</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['workers']['busy_workers'] }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Idle Workers</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['workers']['idle_workers'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Processing Mode</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ ucfirst($systemInfo['workers']['processing_mode']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processing Information -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                    <div class="flex items-center mb-6">
                        <flux:icon.cog-6-tooth class="size-6 text-zinc-600 dark:text-zinc-400 mr-3" />
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Processing Info</h2>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Supported Apps</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($systemInfo['processing']['supported_apps'] as $app)
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-md bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ $app }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Extraction Fields</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($systemInfo['processing']['extraction_fields'] as $field)
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-md border border-zinc-300 text-zinc-700 dark:border-zinc-600 dark:text-zinc-300">{{ $field }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Image Preprocessing</span>
                                <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['processing']['image_preprocessing'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Avg Processing Time</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $avgProcessingTime }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Information -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                <div class="flex items-center mb-6">
                    <flux:icon.information-circle class="size-6 text-zinc-600 dark:text-zinc-400 mr-3" />
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Service Information</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Service</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['service'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Version</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['version'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Uptime</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $systemInfo['uptime'] }}</p>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Last Updated</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ isset($systemInfo['timestamp']) ? \Carbon\Carbon::parse($systemInfo['timestamp'])->format('H:i:s') : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

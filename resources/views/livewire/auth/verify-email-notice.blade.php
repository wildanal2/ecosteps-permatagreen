<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\{Auth, RateLimiter};

new #[Layout('components.layouts.auth')]
    #[Title('Verifikasi Email')]
    class extends Component {

    public ?string $email = null;
    public bool $resent = false;
    public ?int $remainingTime = null;
    public int $attemptCount = 0;

    public function mount()
    {
        $this->email = session('unverified_email') ?? session('registered_email');

        // Keep session alive
        if ($this->email) {
            session()->put('verification_email', $this->email);
        } else {
            $this->email = session('verification_email');
        }

        $this->checkRateLimit();
    }

    public function checkRateLimit()
    {
        if (!$this->email) return;

        $key = 'resend-verification:'.$this->email;
        $countKey = 'resend-count:'.$this->email;
        
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->remainingTime = RateLimiter::availableIn($key);
        } else {
            $this->remainingTime = null;
        }
        
        $this->attemptCount = (int) cache()->get($countKey, 0);
    }
    
    public function getWaitTime()
    {
        // Exponential backoff: 3min, 6min, 12min, 3jam, 6jam, 12jam, 24jam
        $waitTimes = [180, 360, 720, 10800, 21600, 43200, 86400];
        $index = min($this->attemptCount, count($waitTimes) - 1);
        return $waitTimes[$index];
    }
    
    public function formatTime($seconds)
    {
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            return sprintf('%dj %02dm %02ds', $hours, $minutes, $secs);
        } elseif ($seconds >= 60) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return sprintf('%dm %02ds', $minutes, $secs);
        }
        return $seconds . 's';
    }

    public function censorEmail($email)
    {
        if (!$email) return '';

        [$local, $domain] = explode('@', $email);
        $localLen = strlen($local);
        $domainParts = explode('.', $domain);

        // Sensor local part: show first 3 chars, rest as ***
        $censoredLocal = substr($local, 0, min(3, $localLen)) . str_repeat('*', max(0, $localLen - 3));

        // Sensor domain: show first char and last part, middle as **
        $censoredDomain = substr($domainParts[0], 0, 1) . str_repeat('*', strlen($domainParts[0]) - 1);
        if (count($domainParts) > 1) {
            $censoredDomain .= '.' . $domainParts[count($domainParts) - 1];
        }

        return $censoredLocal . '@' . $censoredDomain;
    }

    public function resend()
    {
        if (!$this->email) {
            $this->addError('resend', 'Email tidak ditemukan. Silakan login kembali.');
            return;
        }

        $key = 'resend-verification:'.$this->email;
        $countKey = 'resend-count:'.$this->email;

        // Check rate limit
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('resend', 'Silakan tunggu sebelum mengirim ulang email.');
            $this->checkRateLimit();
            return;
        }

        $user = \App\Models\User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('resend', 'User tidak ditemukan.');
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email sudah terverifikasi. Silakan login.');
        }

        // Get current attempt count
        $currentCount = (int) cache()->get($countKey, 0);
        
        // Calculate wait time based on attempt count
        $waitTime = $this->getWaitTime();
        
        // Hit rate limiter with exponential backoff
        RateLimiter::hit($key, $waitTime);
        
        // Increment attempt count (expires after 7 days)
        cache()->put($countKey, $currentCount + 1, now()->addDays(7));

        $user->sendEmailVerificationNotification();
        $this->resent = true;
        $this->checkRateLimit();
    }
};

?>

<div class="w-full p-8 max-w-md bg-white rounded-2xl shadow-sm">
    <div class="text-center mb-6">
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Verifikasi Email Anda</h1>
        <p class="text-sm text-gray-600">
            @if($email)
                @if(session('unverified_email'))
                    Akun Anda belum diverifikasi. Silakan cek email Anda
                @else
                    Kami telah mengirim link verifikasi ke email Anda
                @endif
                <span class="font-semibold text-gray-800">{{ $this->censorEmail($email) }}</span>
                untuk link verifikasi.
            @else
                Silakan verifikasi email Anda untuk melanjutkan.
            @endif
        </p>
    </div>

    @if($resent)
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800">Email verifikasi berhasil dikirim!</p>
                    <p class="text-xs text-green-700 mt-1">Silakan cek inbox atau folder spam Anda.</p>
                </div>
            </div>
        </div>
    @endif

    @error('resend')
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm text-red-800">{{ $message }}</p>
            </div>
        </div>
    @enderror

    <div class="space-y-4">
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <p class="text-sm text-gray-700 text-center">
                Silakan cek <span class="font-medium">inbox</span> atau <span class="font-medium">folder spam</span> email Anda dan klik link verifikasi untuk mengaktifkan akun.
            </p>
        </div>

        @if($remainingTime)
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="text-center text-amber-800">
                    <div class="flex items-center gap-2 justify-center mb-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm font-medium">Tunggu untuk kirim ulang</p>
                    </div>
                    <p class="text-xs text-amber-700">
                        Percobaan ke-{{ $attemptCount + 1 }} • Waktu tunggu meningkat setiap percobaan
                    </p>
                </div>
            </div>
        @else
            <div class="text-center">
                <p class="text-xs text-gray-500 mb-3">
                    @if($attemptCount > 0)
                        Percobaan ke-{{ $attemptCount + 1 }} • Waktu tunggu: 
                        <span class="font-semibold text-gray-700">{{ $this->formatTime($this->getWaitTime()) }}</span>
                    @else
                        Klik tombol di bawah untuk mengirim ulang email verifikasi
                    @endif
                </p>
            </div>
        @endif

        <form wire:submit.prevent="resend" wire:poll.1s="checkRateLimit">
            <flux:button 
                type="submit" 
                variant="outline" 
                class="w-full font-mono"
                :disabled="$remainingTime !== null"
            >
                @if($remainingTime)
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    Tunggu {{ $this->formatTime($remainingTime) }}
                @else
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Kirim Ulang Email Verifikasi
                @endif
            </flux:button>
        </form>

        <div class="pt-2 border-t border-gray-200">
            <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Login
            </a>
        </div>
    </div>
</div>

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('=== EMAIL VERIFICATION STARTED ===');
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('User ID from URL: ' . $request->route('id'));
        Log::info('Hash from URL: ' . $request->route('hash'));
        Log::info('Expires: ' . $request->query('expires'));
        Log::info('Signature: ' . $request->query('signature'));

        // Get user
        $user = User::findOrFail($request->route('id'));
        Log::info('User found: ' . $user->email);
        Log::info('Current email_verified_at: ' . ($user->email_verified_at ?? 'NULL'));

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            Log::info('User already verified, redirecting to login');
            return redirect()->route('login')->with('status', 'Email sudah terverifikasi sebelumnya.');
        }

        // Verify hash
        $expectedHash = sha1($user->getEmailForVerification());
        $providedHash = $request->route('hash');
        
        Log::info('Expected hash: ' . $expectedHash);
        Log::info('Provided hash: ' . $providedHash);
        Log::info('Hash match: ' . ($expectedHash === $providedHash ? 'YES' : 'NO'));

        if ($expectedHash !== $providedHash) {
            Log::error('Hash mismatch! Verification failed.');
            return redirect()->route('login')->with('error', 'Link verifikasi tidak valid.');
        }

        // Check signature
        if (!$request->hasValidSignature()) {
            Log::error('Invalid signature! Verification failed.');
            return redirect()->route('login')->with('error', 'Link verifikasi tidak valid atau sudah kedaluwarsa.');
        }

        // Mark as verified
        if ($user->markEmailAsVerified()) {
            Log::info('Email verified successfully!');
            Log::info('New email_verified_at: ' . $user->fresh()->email_verified_at);
            event(new Verified($user));
        }

        Log::info('=== EMAIL VERIFICATION COMPLETED ===');

        return redirect()->route('login')->with('status', 'Email berhasil diverifikasi! Silakan login.');
    }
}

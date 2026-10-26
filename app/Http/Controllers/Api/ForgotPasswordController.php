<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    
    public function sendOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $email = mb_strtolower(trim($validated['email']));
            $throttleKey = $this->otpSendThrottleKey($email);
            $deliveryFailureKey = $this->otpDeliveryFailureThrottleKey($email);

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (!$user) {
                if (!RateLimiter::tooManyAttempts($throttleKey, 3)) {
                    RateLimiter::hit($throttleKey, 60);
                }

                return $this->otpRequestAccepted();
            }

            $email = $user->email;
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::transaction(function () use ($email, $otp, $throttleKey, $deliveryFailureKey) {
                User::query()->where('email', $email)->lockForUpdate()->firstOrFail();
                if (RateLimiter::tooManyAttempts($deliveryFailureKey, 1)
                    || RateLimiter::tooManyAttempts($throttleKey, 3)) {
                    return;
                }
                RateLimiter::hit($throttleKey, 60);

                DB::table('password_resets')->where('email', $email)->delete();
                DB::table('password_resets')->insert([
                    'email' => $email,
                    'otp' => $this->otpVerifier($email, $otp),
                    'created_at' => Carbon::now(),
                ]);

                try {
                    Mail::raw(
                        "Kode OTP reset password Anda adalah: $otp\n\nKode ini berlaku selama 10 menit.",
                        function ($message) use ($email) {
                            $message->to($email)
                                ->subject('Kode OTP Reset Password');
                        }
                    );
                } catch (\Throwable $exception) {
                    DB::table('password_resets')->where('email', $email)->delete();
                    Log::error('Failed to send password reset OTP email.', [
                        'exception_type' => get_class($exception),
                    ]);
                    RateLimiter::hit($deliveryFailureKey, 600);
                }

            });

            return $this->otpRequestAccepted();
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset OTP request failed.', [
                'exception_type' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }

    /**
     * Step 2: Verifikasi OTP
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            ]);

            $email = $validated['email'];
            $otp = $validated['otp'];
            $throttleKey = $this->otpAttemptThrottleKey($email);

            $record = DB::transaction(function () use ($email, $otp, $throttleKey) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
                    ->lockForUpdate()
                    ->first();
                if (!$user) {
                    if (!RateLimiter::tooManyAttempts($throttleKey, 5)) {
                        RateLimiter::hit($throttleKey, 600);
                    }

                    return 'invalid';
                }

                if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                    return 'throttled';
                }

                $record = DB::table('password_resets')
                    ->where('email', $user->email)
                    ->orderByDesc('created_at')
                    ->lockForUpdate()
                    ->first();

                if (!$record || !$this->otpMatches($user->email, $otp, $record->otp)) {
                    RateLimiter::hit($throttleKey, 600);

                    return 'invalid';
                }

                if ($this->otpIsExpired($record->created_at)) {
                    DB::table('password_resets')->where('email', $user->email)->delete();
                    return 'expired';
                }

                RateLimiter::clear($throttleKey);

                return 'valid';
            });

            if ($record === 'throttled') {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan OTP. Silakan minta kode baru beberapa saat lagi.',
                ], 429);
            }

            if ($record === 'invalid') {
                return response()->json([
                    'message' => 'Kode OTP salah atau sudah tidak valid.',
                ], 422);
            }

            if ($record === 'expired') {
                return response()->json([
                    'message' => 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.',
                ], 422);
            }

            return response()->json([
                'message' => 'Kode OTP valid. Silakan reset password Anda.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset OTP verification failed.', [
                'exception_type' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }

    /**
     * Step 3: Reset Password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
                'password' => 'required|string|min:8|confirmed',
            ]);

            $email = $validated['email'];
            $otp = $validated['otp'];
            $throttleKey = $this->otpAttemptThrottleKey($email);

            $result = DB::transaction(function () use ($email, $otp, $validated, $throttleKey) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    if (!RateLimiter::tooManyAttempts($throttleKey, 5)) {
                        RateLimiter::hit($throttleKey, 600);
                    }

                    return 'invalid';
                }

                if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                    return 'throttled';
                }

                $record = DB::table('password_resets')
                    ->where('email', $user->email)
                    ->orderByDesc('created_at')
                    ->lockForUpdate()
                    ->first();

                if (!$record || !$this->otpMatches($user->email, $otp, $record->otp)) {
                    RateLimiter::hit($throttleKey, 600);

                    return 'invalid';
                }

                if ($this->otpIsExpired($record->created_at)) {
                    DB::table('password_resets')->where('email', $user->email)->delete();

                    return 'expired';
                }

                $user->password = Hash::make($validated['password']);
                $user->save();
                $user->tokens()->delete();
                DB::table('password_resets')->where('email', $user->email)->delete();
                RateLimiter::clear($throttleKey);

                return 'success';
            });

            if ($result === 'throttled') {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan OTP. Silakan minta kode baru beberapa saat lagi.',
                ], 429);
            }

            if ($result === 'invalid') {
                return response()->json([
                    'message' => 'Kode OTP salah atau sudah tidak valid.',
                ], 422);
            }

            if ($result === 'expired') {
                return response()->json([
                    'message' => 'Kode OTP sudah kadaluarsa.',
                ], 422);
            }

            return response()->json([
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset OTP consumption failed.', [
                'exception_type' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }

    private function otpRequestAccepted()
    {
        return response()->json([
            'message' => 'Jika email terdaftar, kode OTP akan dikirim.',
        ], 200);
    }

    private function otpSendThrottleKey(string $email): string
    {
        return 'password-reset-send:'.hash('sha256', mb_strtolower(trim($email)));
    }

    private function otpAttemptThrottleKey(string $email): string
    {
        return 'password-reset-otp:'.hash('sha256', mb_strtolower(trim($email)));
    }

    private function otpDeliveryFailureThrottleKey(string $email): string
    {
        return 'password-reset-delivery-failure:'.hash('sha256', mb_strtolower(trim($email)));
    }

    private function otpVerifier(string $email, string $otp): string
    {
        $key = (string) config('app.key');
        $digest = hash_hmac('sha256', mb_strtolower(trim($email)."\0".$otp), $key, true);
        $value = unpack('Nvalue', substr($digest, 0, 4))['value'] % (36 ** 6);

        return str_pad(base_convert((string) $value, 10, 36), 6, '0', STR_PAD_LEFT);
    }

    private function otpMatches(string $email, string $otp, string $storedVerifier): bool
    {
        $verifierMatches = hash_equals($storedVerifier, $this->otpVerifier($email, $otp));
        $legacyPlaintextMatches = hash_equals($storedVerifier, $otp);

        return $verifierMatches || $legacyPlaintextMatches;
    }

    private function otpIsExpired(?string $createdAt): bool
    {
        return $createdAt === null
            || Carbon::parse($createdAt)->addMinutes(10)->lte(Carbon::now());
    }
}

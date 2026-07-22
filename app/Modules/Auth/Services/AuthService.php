<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use App\Models\ActivityLog;
use Carbon\Carbon;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('USER');

        // Langsung generate & kirim OTP setelah register
        $this->generateAndSendOtp($user);

        return $user;
    }

    public function generateAndSendOtp(User $user)
    {
        $otp = random_int(100000, 999999); // 6 digit

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return true;
    }

    public function verifyOtp(string $email, string $otp)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak ditemukan.'],
            ]);
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            throw ValidationException::withMessages([
                'otp' => ['Tidak ada kode OTP aktif, silakan minta kode baru.'],
            ]);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP sudah kadaluarsa, silakan minta kode baru.'],
            ]);
        }

        if ($user->otp_code !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP salah.'],
            ]);
        }

        // OTP valid -> tandai email terverifikasi, hapus OTP
        $user->update([
            'email_verified_at' => Carbon::now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return $user;
    }

    public function resendOtp(string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak ditemukan.'],
            ]);
        }

        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Email sudah terverifikasi.'],
            ]);
        }

        $this->generateAndSendOtp($user);

        return true;
    }

    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Wajib verifikasi OTP dulu sebelum bisa login
        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Akun belum terverifikasi. Silakan cek email untuk kode OTP.'],
            ]);
        }

        if ($user->status === 'banned') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been banned permanently.'],
            ]);
        }

        if ($user->status === 'suspended') {
            if ($user->suspended_until && $user->suspended_until->isFuture()) {
                throw ValidationException::withMessages([
                    'email' => ['Your account is suspended until ' . $user->suspended_until->format('Y-m-d H:i:s') . ' (UTC).'],
                ]);
            } else {
                $user->update([
                    'status' => 'active',
                    'suspended_until' => null
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Login',
            'details' => 'User logged in via API',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Logout',
            'details' => 'User logged out',
        ]);

        $user->currentAccessToken()->delete();
    }

    public function forgotPassword(string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak ditemukan.'],
            ]);
        }

        $this->generateAndSendOtp($user);

        return true;
    }

    public function resetPassword(string $email, string $otp, string $newPassword)
    {
        $user = $this->verifyOtp($email, $otp);

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        // Fix #5: hapus semua token/sesi lama, biar sesi yang mungkin
        // dibajak orang lain (kalau alasan reset password ini karena
        // akun dibajak) langsung ke-invalidasi.
        $user->tokens()->delete();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Reset Password',
            'details' => 'User reset their password via OTP',
        ]);

        // SINKRONISASI KE SUPABASE
        $this->syncPasswordToSupabase($email, $newPassword);

        return true;
    }

    /**
     * Fix #4: hapus akun Laravel yang terlanjur dibuat tapi gagal
     * dibuat juga di Supabase Auth (dual-auth register gagal sebagian).
     * Hanya bisa hapus akun yang BELUM verifikasi OTP, supaya tidak
     * mungkin dipakai untuk menghapus akun orang lain yang sudah aktif.
     */
    public function rollbackUnverifiedRegistration(string $email): void
    {
        $user = User::where('email', $email)
            ->whereNull('email_verified_at')
            ->first();

        if ($user) {
            $user->delete();
        }
    }

    private function syncPasswordToSupabase(string $email, string $newPassword): void
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY') ?: env('SUPABASE_KEY');

        if (!$supabaseUrl || !$supabaseKey) {
            Log::warning('Supabase sync skipped: SUPABASE_URL or SUPABASE_KEY not set.');
            return;
        }

        try {
            // 1. Cari UUID Supabase berdasarkan Email menggunakan list users API
            $response = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => "Bearer {$supabaseKey}"
            ])->get("{$supabaseUrl}/auth/v1/admin/users");

            if ($response->successful()) {
                $users = $response->json();
                $supabaseUserId = null;

                // Supabase API returns array of users or an object containing users
                $userList = isset($users['users']) ? $users['users'] : $users;

                foreach ($userList as $u) {
                    if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                        $supabaseUserId = $u['id'];
                        break;
                    }
                }

                if ($supabaseUserId) {
                    // 2. Paksa update password user tersebut
                    $updateResponse = Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => "Bearer {$supabaseKey}",
                        'Content-Type' => 'application/json'
                    ])->put("{$supabaseUrl}/auth/v1/admin/users/{$supabaseUserId}", [
                        'password' => $newPassword
                    ]);

                    if (!$updateResponse->successful()) {
                        Log::error('Supabase password update failed: ' . $updateResponse->body());
                    }
                } else {
                    Log::warning("Supabase user not found for email: {$email}");
                }
            } else {
                Log::error('Supabase admin users list failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Supabase sync exception: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        return $this->successResponse($user, 'User registered successfully', 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $user = $this->authService->verifyOtp($request->email, $request->otp);

        return $this->successResponse($user, 'Email berhasil diverifikasi');
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->authService->resendOtp($request->email);

        return $this->successResponse(null, 'Kode OTP baru telah dikirim');
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse($result, 'Login successful');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
        ], 'User profile retrieved successfully');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->authService->forgotPassword($request->email);

        return $this->successResponse(null, 'Kode OTP pemulihan telah dikirim ke email');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $this->authService->resetPassword($request->email, $request->otp, $request->password);

        return $this->successResponse(null, 'Password berhasil direset. Silakan login dengan password baru.');
    }

    public function rollbackRegistration(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->authService->rollbackUnverifiedRegistration($request->email);

        return $this->successResponse(null, 'Registrasi dibatalkan, silakan coba daftar ulang.');
    }
}
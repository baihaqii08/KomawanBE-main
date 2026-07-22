<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\File;
use Illuminate\Http\Request;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\ActivityLog;

class AdminController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $totalUsers = User::role('USER')->count();
        $totalAdmins = User::role('ADMIN')->count() + User::role('SUPER_ADMIN')->count();
        $totalFiles = File::count();
        $totalStorage = File::sum('size'); // in bytes

        return $this->successResponse([
            'total_users' => $totalUsers,
            'total_admins' => $totalAdmins,
            'total_files' => $totalFiles,
            'total_storage' => (int) $totalStorage,
        ], 'Admin stats retrieved');
    }

    public function files(Request $request)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        // Fetch all files, eager loading user
        $files = File::with(['user' => function($q) {
            $q->select('id', 'name', 'email');
        }])->orderBy('created_at', 'desc')->get();

        return $this->successResponse($files, 'All files retrieved');
    }

    public function createAdmin(Request $request)
    {
        if (!$request->user()->hasRole('SUPER_ADMIN')) {
            return $this->errorResponse('Only Super Admin can create other admins.', 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $admin->assignRole('ADMIN');
        
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Admin Created',
            'details' => "Super Admin created a new Admin account: {$admin->email}",
        ]);

        return $this->successResponse($admin->load('roles'), 'Admin account created successfully', 201);
    }

    public function auditLogs(Request $request)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $logs = ActivityLog::with(['user' => function($q) {
            $q->select('id', 'name', 'email');
        }])->orderBy('created_at', 'desc')->limit(100)->get();

        return $this->successResponse($logs, 'Audit logs retrieved');
    }
}

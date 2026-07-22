<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\User;
use App\Models\ActivityLog;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $totalUsers = User::count();
        $totalFiles = File::count();
        $totalStorage = File::sum('size');
        $recentUploads = File::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        $latestUsers = User::orderBy('created_at', 'desc')->take(5)->get();
        $recentActivities = ActivityLog::with('user')->orderBy('created_at', 'desc')->take(10)->get();

        return $this->successResponse([
            'totalUsers' => $totalUsers,
            'totalFiles' => $totalFiles,
            'totalStorage' => $totalStorage,
            'recentUploads' => $recentUploads,
            'latestUsers' => $latestUsers,
            'recentActivities' => $recentActivities,
        ], 'Dashboard stats retrieved');
    }
}

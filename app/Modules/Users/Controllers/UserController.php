<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $users = User::with('roles')->get();
        return $this->successResponse($users, 'Users retrieved successfully');
    }

    public function ban(Request $request, $id)
    {
        if (!$request->user()->hasRole('SUPER_ADMIN')) {
            return $this->errorResponse('Only Super Admin can ban users', 403);
        }

        $user = User::findOrFail($id);
        if ($user->hasRole('SUPER_ADMIN')) {
            return $this->errorResponse('Cannot ban a Super Admin', 400);
        }

        $user->update(['status' => 'banned', 'suspended_until' => null]);
        
        // Force logout user tokens
        $user->tokens()->delete();

        return $this->successResponse($user->load('roles'), 'User banned successfully');
    }

    public function unban(Request $request, $id)
    {
        if (!$request->user()->hasRole('SUPER_ADMIN')) {
            return $this->errorResponse('Only Super Admin can unban users', 403);
        }

        $user = User::findOrFail($id);
        $user->update(['status' => 'active', 'suspended_until' => null]);

        return $this->successResponse($user->load('roles'), 'User unbanned successfully');
    }

    public function suspend(Request $request, $id)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'hours' => 'required|integer|min:1|max:12'
        ]);

        $user = User::findOrFail($id);
        
        if ($user->hasRole('SUPER_ADMIN') || ($user->hasRole('ADMIN') && !$request->user()->hasRole('SUPER_ADMIN'))) {
            return $this->errorResponse('You cannot suspend this user', 403);
        }

        $user->update([
            'status' => 'suspended',
            'suspended_until' => now()->addHours($request->hours)
        ]);

        // Force logout
        $user->tokens()->delete();

        return $this->successResponse($user->load('roles'), "User suspended for {$request->hours} hours");
    }

    public function unsuspend(Request $request, $id)
    {
        if (!$request->user()->hasRole(['SUPER_ADMIN', 'ADMIN'])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $user = User::findOrFail($id);
        $user->update(['status' => 'active', 'suspended_until' => null]);

        return $this->successResponse($user->load('roles'), 'User suspension revoked');
    }
}

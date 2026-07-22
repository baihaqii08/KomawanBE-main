<?php

namespace App\Modules\Subscription\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionRequest;
use App\Models\ActivityLog;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function requestUpgrade(Request $request)
    {
        $user = $request->user();

        // Check if already requested
        $existing = SubscriptionRequest::where('user_id', $user->id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->first();

        if ($existing) {
            return $this->errorResponse('Upgrade request already exists or approved.');
        }

        $subscriptionRequest = SubscriptionRequest::create([
            'user_id' => $user->id,
            'status' => 'Pending',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Upgrade Request',
            'details' => 'User requested upgrade to Pro',
        ]);

        return $this->successResponse($subscriptionRequest, 'Upgrade request submitted successfully');
    }
}

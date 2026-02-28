<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index($channelId)
    {
        $channel = User::findOrFail($channelId);

        $subscribers = $channel->subscribers()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar_path')
            ->withCount('subscribers')
            ->latest('pivot_created_at')
            ->get();

        return response()->json([
            'subscribers' => $subscribers,
            'count' => $subscribers->count(),
        ]);
    }
}

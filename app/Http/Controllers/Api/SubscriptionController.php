<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Подписаться/отписаться на канал
     */
    public function toggle(Request $request, $channelId)
    {
        $channel = User::findOrFail($channelId);
        $user = $request->user();

        // Нельзя подписаться на себя
        if ($channel->id === $user->id) {
            return response()->json([
                'message' => 'Нельзя подписаться на самого себя',
            ], 422);
        }

        // Проверяем текущую подписку
        $isSubscribed = $user->subscriptions()->where('channel_id', $channelId)->exists();

        if ($isSubscribed) {
            // Отписаться
            $user->subscriptions()->detach($channelId);
            return response()->json([
                'message' => 'Вы отписались от канала',
                'is_subscribed' => false,
                'subscribers_count' => $channel->subscribers()->count(),
            ]);
        } else {
            // Подписаться
            $user->subscriptions()->attach($channelId);
            return response()->json([
                'message' => 'Вы подписались на канал',
                'is_subscribed' => true,
                'subscribers_count' => $channel->subscribers()->count(),
            ]);
        }
    }

    /**
     * Проверить статус подписки
     */
    public function status(Request $request, $channelId)
    {
        $user = $request->user();
        $isSubscribed = $user->subscriptions()->where('channel_id', $channelId)->exists();

        return response()->json([
            'is_subscribed' => $isSubscribed,
        ]);
    }

    /**
     * Список подписок пользователя
     */
    public function index(Request $request)
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->with(['videos' => function ($query) {
                $query->where('is_public', true)
                    ->latest()
                    ->limit(5);
            }])
            ->withCount('subscribers')
            ->latest('pivot_created_at')
            ->paginate($request->get('per_page', 12));

        return response()->json($subscriptions);
    }
}

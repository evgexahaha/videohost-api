<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoLikerController extends Controller
{
    public function index($videoId)
    {
        $video = Video::findOrFail($videoId);

        $likers = $video->likedByUsers()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar_path')
            ->withCount('subscribers')
            ->latest('pivot_created_at')
            ->get();

        return response()->json([
            'likers' => $likers,
            'count' => $likers->count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Video;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);

        $like = Like::where('user_id', auth()->id())
            ->where('video_id', $videoId)
            ->first();

        if ($like) {
            $like->delete();
            $isLiked = false;
        } else {
            Like::create([
                'user_id' => auth()->id(),
                'video_id' => $videoId,
            ]);
            $isLiked = true;
        }

        return response()->json([
            'message' => $isLiked ? 'Лайк поставлен' : 'Лайк удален',
            'is_liked' => $isLiked,
            'likes_count' => $video->likes()->count(),
        ]);
    }

    public function index(Request $request)
    {
        $videos = auth()->user()
            ->likedVideos()
            ->with(['user'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($request->get('per_page', 12));

        $videos->getCollection()->transform(function ($video) {
            $video->is_liked = true;
            return $video;
        });

        return response()->json($videos);
    }
}

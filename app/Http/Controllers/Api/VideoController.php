<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with(['user', 'likes', 'comments'])
            ->where('is_public', true);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $videos = $query->latest()->paginate($request->get('per_page', 12));

        $videos->getCollection()->transform(function ($video) {
            $video->likes_count = $video->likes()->count();
            $video->comments_count = $video->comments()->count();
            $video->is_liked = $video->isLikedBy(auth()->user());
            return $video;
        });

        return response()->json($videos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'required|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:1048576',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
            'duration' => 'nullable|string',
        ]);

        $videoPath = $request->file('video')->store('videos', 'public');
        $thumbnailPath = null;

        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        // Преобразуем is_public из строки в boolean
        $isPublic = $request->has('is_public') 
            ? filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $video = Video::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbnailPath,
            'duration' => $request->duration,
            'is_public' => $isPublic,
        ]);

        $video->load('user');
        $video->likes_count = 0;
        $video->comments_count = 0;
        $video->is_liked = false;

        return response()->json([
            'message' => 'Видео успешно загружено',
            'video' => $video,
        ], 201);
    }

    public function show($id)
    {
        $video = Video::with(['user', 'likes', 'comments.user'])->findOrFail($id);

        if (!$video->is_public && $video->user_id !== auth()->id()) {
            return response()->json(['message' => 'Видео недоступно'], 403);
        }

        $video->increment('views');
        $video->likes_count = $video->likes()->count();
        $video->comments_count = $video->comments()->count();
        $video->is_liked = $video->isLikedBy(auth()->user());

        return response()->json([
            'video' => $video,
        ]);
    }

    public function update(Request $request, $id)
    {
        $video = Video::findOrFail($id);

        if ($video->user_id !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
            'is_public' => 'boolean',
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
            $video->thumbnail_path = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $video->fill($request->only(['title', 'description', 'is_public']));
        $video->save();

        $video->load('user');
        $video->likes_count = $video->likes()->count();
        $video->comments_count = $video->comments()->count();

        return response()->json([
            'message' => 'Видео успешно обновлено',
            'video' => $video,
        ]);
    }

    public function destroy($id)
    {
        $video = Video::findOrFail($id);

        if ($video->user_id !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        Storage::disk('public')->delete($video->video_path);
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }

        $video->delete();

        return response()->json([
            'message' => 'Видео успешно удалено',
        ]);
    }

    public function trending()
    {
        $videos = Video::with(['user'])
            ->where('is_public', true)
            ->withCount(['likes', 'comments'])
            ->orderBy('views', 'desc')
            ->orderBy('likes_count', 'desc')
            ->limit(10)
            ->get();

        $videos->each(function ($video) {
            $video->is_liked = $video->isLikedBy(auth()->user());
        });

        return response()->json([
            'videos' => $videos,
        ]);
    }
}

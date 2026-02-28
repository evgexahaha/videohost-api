<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($videoId)
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('video_id', $videoId)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, $videoId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $video = Video::findOrFail($videoId);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'video_id' => $videoId,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $comment->load('user', 'replies.user');

        return response()->json([
            'message' => 'Комментарий успешно добавлен',
            'comment' => $comment,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Комментарий успешно обновлен',
            'comment' => $comment,
        ]);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Комментарий успешно удален',
        ]);
    }
}

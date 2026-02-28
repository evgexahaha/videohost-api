<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show($id = null)
    {
        $user = $id ? User::findOrFail($id) : auth()->user();

        $videos = $user->videos()
            ->where('is_public', true)
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $totalViews = $user->videos()->sum('views');
        $totalVideos = $user->videos()->count();
        $subscribersCount = $user->subscribers()->count();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'avatar_path' => $user->avatar_path,
                'created_at' => $user->created_at,
                'total_videos' => $totalVideos,
                'total_views' => $totalViews,
                'subscribers_count' => $subscribersCount,
            ],
            'videos' => $videos,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
            'current_password' => 'required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->has('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Неверный текущий пароль'], 422);
            }
            $user->password = Hash::make($request->password);
        }

        $user->fill($request->only(['name', 'email', 'bio']));
        $user->save();

        return response()->json([
            'message' => 'Профиль успешно обновлен',
            'user' => $user,
        ]);
    }

    public function myVideos(Request $request)
    {
        $videos = auth()->user()
            ->videos()
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($request->get('per_page', 12));

        return response()->json($videos);
    }

    public function destroyAccount()
    {
        $user = auth()->user();

        foreach ($user->videos as $video) {
            Storage::disk('public')->delete($video->video_path);
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        return response()->json([
            'message' => 'Аккаунт успешно удален',
        ]);
    }
}

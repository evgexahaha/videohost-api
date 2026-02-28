<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likedVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'likes')->withTimestamps();
    }

    // Подписчики канала
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'channel_id', 'subscriber_id')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    // На кого подписан пользователь
    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'subscriber_id', 'channel_id')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    // Проверка подписки
    public function isSubscribedTo(User $channel): bool
    {
        return $this->subscriptions()->where('channel_id', $channel->id)->exists();
    }

    // Количество подписчиков
    public function getSubscribersCountAttribute(): int
    {
        return $this->subscribers()->count();
    }
}

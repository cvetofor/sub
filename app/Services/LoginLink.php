<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Вход в кабинет по короткой ссылке из СМС, без кода.
 * Ссылка живёт 30 дней, одна на пользователя: новая заменяет старую.
 */
class LoginLink {
    public const TTL_DAYS = 30;

    /** Выдать ссылку для пользователя: токен в базе, адрес вида /k/XXXXXXXXXXXX. */
    public static function for(User $user, array $query = []): string {
        $token = Str::random(12);
        $user->forceFill(['login_token' => $token, 'login_token_expires_at' => Carbon::now()->addDays(self::TTL_DAYS)])->save();

        return route('auth.link', ['token' => $token] + $query);
    }

    /** Пользователь по действующему токену или null. Токен не сгорает: ссылка нужна ещё раз через неделю. */
    public static function user(string $token): ?User {
        if (!preg_match('/^[A-Za-z0-9]{12}$/', $token)) {
            return null;
        }
        $user = User::where('login_token', $token)->first();
        if (!$user || !$user->is_active || !$user->isClient()) {
            return null;
        }
        if (!$user->login_token_expires_at || Carbon::parse($user->login_token_expires_at)->isPast()) {
            return null;
        }

        return $user;
    }
}

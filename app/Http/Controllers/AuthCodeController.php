<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\SmsCode;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Вход и регистрация клиента по номеру телефона и коду из СМС.
 * Один и тот же код используется на странице входа и на шаге 4 мастера.
 */
class AuthCodeController extends Controller {
    public function show() {
        if (Auth::check()) {
            return redirect()->route('profile');
        }

        return view('auth.login');
    }

    public function sendCode(Request $request, SmsCode $sms) {
        $data = $request->validate(['phone' => 'required|string|max:32']);
        $phone = Phone::normalize($data['phone']);
        if (!$phone) {
            return response()->json(['ok' => false, 'message' => 'Проверьте номер: нужен российский мобильный, например +7 912 345-67-89.'], 422);
        }

        $result = $sms->send($phone, (string) $request->ip());
        if (!$result['ok']) {
            $wait = $result['retry_after'] ?? 60;

            return response()->json(['ok' => false, 'retry_after' => $wait, 'message' => "Код уже отправлен. Повторить можно через {$wait} сек."], 429);
        }

        return response()->json(array_filter([
            'ok' => true,
            'phone' => $phone,
            'pretty' => Phone::pretty($phone),
            'retry_after' => $result['retry_after'],
            'debug_code' => $result['debug_code'] ?? null,
        ], fn ($v) => $v !== null));
    }

    public function verify(Request $request, SmsCode $sms) {
        $data = $request->validate([
            'phone' => 'required|string|max:32',
            'code' => 'required|digits:4',
            'name' => 'nullable|string|min:2|max:120',
        ]);
        $phone = Phone::normalize($data['phone']);
        if (!$phone || !$sms->verify($phone, $data['code'])) {
            return response()->json(['ok' => false, 'message' => 'Код не подошёл. Проверьте цифры или запросите новый.'], 422);
        }

        $user = self::findOrCreateClient($phone, $data['name'] ?? null);
        Auth::login($user, true);
        $request->session()->regenerate();

        // Сессия пересоздана, поэтому отдаём новый CSRF-токен: мастер подставит его в следующий запрос
        return response()->json(['ok' => true, 'csrf' => csrf_token(), 'user' => ['id' => $user->id, 'name' => $user->name, 'phone' => Phone::pretty($phone)]]);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Ищем клиента по единому номеру, затем по старому формату, иначе создаём. */
    public static function findOrCreateClient(string $phoneE164, ?string $name = null): User {
        $user = User::where('phone_e164', $phoneE164)->first()
            ?? User::where('phone', Phone::legacy($phoneE164))->first()
            ?? User::where('phone', $phoneE164)->first();

        if ($user) {
            $changes = [];
            if (!$user->phone_e164) {
                $changes['phone_e164'] = $phoneE164;
            }
            if ($name && ($user->name === '' || $user->name === 'Клиент')) {
                $changes['name'] = $name;
            }
            if (!$user->is_active) {
                $changes['is_active'] = true;
            }
            if ($changes) {
                $user->forceFill($changes)->save();
            }

            return $user;
        }

        return User::create([
            'name' => $name ?: 'Клиент',
            'email' => 'client-' . ltrim($phoneE164, '+') . '@phone.local',
            'phone' => $phoneE164,
            'phone_e164' => $phoneE164,
            'password' => Hash::make(Str::random(40)),
            'role_id' => Role::CLIENT,
            'is_active' => true,
        ]);
    }
}

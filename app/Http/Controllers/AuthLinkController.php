<?php

namespace App\Http\Controllers;

use App\Services\LoginLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Вход по ссылке из СМС: /k/{token}. Открыл ссылку — уже в кабинете. */
class AuthLinkController extends Controller {
    public function __invoke(Request $request, string $token) {
        $user = LoginLink::user($token);
        if (!$user) {
            return redirect()->route('auth.login')->with('error', 'Ссылка устарела. Введите телефон, пришлём код для входа.');
        }
        if (!Auth::check() || (int) Auth::id() !== (int) $user->id) {
            Auth::login($user, true);
            $request->session()->regenerate();
            Log::channel('shop')->info('Вход по ссылке из СМС.', ['user_id' => $user->id]);
        }

        // Из СМС о неоплаченной заявке: сразу к оплате своей подписки
        $payId = $request->integer('pay');
        if ($payId) {
            $sub = \App\Models\Subscription::find($payId);
            if ($sub && $sub->belongsToUser($user)) {
                return redirect()->route('subscription.pay', $sub);
            }
        }

        return redirect()->route('profile');
    }
}

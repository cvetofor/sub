<?php

namespace App\Http\Controllers;

use App\Http\Api\SMSC;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller {
    public function index() {
        return view('profile.index');
    }

    public function authView() {
        return view('profile.auth');
    }

    public function resetPassword(Request $request) {
        $user = User::where('phone', $request->phone)
            ->where('role_id', Role::CLIENT)
            ->first();

        if (!$user) {
            session()->flash('error', 'Пользователь не найден!');

            return redirect()->back();
        }

        $generatedPassword = self::generateCode();
        $user->password = Hash::make($generatedPassword);
        $user->save();

        $smsc = new SMSC(preg_replace('/\D/', '', $request->phone));
        $message = 'Пароль для входа в личный кабинет на подписка.цветофор.рф - ' . $generatedPassword;

        $response = $smsc->sendSMS($message);

        Log::channel('shop')->info('Был отправлен новый пароль от личного кабинета.', [$response]);

        if ($response) {
            session()->flash('success', 'Новый пароль был отправлен на ваш номер телефона!');
        } else {
            session()->flash('error', 'Произошла ошибка при отправке пароля. Попробуйте снова.');
        }

        return redirect()->back();
    }

    public function registerUser($phone, $name) {
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser) {
            return false;
        }

        $generatedPassword = self::generateCode();

        $user = User::create([
            'name'     => $name,
            'is_active' => true,
            'phone'    => $phone,
            'role_id'  => Role::CLIENT,
            'password' => Hash::make($generatedPassword),
        ]);

        $phone = preg_replace('/\D/', '', $phone);
        $smsc = new SMSC($phone);
        $message = 'Ваш пароль для входа в личный кабинет на подписка.цветофор.рф - ' . $generatedPassword;
        $response = $smsc->sendSMS($message);

        Log::channel('shop')->info('Новый пользователь зарегистрирован и получен пароль.', [
            'phone'    => $phone,
            'sms_sent' => $response,
        ]);

        return true;
    }

    public function login(Request $request) {
        $request->validate([
            'phone'    => 'required',
            'password' => 'required',
        ]);

        $user = User::where('phone', $request->phone)
            ->where('role_id', Role::CLIENT)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::channel('shop')->warning('Ошибка авторизации.', [
                'phone'   => $request->input('phone'),
                'ip'      => $request->ip(),
                'agent'   => $request->header('User-Agent'),
                'payload' => $request->all(),
            ]);

            session()->flash('error', 'Неверный телефон или пароль.');

            return redirect()->back();
        }

        Auth::login($user);

        return redirect()->route('profile');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private static function generateCode() {
        $digits = [];
        foreach (range(0, 9) as $d) {
            $digits[] = $d;
            $digits[] = $d;
        }

        shuffle($digits);

        $codeDigits = array_slice($digits, 0, 6);

        if ($codeDigits[0] === 0) {
            foreach ($codeDigits as $i => $digit) {
                if ($digit !== 0) {
                    $codeDigits[0] = $digit;
                    $codeDigits[$i] = 0;
                    break;
                }
            }
        }

        return implode('', $codeDigits);
    }
}

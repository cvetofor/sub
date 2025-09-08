<?php

namespace App\Http\Api;

class SMSC {
    private $login, $password, $phone;

    public function __construct($phone) {
        $this->login = config('smsc.login');
        $this->password = config('smsc.password');
        $this->phone = $phone;
    }

    public function sendSMS($message) {
        $params = [
            'login'  =>  $this->login,
            'psw'    =>  $this->password,
            'phones' => $this->phone,
            'mes'    => $message,
            'fmt'    => 3,
        ];

        return $this->sendCurl('https://smsc.ru/sys/send.php?', $params);
    }

    private function sendCurl($url, $params) {
        $ch = curl_init($url . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}

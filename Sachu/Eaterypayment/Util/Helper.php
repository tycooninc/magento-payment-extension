<?php

namespace Sachu\Eaterypayment\Util;

class Helper
{
    public static function sendRequestWithBasicAuth($url, $data, $merchantNo, $merchantSecret)
    {
        $authorization = base64_encode($merchantNo . ":" . $merchantSecret);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . $authorization
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}

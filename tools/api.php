<?php

namespace Eviger\EvigerAPI;

use CURLFile;

class API {

    private $token = "";

    private $useToken;

    public function __construct($token) {
        isset($token) ? $this->token = $token : false;
    }

    public static function create($token) {
        return new self($token);
    }

    public function requestGet($method, $params = [], $useToken = false) {
        $url = 'https://api.eviger.ru/methods/' . $method;
        isset($this->useToken) ? $params['token'] = $this->token : false;

        return $this->requests($url, $params, false);
    }
    public function requestPost($method, $params = [], $useToken = false) {
        $url = 'https://api.eviger.ru/methods/' . $method;
        $this->useToken ? $params['token'] = $this->token : false;

        return $this->requests($url, $params, true);
    }

    private function requests($url, $params, $post) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post === false ? $url."?".http_build_query($params) : $url);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = json_decode(curl_exec($ch), True);
        curl_close($ch);

        if (!isset($result)) {
            return null;
        }
        return $result;
    }

}
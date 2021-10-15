<?php

declare(strict_types=1);

namespace Eviger\Api\Tools;

class Api
{
    private const API_URI = "https://api.eviger.ru/methods/";

    private ?string $token;

    public function __construct(string $token = null)
    {
        $this->token = $token;
    }


    /**
     * @param string $method
     * @param array $params
     * @return array|null
     */
    public function requestGet(string $method, array $params = [])
    {
        return $this->request(self::API_URI . $method, $params, false);
    }

    /**
     * @param string $method
     * @param array $params
     * @return array|null
     */
    public function requestPost(string $method, array $params = [])
    {
        return $this->request(self::API_URI . $method, $params, true);
    }

    /**
     * @param string $url
     * @param array $params
     * @param bool $post
     * @return array|null
     */
    private function request(string $url, array $params, bool $post): ?array
    {
        $params['token'] = $this->token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post ?: $url . "?" . http_build_query($params));
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

        $raw_data = curl_exec($ch);
        $response = @json_decode($raw_data, true);

        curl_close($ch);

        return $response ?: null;
    }

}
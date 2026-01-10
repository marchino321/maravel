<?php

namespace Core\Helpers;

use App\Config;

class WooCommerceClient
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(Config::$base_url_wc, '/') . '/';
        $this->consumerKey = Config::$client_key_wc;
        $this->consumerSecret = Config::$secret_key_wc;
    }

    private function request($method, $endpoint, $data = [])
    {
        $url = $this->baseUrl . ltrim($endpoint, '/');
        $ch = curl_init();

        // Impostazioni di base
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_USERPWD => $this->consumerKey . ":" . $this->consumerSecret,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        // Se ci sono dati (POST/PUT)
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception("WooCommerce API Error: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \Exception("WooCommerce API HTTP $httpCode: " . $response);
        }

        return $decoded;
    }

    public function get($endpoint, $params = [])
    {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->request('GET', $endpoint . $query);
    }

    public function post($endpoint, $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put($endpoint, $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete($endpoint, $data = [])
    {
        return $this->request('DELETE', $endpoint, $data);
    }
}

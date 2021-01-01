<?php

namespace Lcmaquino\GoogleOAuth2;

class HttpClient
{
    protected $ch;

    protected function init()
    {
        $this->ch = curl_init();
    }

    protected function close()
    {
        curl_close($this->ch);
    }

    /**
     * Do a GET http request.
     *
     * @param string $url
     * @param array $query
     * @return array|null
     */
    public function get($url = '', $query = [])
    {
        $this->init();

        $protocol = str_starts_with($url, 'https') ? 'https' : 'http';

        curl_setopt($this->ch, CURLOPT_URL, $url . '?' . http_build_query($query));

        if(!empty($header)) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        }
        return $this->curlToJson($protocol);
    }

    /**
     * Do a POST http request.
     *
     * @param string $url
     * @param array $query
     * @return array
     */
    public function post($url = '', $query = [])
    {
        $this->init();

        $protocol = str_starts_with($url, 'https') ? 'https' : 'http';

        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($query));
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen(http_build_query($query)),
        ]);

        return $this->curlToJson($protocol);
    }

    /**
     * Perform a cURL session and return the result as an JSON
     * associative array.
     *
     * @param string $protocol
     * @return array|null
     */
    private function curlToJson($protocol = 'http') 
    {
        if ($protocol === 'http') {
            curl_setopt($this->ch, CURLOPT_PORT, 80);
        } else {
            curl_setopt($this->ch, CURLOPT_PORT, 443);
        }

        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $rawData = curl_exec($this->ch);

        if (curl_errno($this->ch)) {
            $rawData = null;
        }

        $this->close();

        $jsonData = empty($rawData) ? null : json_decode($rawData, true);
        
        return $jsonData;
    }
}
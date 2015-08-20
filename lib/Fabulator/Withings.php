<?php
namespace Fabulator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Withings
{

    private $email;
    private $password;

    private $sessionKey;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Parse header
     * @param  array  $headers
     * @return [type]          [description]
     */
    private function parseHeaders(array $headers)
    {
        $output = array();

        if ('HTTP' === substr($headers[0], 0, 4)) {
            list(, $output['status'], $output['status_text']) = explode(' ', $headers[0]);
            unset($headers[0]);
        }

        foreach ($headers as $v) {
            $h = preg_split('/:\s*/', $v);
            @$output[strtolower($h[0])] = $h[1];
        }

        return $output;
    }

    /**
     * Request session key from Withings
     * @return string
     */
    public function getSessionKey()
    {

        $loginUrl = 'https://account.withings.com/connectionuser/account_login?appname=my2&appliver=8b90cb73&r=https%3A%2F%2Fhealthmate.withings.com%2F';

        $fields = [
            'email' => $this->email,
            'password' => $this->password
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if (!($result = curl_exec($ch))) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $headerArray = ($this->parseHeaders(explode("\n", $header)));
        $cookie = explode(";", $headerArray['set-cookie']);
        $session = str_replace("session_key=", '', $cookie[0]);
        $this->sessionKey = $session;
        return $session;
    }

    /**
     * Set session key
     * @param string $sessionKey
     */
    public function setSessionKey($sessionKey)
    {
        $this->sessionKey = $sessionKey;
    }

    /**
     * Get air quality (temperature + CO2 levels)
     * @param  \DateTime $start
     * @param  \DateTime $end
     * @param  integer    $deviceId Id of device you are measuring
     * @return object
     */
    public function getAirQuality(\DateTime $start, \DateTime $end, $deviceId)
    {
        $params = [
            'action' => 'getmeashf',
            'startdate' => $start->getTimestamp(),
            'enddate' => $end->getTimestamp(),
            'meastype' => '12,35',
            'deviceid' => $deviceId
        ];
        return $this->requestAPI('v2/measure', $params);
    }

    /**
     * Get info about device
     * @param  integer $deviceId
     * @return object
     */
    public function getDeviceInfo($deviceId)
    {
        $params = [
            'deviceid' => $deviceId,
            'action' => 'getproperties'
        ];
        return $this->requestAPI('device', $params);
    }

    /**
     * Request Withings API
     * @param  string $service
     * @param  array $params
     * @return object
     */
    public function requestAPI($service, $params = [])
    {
        $client = new Client();

        $defaultParams = [
            'sessionid' => $this->sessionKey,
            'appname' => 'my2',
            'appliver' => '8b90cb73',
            'apppfm' => 'web'
        ];

        $params = array_merge($defaultParams, $params);

        $request = $client->post(
            'https://healthmate.withings.com/index/service/' . $service,
            [
            'body' => $params,
            ]
        );

        $values = json_decode($request->getBody()->getContents());

        if (isset($values->error)) {
            throw new \Exception("Error: " . $values->error);
        }

        return $values;
    }
}

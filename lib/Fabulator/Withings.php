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
     * Request session key from Withings
     * @return string
     */
    public function getSessionKey()
    {
        $fields = [
            'email' => $this->email,
            'password' => $this->password
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
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
            'startdate' => $end->getTimestamp(),
            'enddate' => $end->getTimestamp(),
            'meastype' => '12,35',
            'deviceid' => $deviceId
        ];
        return requestAPI('measure', $params);
    }

    /**
     * Request Withings API
     * @param  string $service
     * @param  array $params
     * @return object
     */
    public function requestAPI($service, $params)
    {
        $client = new Client();

        $defaultParams = [
            'sessionid' => $this->session,
            'appname' => 'my2',
            'appliver' => '8b90cb73',
            'apppfm' => 'web'
        ];

        $params = array_merge($defaultParams, $params);

        $request = $client->post(
            'https://healthmate.withings.com/index/service/v2/' . $service,
            [
            'body' => $params
            ]
        );

        $values = json_decode($request->getBody()->getContents());

        if (isset($values->error)) {
            throw new \Exception("Error: " . $values->error);
        }

        return $values;
    }
}

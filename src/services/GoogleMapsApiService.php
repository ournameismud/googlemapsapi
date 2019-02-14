<?php

/**
 * GoogleMapsApi plugin for Craft CMS 3.x
 *
 * GoogleMapsApi Service
 *
 * @link      https://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 * @author Klearchos Douvantzis
 */

namespace bolden\googlemapsapi\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\elements\Entry;
use craft\services\Elements;
use yii\base\Event;
use craft\elements\db\ElementQuery;
use yii\db\IntegrityException;
use bolden\googlemapsapi\GoogleMapsApi;
use function GuzzleHttp\json_decode;

/**
 * GoogleMapsApi Service
 */
class GoogleMapsApiService extends Component
{
    private $params = [];
    private $settings = [];

    public function __construct()
    {
        $this->settings = GoogleMapsApi::getInstance()->getSettings();
        $this->params['baseUrl'] = 'https://maps.googleapis.com/maps/api/';
        $this->params['format'] = 'json';
        parent::__construct();
    }

    public function timezone($lat, $lon, $timestamp)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
        
        $result = $this->request('timezone', 'location=' . $lat . ',' . $lon . '&timestamp=' . $timestamp . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function elevation($lat, $lon)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
        
        $result = $this->request('elevation', 'locations=' . $lat . ',' . $lon . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['results']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function distance($originLat, $originLon, $destinationLat, $destinationLon, $mode)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];                
        
        $result = $this->request('distancematrix', 'origins=' . $originLat . ',' . $originLon . '&destinations=' . $destinationLat . ',' . $destinationLon . '&mode=' . $mode . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['rows']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function geocode($lat, $lon)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
                
        $result = $this->request('geocode', 'latlng=' . $lat . ',' . $lon . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['results']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function placeFromText($input, $fields)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
        
        $parameters = 'input=' . $input . '&inputtype=textquery&key=' . $apiKey;
        if (!empty($fields)) {
            $parameters .= '&fields=' . $fields;
        }
        $result = $this->request('place/findplacefromtext', $parameters);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['candidates']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function placeAutocomplete($input)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
        
        $result = $this->request('place/autocomplete', 'input=' . $input . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['predictions']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    public function placeDetails($placeId)
    {
        $apiKey = $this->settings->googleMapsApiKey;
        if ($apiKey == '') $apiKey = Craft::$app->config->general->custom['googleApi'];
                
        $result = $this->request('place/details', 'placeid=' . $placeId . '&key=' . $apiKey);
        if ($result['status']) {
            return [
                'status' => true,
                'data' => $result['data']['result']
            ];
        } else {
            return [
                'status' => false,
                'error' => $result['error']
            ];
        }
    }

    private function request($type, $params)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $url = $this->params['baseUrl'] . $type . '/' . $this->params['format'] . '?' . $params;
            $response = $client->request('GET', $url);
            if ($response->getStatusCode() === 200) {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                return [
                    "status" => true,
                    "data" => $data
                ];
            }
            return [
                "status" => false,
                "error" => "Server Error"
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "error" => $e->getMessage()
            ];
        }
    }

}

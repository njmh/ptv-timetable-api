<?php

namespace Njmh\PtvTimetableApi;

use DateTime;
use DateTimeZone;

/**
*  PTV Timetable API
*
*  A simple wrapper class for the PTV (Public Transport Victoria) Timetable API.
*  All data provided by this API is Licensed from Public Transport Victoria
*  under a Creative Commons Attribution 4.0 International Licence.
*
*  @author Nick Morton (nick.john.morton@gmail.com)
*  @licence MIT
*
*  @see http://timetableapi.ptv.vic.gov.au/swagger/ui/index
*/
class PtvTimetableApi {

    /**
     * The PTV API developer ID
     *
     * @var string
     * @see https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF
     */
    private $developerId = '';

    /**
     * The PTV API developer key
     *
     * @var string
     * @see https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF
     */
    private $developerKey = false;

    /**
     * The currently generated API endpoint
     *
     * @var string
     */
    private $url = '';

    /**
     * The PTV API base URL
     *
     * @var string
     */
    private $apiUrl = 'https://timetableapi.ptv.vic.gov.au';

    /**
     * Route type constants
     */
    const TRAIN = 0;
    const TRAM = 1;
    const BUS = 2;
    const VLINE = 3;
    const NIGHT_BUS = 4;

    /**
     * List of all API endpoints
     *
     * @var array
     */
    private $apiEndpoints = [
        'departures'            => '/v3/departures/route_type/{route_type}/stop/{stop_id}',
        'departuresRoute'       => '/v3/departures/route_type/{route_type}/stop/{stop_id}/route/{route_id}',
        'directionsByRoute'     => '/v3/directions/route/{route_id}',
        'directionRoutes'       => '/v3/directions/{direction_id}',
        'directionRoutesByType' => '/v3/directions/{direction_id}/route_type/{route_type}',
        'disruptions'           => '/v3/disruptions',
        'disruptionsByRoute'    => '/v3/disruptions/route/{route_id}',
        'disruption'            => '/v3/disruptions/{disruption_id}',
        'patterns'              => '/v3/pattern/run/{run_id}/route_type/{route_type}',
        'routes'                => '/v3/routes',
        'route'                 => '/v3/routes/{route_id}',
        'routeTypes'            => '/v3/route_types',
        'runs'                  => '/v3/runs/route/{route_id}',
        'run'                   => '/v3/runs/{run_id}',
        'runByType'             => '/v3/runs/{run_id}/route_type/{route_type}',
        'search'                => '/v3/search/{search_term}',
        'stopFacilities'        => '/v3/stops/{stop_id}/route_type/{route_type}',
        'stopsByRoute'          => '/v3/stops/route/{route_id}/route_type/{route_type}',
        'stopsNear'             => '/v3/stops/location/{latitude},{longitude}',
    ];

    /**
     * Set the PTV Timetable API Developer ID
     *
     * @param  string $developerId
     *
     * @see https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF
     *
     */
    public function setDeveloperId($developerId) {
        $this->developerId = $developerId;
    }

    /**
     * Set the PTV Timetable API Developer Key
     *
     * @param  string $developerKey
     *
     * @see https://static.ptv.vic.gov.au/PTV/PTV%20docs/API/1475462320/PTV-Timetable-API-key-and-signature-document.RTF
     *
     */
    public function setDeveloperKey($developerKey) {
        $this->developerKey = $developerKey;
    }

    /**
     * Don't use HTTPS for API calls
     */
    public function dontUseHttps() {
        $this->apiUrl = str_replace('https', 'http', $this->apiUrl);
    }

    /**
     * Generate and append the request signature to the request URL
     *
     * @param  string $requestUrl
     * @return string $signature
     *
     */
    private function _generateSignature($requestUrl) {
        $signature = strtoupper(hash_hmac('sha1', $requestUrl, $this->developerKey));
        return $signature;
    }

    /**
     * Generate the full API request URL
     *
     * @param  string $endpointKey
     * @param  array $urlParams
     * @param  array $queryParams
     * @return string $endpointUrl
     *
     */
    private function _generateEndpointUrl($endpointKey, $urlParams=[], $queryParams=[]) {

        // check if dev id and key set
        if(! $this->developerId)  throw new \Exception('PTV developer ID not set.');
        if(! $this->developerKey)  throw new \Exception('PTV developer key not set.');

        // get the endpoint URL
        $endpointUrl = $this->endpoint($endpointKey);

        // replace inline url parameters
        $endpointUrl = strtr($endpointUrl, $urlParams);

        // generate query parameters
        $queryParamsString = ['devid=' . $this->developerId];
        foreach($queryParams as $paramKey => $paramValue) {
            if(is_array($paramValue)) {
                foreach($paramValue as $val) $queryParamsString[] = $paramKey . '=' . urlencode($val);
            } else {
                $queryParamsString[] = $paramKey . '=' . urlencode($paramValue);
            }
        }

        // append query parameters
        $endpointUrl .= '?' . implode('&', $queryParamsString);

        // generate signature
        $signature = $this->_generateSignature($endpointUrl);

        // append signature
        $endpointUrl .= '&signature=' . $signature;

        // prepend base URL
        $endpointUrl = $this->apiUrl . $endpointUrl;

        return $endpointUrl;
    }

    /**
     * Make CURL request to API with given URL
     *
     * @param  string $apiUrl
     * @return object $response
     *
     */
    public function call($apiUrl) {

        mb_internal_encoding("UTF-8");

        $callStart = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $curlResponse = curl_exec($ch);

        if($curlResponse) {

            $responseHeaders = [];

            // split header and body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaderRaw = trim(substr($curlResponse, 0, $headerSize));
            $responseBody = substr($curlResponse, $headerSize);

            // extract response header to array
            foreach (explode("\r\n", $responseHeaderRaw) as $i => $headerLine) {
                if ($i === 0) {
                    $responseHeaders['HTTP'] = $headerLine;
                } else {
                    $headerLine = explode(': ', $headerLine);
                    $responseHeaders[$headerLine[0]] = $headerLine[1];
                }
            }

            // decode json response
            $response = json_decode($responseBody);

            // append API URL
            $response->url = $apiUrl;

            // append execution time
            $response->execution = microtime(true) - $callStart;

            // get the current time provided by API, or use server time (server should be Australia\Melbourne timezone)
            if(isset($responseHeaders['Date'])) {

                // convert to UCT
                $apiTime = new \DateTime($responseHeaders['Date']);
                $apiTime->setTimeZone(new \DateTimeZone('UTC'));
                $utcTime = $apiTime->format('Y-m-d\TH:i:s\Z'); // UTC with Zulu

            } else {

                // use server time
                $serverTime = new \DateTime();
                $serverTime->setTimeZone(new \DateTimeZone('UTC'));
                $utcTime = $serverTime->format('Y-m-d\TH:i:s\Z'); // UTC with Zulu

            }

            // append PTV server time (UTC)
            $response->time = $utcTime;

        }

        // close connection
        curl_close($ch);

        return $response;
    }

    /**
     * Get the specified endpoint by key
     *
     * @param  string $endpointKey
     * @return string $endpoint
     *
     */
    public function endpoint($endpointKey) {

        // check if endpoint is defined
        if(! array_key_exists($endpointKey, $this->apiEndpoints)) throw new \Exception('Invalid endpoint specified.');

        // get the endpoint
        $endpoint = $this->apiEndpoints[$endpointKey];

        return $endpoint;
    }

    /**
     * Return the generated URL
     *
     * @return object
     *
     */
    public function get() {
        return $this->call($this->url);
    }

    /**
     * Return the generated URL
     *
     * @return string $this->url
     *
     */
    public function url() {
        return $this->url;
    }

    /**
     * API Wrapper Methods
     *
     * @todo add documentation for each method
     *
     */

    public function departures($routeType, $stopId, $parameters=[]) {

        // generate url
        $this->url = $this->_generateEndpointUrl('departures', ['{route_type}' => $routeType, '{stop_id}' => $stopId], $parameters);

        return $this;
    }

    public function departuresRoute($routeType, $stopId, $routeId, $parameters=[]) {

        // generate url
        $this->url = $this->_generateEndpointUrl('departuresRoute', ['{route_type}' => $routeType, '{stop_id}' => $stopId, '{route_id}' => $routeId], $parameters);

        return $this;
    }

    public function directionsByRoute($routeId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('directionsByRoute', ['{route_id}' => $routeId]);

        return $this;
    }

    public function directionRoutes($directionId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('directionRoutes', ['{direction_id}' => $directionId]);

        return $this;
    }

    public function directionRoutesByType($directionId, $routeType) {

        // generate url
        $this->url = $this->_generateEndpointUrl('directionRoutesByType', ['{direction_id}' => $directionId, '{route_type}' => $routeType]);

        return $this;
    }

    public function disruptions($routeTypes=false, $disruptionStatus=false, $parameters=[]) {

        if($routeTypes) $parameters['route_types'] = $routeTypes;
        if($disruptionStatus) $parameters['disruption_status'] = $disruptionStatus;

        // generate url
        $this->url = $this->_generateEndpointUrl('disruptions', [], $parameters);

        return $this;
    }

    public function disruptionsByRoute($routeId, $disruptionStatus=false, $parameters=[]) {

        if($disruptionStatus) $parameters['disruption_status'] = $disruptionStatus;

        // generate url
        $this->url = $this->_generateEndpointUrl('disruptionsByRoute', ['{route_id}' => $routeId], $parameters);

        return $this;
    }

    public function disruption($disruptionId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('disruption', ['{disruption_id}' => $disruptionId]);

        return $this;
    }

    public function patterns($runId, $routeType) {

        // generate url
        $this->url = $this->_generateEndpointUrl('patterns', ['{run_id}' => $runId, '{route_type}' => $routeType]);

        return $this;
    }

    public function route($routeId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('route', ['{route_id}' => $routeId]);

        return $this;
    }

    public function routes($routeTypes=false, $routeName=false, $parameters=[]) {

        if($routeTypes) $parameters['route_types'] = $routeTypes;
        if($routeName) $parameters['route_name'] = $routeName;

        // generate url
        $this->url = $this->_generateEndpointUrl('routes', [], $parameters);

        return $this;
    }

    public function routeTypes() {

        // generate url
        $this->url = $this->_generateEndpointUrl('routeTypes');

        return $this;
    }

    public function runs($routeId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('runs', ['{route_id}' => $routeId]);

        return $this;
    }

    public function run($runId) {

        // generate url
        $this->url = $this->_generateEndpointUrl('run', ['{run_id}' => $runId]);

        return $this;
    }

    public function runByType($runId, $routeType) {

        // generate url
        $this->url = $this->_generateEndpointUrl('runByType', ['{run_id}' => $runId, '{route_type}' => $routeType]);

        return $this;
    }

    public function search($searchTerm, $parameters=[]) {

        // generate url
        $this->url = $this->_generateEndpointUrl('search', ['{search_term}' => $searchTerm], $parameters);

        return $this;
    }

    public function stopFacilities($stopId, $routeType, $parameters=[]) {

        // generate url
        $this->url = $this->_generateEndpointUrl('stopFacilities', ['{stop_id}' => $stopId, '{route_type}' => $routeType], $parameters);

        return $this;
    }

    public function stopsByRoute($routeId, $routeType) {

        // generate url
        $this->url = $this->_generateEndpointUrl('stopsByRoute', ['{route_id}' => $routeId, '{route_type}' => $routeType]);

        return $this;
    }

    public function stopsNear($latitude, $longitude, $parameters=[]) {

        // generate url
        $this->url = $this->_generateEndpointUrl('stopsNear', ['{latitude}' => $latitude, '{longitude}' => $longitude], $parameters);

        return $this;
    }

}

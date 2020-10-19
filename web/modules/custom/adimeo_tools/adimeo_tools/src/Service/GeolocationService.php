<?php

namespace Drupal\adimeo_tools\Service;

use \Drupal\Core\Database\Query\SelectInterface;

/**
 * Class GeolocationService.
 *
 * Allows you to ask Google geocode API to get geolocation coordonnates using
 * address components.
 *
 */
class GeolocationService {

  /**
   * Service name.
   */
  const SERVICE_NAME = 'adimeo_tools.geolocation';

  /**
   * Google API KEY global var name.
   *
   * Allows you to define a Global Google Api Key for you project :
   * ex: define(GeolocationService::GOOGLE_API_KEY_VAR_NAME , '_your_api_key_').
   */
  const GOOGLE_API_KEY_VAR_NAME = 'GOOGLE_API_KEY';

  /**
   * Google OVER_QUERY_LIMIT.
   */
  const GOOGLE_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';

  /**
   * Google geo code api url.
   */
  const GOOGLE_SERVICE = 'https://maps.googleapis.com/maps/api/geocode/json';

  /**
   * Max request per second defined for basic google account.
   */
  const MAX_REQUEST_PER_SECOND = 50;

  /**
   * Make a sleep if the last request to google service was made less than 1sec.
   */
  const REQUEST_TIME_DELTA = 1;

  /**
   * CURL time out.
   */
  const CURL_TIME_OUT = 2;

  /**
   * Sleep time to avoid google api max request achievment.
   */
  const SLEEP_TIME = 1;

  /**
   * Google latitude selector in response.
   */
  const LATITUDE = 'lat';

  /**
   * Google Longitude selector in response.
   */
  const LONGITUDE = 'lng';

  /**
   * Distance column name for nearest query.
   */
  const QUERY_FIELD_DISTANCE = 'distance';

  /**
   * Max zip code depth error.
   */
  const MAX_ZIP_CODE_DEPTH_SEARCH = 10;

  /**
   * TRUE if google over query limit is attempted.
   *
   * @var bool
   */
  static protected $overQueryLimitAttempted = FALSE;

  /**
   * Local Api Key.
   *
   * @var string
   */
  protected $apiKey = NULL;

  /**
   * Request count.
   *
   * @var int
   */
  protected $requestCount = 0;

  /**
   * Last request date.
   *
   * @var int
   */
  protected $lastRequestDate = 0;

  /**
   * True if the account has request limits (by default).
   *
   * @var bool
   */
  protected $hasRequestLimits = TRUE;

  /**
   * ZIp code depth.
   *
   * @var int
   */
  protected $zipCodeSearch;

  /**
   * Max Zip Code Depth Search.
   *
   * @var int
   */
  protected $maxZipCodeDepthSearch = self::MAX_ZIP_CODE_DEPTH_SEARCH;

  /**
   * Number of API calls.
   *
   * @var int
   */
  static protected $apiCallsNb = 0;

  /**
   * Retourne le singleton (quand pas d'injection de dépendances possible)
   *
   * @return static
   *   Le singleton.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * Set the google api Key.
   *
   * @param string $apiKey
   *   Google api Key.
   */
  public function setGoogleApiKey($apiKey) {
    $this->apiKey = $apiKey;
  }

  /**
   * Return the google Api Key.
   *
   * Local if exists, else global if exists , else emtpy string.
   *
   * @return string
   *   The used Google api Key.
   */
  public function getGoogleApiKey() {
    if (isset($this->apiKey)) {
      return $this->apiKey;
    }
    elseif (defined(self::GOOGLE_API_KEY_VAR_NAME)) {
      return constant(self::GOOGLE_API_KEY_VAR_NAME);
    }
    return '';
  }

  /**
   * Get the Latitude and Longitude array from search components.
   *
   * @param string $address
   *   The searched address.
   * @param string $country
   *   The country filter.
   * @param string $postal_code
   *   The postal code filter.
   * @param string $locality
   *   The locality filter.
   * @param string $administrative_area
   *   The adminstrative area filter.
   * @param string $route
   *   The route filter.
   *
   * @return array|bool
   *   The latitude|longitude array or FALSE if error
   */
  public function getLatLngFromSearchComponents($address = NULL, $country = NULL, $postal_code = NULL, $locality = NULL, $administrative_area = NULL, $route = NULL) {
    if ($result = $this->getGeolocationData($address, $country, $postal_code, $locality, $administrative_area, $route)) {
      return $this->getLatLngFromGoogleResponse($result);
    }
    return FALSE;
  }

  /**
   * Get the google response array from search components.
   *
   * @param string $address
   *   The searched address.
   * @param string $country
   *   The country filter.
   * @param string $postal_code
   *   The postal code filter.
   * @param string $locality
   *   The locality filter.
   * @param string $administrative_area
   *   The adminstrative area filter.
   * @param string $route
   *   The route filter.
   *
   * @return array|bool
   *   The google response array or FALSE if error
   */
  public function getGeolocationData($address = NULL, $country = NULL, $postal_code = NULL, $locality = NULL, $administrative_area = NULL, $route = NULL) {

    if (static::$overQueryLimitAttempted) {
      $this->overQueryLimitAlert();
    }

    // Avoid the max of 50 requests per second.
    $this->sleep();

    // Init the url.
    $curl = curl_init();
    $url = $this->getGeolocationUrl($address, $country, $postal_code, $locality, $administrative_area, $route);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_TIMEOUT, self::CURL_TIME_OUT);
    $result = curl_exec($curl);
    curl_close($curl);

    static::$apiCallsNb += 1;

    if ($result) {
      $result = json_decode($result, TRUE);

      // Throw overQuery limit :
      if ($result['status'] == static::GOOGLE_OVER_QUERY_LIMIT) {
        $this->overQueryLimitAlert();
      }

      // Throw error message.
      if (array_key_exists('error_message', $result)) {
        throw  new \Exception($result['error_message']);
      }

      // Système de fallback en cas de lacune de Google geocoding.
      // Pourrait être résolu en utilisant l'autocompletion "places".
      $reset_postal_code = FALSE;
      if (isset($postal_code)) {
        if ($this->zipCodeSearch < $this->getMaxZipCodeDepthSearch() && $this->isNotLocality($result)) {
          $this->zipCodeSearch++;
          return $this->getGeolocationData($address, $country, $postal_code + 1, $locality, $administrative_area, $route);
        }
        // On peut afficher des résultats qui ne sont pas exactement une localité (ex: 47000).
        // Ca compense certaines lacunes du geocoding de Google qui ne connait pas tous les codes postaux...
        elseif ($this->zipCodeSearch - $this->getMaxZipCodeDepthSearch() < $this->getMaxZipCodeDepthSearch() && $result['status'] == 'ZERO_RESULTS') {
          if (!$reset_postal_code) {
            $postal_code -= $this->getMaxZipCodeDepthSearch() + 1;
          }
          $this->zipCodeSearch++;
          return $this->getGeolocationData($address, $country, $postal_code + 1, $locality, $administrative_area, $route);
        }
      }
      $this->zipCodeSearch = 0;

      return $result;
    }

    return FALSE;
  }

  /**
   * Throws error when over query limit is attempted.
   *
   * @throws \Exception
   */
  protected function overQueryLimitAlert() {
    static::$overQueryLimitAttempted = TRUE;
    throw new \Exception('You have exceeded your daily request quota for this API');
  }

  /**
   * Get the google response array from search components.
   *
   * @param string $address
   *   The searched address.
   * @param string $country
   *   The country filter.
   * @param string $postal_code
   *   The postal code filter.
   * @param string $locality
   *   The locality filter.
   * @param string $administrative_area
   *   The adminstrative area filter.
   * @param string $route
   *   The route filter.
   *
   * @return string
   *   The google api url to request.
   */
  public function getGeolocationUrl($address = NULL, $country = NULL, $postal_code = NULL, $locality = NULL, $administrative_area = NULL, $route = NULL) {

    // Searched address param.
    if (isset($address)) {
      $params['address'] = urlencode($address);
    }

    // Searched filters with components.
    $components = [];
    if (isset($country)) {
      $components[] = 'country:' . urlencode($country);
    }
    if (isset($postal_code)) {
      $components[] = 'postal_code:' . urlencode($postal_code);
    }
    if (isset($locality)) {
      $components[] = 'locality:' . urlencode($locality);
    }
    if (isset($administrative_area)) {
      $components[] = 'administrative_area:' . urlencode($administrative_area);
    }
    if (isset($route)) {
      $components[] = 'route:' . urlencode($route);
    }

    if (!empty($components)) {
      $params['components'] = implode('|', $components);
    }

    // Api key.
    $params['key'] = $this->getGoogleApiKey();

    return self::GOOGLE_SERVICE . '?' . http_build_query($params);
  }

  /**
   * Return true if the result is not a locality.
   *
   * @param array $result
   *   Google answer.
   *
   * @return bool
   *   True if the google answer.
   */
  protected function isNotLocality(array $result) {
    if ($result['status'] == 'ZERO_RESULTS') {
      return TRUE;
    }

    foreach ($result['results'][0]['address_components'] as $component) {
      if (in_array('locality', $component['types'])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get the latitude and longitude array from google response array.
   *
   * @param array $googleResponse
   *   Google response array.
   *
   * @return mixed
   *   The Latitude|Longitude array.
   */
  public function getLatLngFromGoogleResponse(array $googleResponse) {
    return $googleResponse['results'][0]['geometry']['location'];
  }

  /**
   * Avoid the max requests per second.
   *
   * Make a pause if
   *  the last request was made less than a second
   *  and the max requests count per second is acheived
   *  and the google account is not a billing one.
   */
  protected function sleep() {
    if (!$this->hasRequestLimits()) {
      return;
    }

    $this->requestCount++;
    $time = time();
    if ($this->requestCount >= self::MAX_REQUEST_PER_SECOND && $time - $this->lastRequestDate < self::REQUEST_TIME_DELTA) {
      sleep(self::SLEEP_TIME);
      $this->requestCount = 0;
    }
    // Update last request date.
    $this->lastRequestDate = $time;
  }

  /**
   * Set the google account status for requests limits.
   *
   * @param bool $value
   *   The status of the google account used for requests.
   */
  public function setRequestLimits($value) {
    $this->hasRequestLimits = $value;
  }

  /**
   * Return true if the used google account has request limits per second.
   *
   * @return bool
   *   The status of the google account used for requests.
   */
  public function hasRequestLimits() {
    return $this->hasRequestLimits;
  }

  /**
   * Prepare a query adding a custom expression with distance between to points.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to alter.
   * @param float $lat
   *   The referent point latitude.
   * @param float $lng
   *   The referent point longitude.
   * @param string $fieldLat
   *   The latitude column for distance with the referent point.
   * @param string $fieldLng
   *   The longitude column for distance with the referent point.
   */
  public function prepareNearestQuery(SelectInterface $query, $lat, $lng, $fieldLat = self::LATITUDE, $fieldLng = self::LONGITUDE) {
    $expression = '(((acos(sin((' . $lat . '*pi()/180)) *
          sin((`' . $fieldLat . '`*pi()/180))+cos((' . $lat . '*pi()/180)) *
          cos((`' . $fieldLat . '`*pi()/180)) * cos(((' . $lng . '-
              `' . $fieldLng . '`)*pi()/180))))*180/pi())*60*1.1515*1.609344)';

    $query->addExpression($expression, self::QUERY_FIELD_DISTANCE);
  }

  /**
   * Get the max zip code depth search.
   *
   * @return int
   *   The max zip code depth search.
   */
  public function getMaxZipCodeDepthSearch() {
    return $this->maxZipCodeDepthSearch;
  }

  /**
   * Set the max zip code depth search.
   *
   * @param int $max
   *   The max depth to search.
   */
  public function setMaxZipCodeDepthSearch($max) {
    $this->maxZipCodeDepthSearch = $max > 0 ? $max : 1;
  }

  /**
   * Return the number of api calls.
   *
   * @return int
   *   THe number of api calls
   */
  public function getApiCallsNumber() {
    return static::$apiCallsNb;
  }

}

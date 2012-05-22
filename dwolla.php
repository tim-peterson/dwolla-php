<?php
/**
 * Dwolla REST API Library for PHP
 *
 * @description Dwolla - PHP Client API Library
 * @copyright   Copyright (c) 2012 Dwolla Inc. (http://www.dwolla.com)
 * @autor       Michael Schonfeld <michael@dwolla.com>
 * @version     1.0.0
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define ("API_SERVER", "https://www.dwolla.com/oauth/rest/");

if (!function_exists('curl_init'))  throw new Exception("Dwolla's API Client Library requires the CURL PHP extension.");
if (!function_exists('json_decode')) throw new Exception("Dwolla's API Client Library requires the JSON PHP extension.");

class DwollaRestClient {
    private $apiKey;
    private $apiSecret;
    private $oauthToken;

    private $permissions;
    private $redirectUri;

    private $errorMessage = FALSE; // Store any error messages we get from Dwolla

    public function __construct($apiKey = FALSE,
                                $apiSecret = FALSE,
                                $redirectUri = FALSE,
                                $permissions = array("send", "transactions", "balance", "request", "contacts", "accountinfofull"))
    {
        $this->apiKey       = $apiKey;
        $this->apiSecret    = $apiSecret;
        $this->redirectUri  = $redirectUri;
        $this->permissions  = $permissions;
        $this->apiServerUrl = API_SERVER;
    }

    // ***********************
    // Authentication Methods
    // ***********************
    public function getAuthUrl()
    {
        $params = array(
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => implode('|', $this->permissions)
        );
        $url = 'https://www.dwolla.com/oauth/v2/authenticate?' . http_build_query($params);

        return $url;
    }

    public function requestToken($code)
    {
        if(!$code) { return $this->_setError('Please pass an oauth code.'); }

        $params = array(
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
            'code'          => $code
        );
        $url = 'https://www.dwolla.com/oauth/v2/token?'  . http_build_query($params);
        $response = json_decode($this->_curl($url, 'GET'), TRUE);

        if($response['error'])
        {
            $this->errorMessage = $response['error_description'];
            return FALSE;
        }

        return $response['access_token'];
    }

    public function setToken($token) {
        if(!$token) { return $this->_setError('Please pass a token string.'); }

        $this->oauthToken = $token;

        return TRUE;
    }

    public function getToken() {
        return $this->oauthToken;
    }

    // ******************
    // Users Methods
    // ******************
    public function me()
    {
        $response = $this->_get('users');

        $me = $this->_parse($response);

        return $me;
    }

    public function getUser($user_id = FALSE)
    {
        if(!$user_id) { return $this->_setError('Please pass a user ID.'); }

        $params = array(
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret
        );
        $response = $this->_get("users/{$user_id}", $params);

        $user = $this->_parse($response);

        return $user;
    }

    // *********************
    // Register Methods
    // *********************
    public function register($user_id = FALSE)
    {
        if(!$user_id) { return $this->_setError('Please pass a user ID.'); }

        $params = array(
            'client_id'     => $this->apiKey,
            'client_secret' => $this->apiSecret
        );
        $response = $this->_post('register', $params);

        $me = $this->_parse($response);

        return $me;
    }

    // *********************
    // Contacts Methods
    // *********************
    public function contacts($search = FALSE, $types = array('Dwolla'), $limit = 10)
    {
        $params = array(
            'search'    => $search,
            'types'     => implode(',', $types),
            'limit'     => $limit
        );
        $response = $this->_get('contacts', $params);

        $contacts = $this->_parse($response);

        return $contacts;
    }

    public function nearbyContacts($search = FALSE, $types = array('Dwolla'), $limit = 10)
    {
        $params = array(
            'search'    => $search,
            'types'     => implode(',', $types),
            'limit'     => $limit
        );
        $response = $this->_get('contacts', $params);

        $contacts = $this->_parse($response);

        return $contacts;
    }

    // *********************
    // Balance Methods
    // *********************
    public function balance()
    {
        $response = $this->_get('balance');

        $balance = $this->_parse($response);

        return $balance;
    }

    // *********************
    // Transactions Methods
    // *********************
    public function send(   $pin = FALSE,
                            $destinationId = FALSE,
                            $amount = FALSE,
                            $notes = '',
                            $destinationType = 'Dwolla',
                            $facilitatorAmount = 0,
                            $assumeCosts = TRUE
                        )
    {
        // Verify required paramteres
        if(!$pin) { return $this->_setError('Please enter a PIN.'); }
        else if(!$destinationId) { return $this->_setError('Please enter a destination ID.'); }
        else if(!$amount) { return $this->_setError('Please enter a transaction amount.'); }

        // Build request, and send it to Dwolla
        $params = array(
            'pin'               => $pin,
            'destinationId'     => $destinationId,
            'destinationType'   => $destinationType,
            'amount'            => $amount,
            'facilitatorAmount' => $facilitatorAmount,
            'assumeCosts'       => $assumeCosts,
            'notes'             => $notes
        );
        $response = $this->_post('transactions/send', $params);

        // Parse Dwolla's response
        $transactionId = $this->_parse($response);

        return $transactionId;
    }

    public function request($pin = FALSE,
                            $sourceId = FALSE,
                            $sourceType = 'Dwolla',
                            $amount = FALSE,
                            $facilitatorAmount = 0,
                            $notes = '')
    {
        // Verify required paramteres
        if(!$pin) { return $this->_setError('Please enter a PIN.'); }
        else if(!$sourceId) { return $this->_setError('Please enter a source ID.'); }
        else if(!$amount) { return $this->_setError('Please enter a transaction amount.'); }

        // Build request, and send it to Dwolla
        $params = array(
            'pin'               => $pin,
            'sourceId'          => $destinationId,
            'sourceType'        => $destinationType,
            'amount'            => $amount,
            'facilitatorAmount' => $facilitatorAmount,
            'notes'             => $notes
        );
        $response = $this->_post('transactions/request', $params);

        // Parse Dwolla's response
        $transactionId = $this->_parse($response);

        return $transactionId;
    }

    public function transaction($transactionId)
    {
        // Verify required paramteres
        if(!$transactionId) { return $this->_setError('Please enter a transaction ID.'); }

        // Build request, and send it to Dwolla
        $response = $this->_get("transactions/{$transactionId}");

        // Parse Dwolla's response
        $transaction = $this->_parse($response);

        return $transaction;
    }

    public function listings(   $sinceDate = FALSE,
                                $types = array('money_sent', 'money_received', 'deposit', 'withdrawal', 'fee'),
                                $limit = 10,
                                $skip = FALSE)
    {
        $params = array(
            'sinceDate' => $sinceData,
            'types'     => implode('|', $types),
            'limit'     => $limit,
            'skip'      => $skit
        );

        // Build request, and send it to Dwolla
        $response = $this->_get("transactions", $params);

        // Parse Dwolla's response
        $listings = $this->_parse($response);

        return $listings;
    }

    public function stats(  $types = array('TransactionsCount', 'TransactionsTotal'),
                            $sinceDate = FALSE,
                            $endDate = FALSE)
    {
        $params = array(
            'sinceDate' => $sinceData,
            'types'     => implode(',', $types),
            'limit'     => $limit,
            'skip'      => $skit
        );

        // Build request, and send it to Dwolla
        $response = $this->_get("transactions/stats", $params);

        // Parse Dwolla's response
        $stats = $this->_parse($response);

        return $stats;
    }

    // ***************
    // Public methods
    // ***************
    public function getError()
    {
        if(!$this->errorMessage) { return FALSE; }

        $error = $this->errorMessage;
        $this->errorMessage = FALSE;

        return $error;
    }

    // ********************
    // Private methods
    // ********************
    protected function _setError($message)
    {
        $this->errorMessage = $message;
        return FALSE;
    }

    protected function _parse($response)
    {
        if(!$response['Success'])
        {
            $this->errorMessage = $response['Message'];
            return FALSE;
        }

        return $response['Response'];
    }

    protected function _post($request, $params = FALSE)
    {
        $params['oauth_token'] = $this->oauthToken;
        $url = $this->apiServerUrl . $request . "?" . http_build_query($params);

        $rawData = $this->_curl($url, 'POST', $params);

        return json_decode($rawData, TRUE);
    }

    protected function _get($request, $params = array())
    {
        $params['oauth_token'] = $this->oauthToken;
        $url = $this->apiServerUrl . $request . "?" . http_build_query($params);

        $rawData = $this->_curl($url, 'GET');

        return json_decode($rawData, TRUE);
    }

    protected function _curl($url, $method = 'GET', $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json'));

        $rawData = curl_exec($ch);
        curl_close($ch);

        return $rawData;
    }
}
?>
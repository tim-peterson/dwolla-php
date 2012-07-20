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
            'response_type' => 'code',
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
        $response = $this->_curl($url, 'GET');

        if(isset($response['error']))
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
    
    /**
     * Grabs the account information for the
     * authenticated user
     *
     * @param  {}
     * @return {array} Account information
     */
    public function me()
    {
        $response = $this->_get('users');

        $me = $this->_parse($response);

        return $me;
    }

    /**
     * Grabsc the basic account information for
     * the given Dwollaaccount ID
     *
     * @param {string/int} Dwolla Account ID
     * @return {array} Basic account information
     */
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
    
    /**
     * Send money from the authenticated account
     * to another online account
     *
     * @param {string} The sending account's PIN
     * @param {string} The destination account ID; Can be Dwolla ID, email, phone, Twitter ID, or Facebook ID
     * @param {string} Amount to be sent
     * @param {string} The destination ID type; Can be 'Dwolla', 'Email', 'Phone', 'Twitter', or 'Facebook'
     * @param {string} Notes to be associated with this transaction
     * @param {int} Facilitator fee amount to be added
     * @param {boolean} Does the sending user assume any and all transaction costs?
     * @return {int} The transaction ID, or {boolean:FALSE} when an error occurs
     */
    public function send(   $pin = FALSE,
                            $destinationId = FALSE,
                            $amount = FALSE,
                            $destinationType = 'Dwolla',
                            $notes = '',
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

    /**
     * Send a 'Request money' from the authenticated
     * account to another online account
     *
     * @param {string} The requesting account's PIN
     * @param {string} The destination account ID; Can be Dwolla ID, email, phone, Twitter ID, or Facebook ID
     * @param {string} Amount to be sent
     * @param {string} The destination ID type; Can be 'Dwolla', 'Email', 'Phone', 'Twitter', or 'Facebook'
     * @param {string} Notes to be associated with this transaction
     * @param {int} Facilitator fee amount to be added
     * @return {int} The request transaction ID, or {boolean:FALSE} when an error occurs
     */
    public function request($pin = FALSE,
                            $sourceId = FALSE,
                            $amount = FALSE,
                            $sourceType = 'Dwolla',
                            $notes = '',
                            $facilitatorAmount = 0)
    {
        // Verify required paramteres
        if(!$pin) { return $this->_setError('Please enter a PIN.'); }
        else if(!$sourceId) { return $this->_setError('Please enter a source ID.'); }
        else if(!$amount) { return $this->_setError('Please enter a transaction amount.'); }

        // Build request, and send it to Dwolla
        $params = array(
            'pin'               => $pin,
            'sourceId'          => $sourceId,
            'sourceType'        => $sourceType,
            'amount'            => $amount,
            'facilitatorAmount' => $facilitatorAmount,
            'notes'             => $notes
        );
        $response = $this->_post('transactions/request', $params);

        // Parse Dwolla's response
        $transactionId = $this->_parse($response);

        return $transactionId;
    }

    /**
     * Grab information for the given
     * transaction ID
     *
     * @param {int} Transaction ID to which information is pulled
     * @return {array} Transaction information
     */
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

    /**
     * Grabs a list of all transactions associated
     * with the authenticated account
     *
     * @param {string} 
     * @param {array} 
     * @param {int} 
     * @param {boolean} 
     * @return {array} List of transactions
     */
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

        return $rawData;
    }

    protected function _get($request, $params = array())
    {
        $params['oauth_token'] = $this->oauthToken;
        $url = $this->apiServerUrl . $request . "?" . http_build_query($params);

        $rawData = $this->_curl($url, 'GET');

        return $rawData;
    }

    protected function _curl($url, $method = 'GET', $params = array())
    {
    	// Set up our CURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json;charset=UTF-8'));
        
        // Windows require this certificate
        $ca = dirname(__FILE__);
        curl_setopt($ch, CURLOPT_CAINFO, $ca); // Set the location of the CA-bundle
        curl_setopt($ch, CURLOPT_CAINFO, $ca . '/cacert.pem'); // Set the location of the CA-bundle

        // Initiate request
        $rawData = curl_exec($ch);
        
        // If HTTP response wasn't 200,
        // log it as an error!
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($code !== 200) {
        	return array(
        		'Success' => FALSE,
        		'Message' => "Request failed. Server responded with: {$code}"
        	);
        }

        // All done with CURL
        curl_close($ch);

        // Otherwise, assume we got some
        // sort of a response
        return json_decode($rawData, TRUE);;
    }
}
?>
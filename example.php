<?php
// Include the Dwolla REST Client
require 'dwolla.php';

// Hide mah keys!
require 'keys.php';

// Instantiate a new Dwolla REST Client
$Dwolla = new DwollaRestClient($apiKey, $apiSecret);

// Use a previously generated access token
//$Dwolla->setToken('YMfx4135eeuJDNnC7ub0qMAn+ZgYIzwkl7nLUoDxO7nBd49X/6');

$token = $Dwolla->requestToken('abc');
if(!$token) {
	echo $Dwolla->getError();
}

/********************
 ** BEGIN EXAMPELS **
 ********************/
/*
// Send money
$tid = $Dwolla->send($pin, 'info@matisen.dk', 0.01, 'Email');
if(!$tid) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Send transaction ID: {$tid} \n";
echo "-------------------- \n";

// Send a money request
$tid = $Dwolla->request($pin, '812-546-3855', 0.01);
if(!$tid) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Send transaction ID: {$tid} \n";
echo "-------------------- \n";

// Get user's balance
$balance = $Dwolla->balance();
if(!$balance) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Your balance is: {$balance} \n";
echo "-------------------- \n";

// Get transaction details
$details = $Dwolla->transaction('743160');
if(!$details) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Transaction details: {$details} \n";
echo "-------------------- \n";

// Get own account info
$me = $Dwolla->me();
if(!$me) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Own account: \n";
print_r($me);
echo "-------------------- \n";

// Get user account info
$user = $Dwolla->getUser('812-546-3855');
if(!$user) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Own account: \n";
print_r($user);
echo "-------------------- \n";

// Get contacts
$contacts = $Dwolla->contacts('Ben');
if(!$contacts) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Own contacts: \n";
print_r($contacts);
echo "-------------------- \n";

// Get nearby contacts
$nearbyContacts = $Dwolla->nearbyContacts('Ben');
if(!$nearbyContacts) { echo "Error: {$Dwolla->getError()} \n"; }
echo "Nearby contacts: \n";
print_r($nearbyContacts);
echo "-------------------- \n";

*/

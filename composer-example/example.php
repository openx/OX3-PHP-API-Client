<?php

// init autoloader
require_once('vendor/autoload.php');

$uri      = getenv('OPENX_URI');
$email    = getenv('OPENX_EMAIL');
$password = getenv('OPENX_PASSWORD');
$key      = getenv('OPENX_KEY');
$secret   = getenv('OPENX_SECRET');
$realm    = getenv('OPENX_REALM')?:'';

// grab a client
$client = new \OpenX\PlatformAPI\OXApiClient($uri, $email, $password, $key, $secret, $realm);

// and make a request
$response = $client->get('/session');
$raw_body = $response->getBody();
$session = json_decode($raw_body);
print_r($session->user); // logs the correct user

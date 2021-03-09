<?php
require_once __DIR__ ."/../vendor/autoload.php";

use OpenX\PlatformAPI\OX3ApiClient2;

$uri      = getenv('OPENX_URI');
$email    = getenv('OPENX_EMAIL');
$password = getenv('OPENX_PASSWORD');
$key      = getenv('OPENX_KEY');
$secret   = getenv('OPENX_SECRET');
$realm    = getenv('OPENX_REALM')?:'';

$client = new OX3ApiClient2($uri, $email, $password, $key, $secret, $realm);

// list all site
$result = $client->get('/site');


if ($result->getStatusCode() == 200) {
    echo "Response: " . print_r(json_decode($result->getBody(), true), true) . "\n";
} else {;
    echo "Non-200 response:\ncode: " . $result->getStatus() . "\nmessage: " . $result->getMessage() . "\nbody: " . $result->getBody() . "\n";
}


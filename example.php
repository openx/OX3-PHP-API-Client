<?php

require_once 'OX3_Api_Client.php';

$uri      = 'http://localhost';
$email    = 'root@openx.org';
$password = '';
$key      = '';
$secret   = '';
$realm    = '';
$client = new OX3_API_Client($uri, $email, $password, $key, $secret, $realm);

// Add a user
//$result = $client->post('/a/user/', array('first_name' => 'Chris', 'last_name' => 'Nutting', 'account_id' => 7, 'email' => uniqid('chris.nutting+') . '@openx.org', 'password' => md5('test'), 'status' => 'Active'));

// Get a user
$result = $client->get('/a/user/1');


if ($result->getStatus() == 200) {
    echo "Response: " . print_r(json_decode($result->getBody(), true), true) . "\n";
} else {;
    echo "Non-200 response:\ncode: " . $result->getStatus() . "\nmessage: " . $result->getMessage() . "\nbody: " . $result->getBody() . "\n";
}

?>

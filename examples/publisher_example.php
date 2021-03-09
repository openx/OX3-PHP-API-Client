<?php

/* 
 * This file includes specific examples of how to use the OpenX API to 
 * create a publisher account and objects associated with a publisher.
 */

// change to 'OX3_Api_Client2.php' if using Zend 2
require_once 'OX3_Api_Client.php';

/* TO BE READ BY USER --------------------------------------------------------------------------

Change $master_uid to equal the uid of the master/network account that will contain the new
publisher account.*/

$master_uid = '6008e0d5-accf-fff1-8123-0c9a66';
/*------------------------------------------------------------------------------------------------*/

$uri	  = 'http://localhost';
$email    = 'root@openx.org';
$password = '';
$key	  = '';
$secret   = '';
$realm    = '';

// change to 'OX3_Api_Client2(...) if using Zend 2
$client = new OX3_API_Client($uri, $email, $password, $key, $secret, $realm);

/*----------------------------------------------------------------------------------------------*/

// Create a publisher account (The name of the account must be unique)
// account uid is uid of master/network account
$pub_acct_query = array(
	'account_uid'=>$master_uid, 
	'currency'=>'USD', 
	'experience'=>'publisher', 
	'name'=>'PHP Publisher', 
	'status'=>'Active', 
	'type_full'=>'account.publisher', 
	'timezone'=>'America/Los_Angeles'
	);
$pub_acct = json_decode($client->post('/account/', $pub_acct_query)->getBody(), true);
// store the account uid of the publisher account just created
$pub_acct_uid = $pub_acct['0']['uid'];

/*----------------------------------------------------------------------------------------------*/

// Create a site for a given publisher account
$site_query = array(
	'account_uid'=>$pub_acct_uid, 
	'name'=>'PhpClient test', 
	'status'=>'Active', 
	'url'=>'https://sites.google.com/site/jagritiphpclient/'
	);
$site = json_decode($client->post('/site/', $site_query)->getBody(), true);
$site_uid = $site['0']['uid'];

/*----------------------------------------------------------------------------------------------*/

// Create a sitesection
$sitesection_query = array(
	'name'=>'Turner',
	'status'=>'Active', 
	'site_uid'=>$site_uid
	);
$sitesection = json_decode($client->post('/sitesection/', $sitesection_query)->getBody(), true);
$sitesection_uid = $sitesection['0']['uid'];

/*-------------------------------------------------------------------------------------------------*/
// Create an adunit for a given site
// content_topics, primary_size, sitesection_uid are optional fields
$adunit1_query = array(
	'site_uid'=>$site_uid, 
	'name'=>'Turner_Rain_Steam_Speed', 
	'status'=>'Active', 
	'delivery_medium_id'=>'2', 
	'tag_type_id'=>'5', 
	'type_full'=>'adunit.web', 
	'content_topics'=>array(
		'2302'=>'1'), 
	'primary_size'=>'300x250',
	'sitesection_uid'=>$sitesection_uid
	);
$adunit1 = json_decode($client->post('/adunit/', $adunit1_query)->getBody(), true);
$adunit1_uid = $adunit1['0']['uid'];

// create another adunit (need at least two adunits of the same type to create 
// an adunitgorup)
$adunit2_query = array(
	'site_uid'=>$site_uid, 
	'name'=>'Turner_Storm', 
	'status'=>'Active', 
	'delivery_medium_id'=>'2', 
	'tag_type_id'=>'5', 
	'type_full'=>'adunit.web', 
	'content_topics'=>array('2302'=>'1'), 
	'primary_size'=>'300x250',
	'sitesection_uid'=>$sitesection_uid
	);
$adunit2 = json_decode($client->post('/adunit/', $adunit2_query)->getBody(), true);
$adunit2_uid = $adunit2['0']['uid'];

/*----------------------------------------------------------------------------------------------*/

// Create an adunit group
$adunit_group_query = array(
	'name'=>'Turner_paintings',
	// uid of rain_storm_speed adunit
	'masteradunit_uid'=>$adunit1_uid, 
	'site_uid'=>$site_uid, 
	'status'=>'Active', 
	'delivery_medium'=>'WEB', 
	// uid of turner_storm adunit
	'adunit_uids'=>array($adunit2_uid=>'1')
	);
$adunit_group = $client->post('/adunitgroup/', $adunit_group_query);

?>

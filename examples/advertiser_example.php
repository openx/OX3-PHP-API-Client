<?php

/* 
 * This file includes specific examples of how to use the OpenX API to 
 * create an advertiser account and objects associated with an advertiser.
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

// Create an advertiser account (The name of the account must be unique)
// account uid is uid of master/network account
$adv_acct_query = array(
	'account_uid'=>$master_uid,
	'currency'=>'USD', 
	'experience'=>'advertiser', 
	'name'=>'PHP Advertiser', 
	'status'=>'Active', 
	'type_full'=>'account.advertiser', 
	'timezone'=>'America/Los_Angeles'
	);
$adv_acct = json_decode($client->post('/account/', $adv_acct_query)->getBody(), true);
// store the account uid of the advertiser account just created 
$adv_acct_uid = $adv_acct['0']['uid'];

/*----------------------------------------------------------------------------------------------*/

// Create an order for the advertiser account we just created
$order_query = array(
	'name'=>'Art', 
	'status'=>'Active', 
	'start_date'=>'2015-07-09', 
	'account_uid'=> $adv_acct_uid
	);
$order = json_decode($client->post('/order/', $order_query)->getBody(), true);

$order_uid = $order['0']['uid'];

/*----------------------------------------------------------------------------------------------*/

$lineitem_query = array(
	'type_full'=>'lineitem.non_guaranteed', 
	'ad_delivery'=>'equal', 
	'name'=>'turner_train_painting', 
	'status'=>'Active', 
	'order_uid'=>$order_uid, 
	'start_date'=>'now', 
	'delivery_medium'=>'WEB',
	'targeting'=>array(
		'content'=>array(
			'content_topic'=>array(
				'val'=>'2302', 'op'=>'EQ'))), 
	'pricing_rate'=>'1.0000', 
	'pricing_model'=>'cpm', 
	);
$lineitem = json_decode($client->post('/lineitem/', $lineitem_query)->getBody(), true);
$lineitem_uid = $lineitem['0']['uid'];

/*-----------------------------------------------------------------------------------------------*/

// Create a creative
$creative_query = array(
	'ad_type_full'=>'ad.image', 
	'name'=>'Turner_rain_steam_speed_creative', 
	'uri'=>'http://data1.whicdn.com/images/337374/superthumb.jpg', 
	'account_uid'=>$adv_acct_uid, 
	'ad_type_full'=>'ad.image', 
	'width'=>'300', 
	'height'=>'250'
	);
$creative = json_decode($client->post('/creative/', $creative_query)->getBody(), true);
$creative_uid = $creative['0']['uid'];

/*------------------------------------------------------------------------------------------------*/

//Create an ad
$ad_query = array(
	'name'=>'Turner_painting ad', 
	'status'=>'Active', 
	'start_date'=>'now', 
	'click_url'=>'https://sites.google.com/site/landingjagritisite1/',
	'primary_creative_uid'=> $creative_uid, 
	'size'=>'300x250', 
	'type_full'=>'ad.image', 
	'lineitem_uid'=>$lineitem_uid,
	);
$ad = $client->post('/ad/', $ad_query);

?>

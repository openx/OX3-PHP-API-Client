<?php
/* This file includes a script which prints/exports all current objects for an 
advertiser account specified by an account uid. */

// change to 'OX3_Api_Client2.php' if using Zend 2
require_once 'OX3_Api_Client.php';
require_once 'CSV_TXT_export_functions.php';

/*TO BE READ BY USER---------------------------------------------------------------------------------------*/

/* Change $uid to equal the account uid of the advertiser account you wish 
to view the objects of. */
$uid = '60090af2-accf-fff1-8123-0c9a66';

/* Change $CSV_OUPUT and/or $TEXT_OUTPUT to equal 1 in order to export to a csv/.txt
file, respectively. Change $CSV_FILE and/or $TEXT_FILE to equal the name of 
the file you would like to export to. Note that the file must already exist in the 
same directory as this file in order for the objets to be successfully exported */

$CSV_OUTPUT = 0;
// csv file where output would be exported 
$CSV_FILE = 'output.csv';

$TEXT_OUTPUT = 0;
// text file where output would be exported 
$TEXT_FILE = 'output.txt';
/*----------------------------------------------------------------------------------------------------------*/

$uri	  = 'http://localhost';
$email    = 'root@openx.org';
$password = '';
$key	  = '';
$secret   = '';
$realm    = '';

// change to 'OX3_Api_Client2(...) if using Zend 2
$client = new OX3_API_Client($uri, $email, $password, $key, $secret, $realm);

/*----------------------------------------------------------------------------------------------------------*/

// get all orders
$orders = json_decode($client->get('/order')->getBody(), true);
$num_orders = sizeof($orders['objects']);

// get all lineitems
$lineitems = json_decode($client->get('/lineitem')->getBody(), true);
$num_lineitems = sizeof($lineitems['objects']);

// get all ads
$ads = json_decode($client->get('/ad')->getBody(), true);
$num_ads = sizeof($ads['objects']);

// get all creatives
$creatives = json_decode($client->get('/creative')->getBody(), true);
$num_creatives = sizeof($creatives['objects']);

// get the advertiser account associated with the specified account uid
$adv_account = json_decode($client->get("/account/{$uid}")->getBody(), true)['0'];

// extract information about specified advertiser account
$account_name = $adv_account['name'];
$account_uid = $adv_account['uid'];
$account_status = $adv_account['status'];
$account_id = $adv_account['id'];

$account = array(
	'Account Name'=>$account_name, 
	'Account UID'=>$account_uid, 
	'Account Status'=>$account_status, 
	'Account ID'=>$account_id,
	'Orders'=>array(), 
	'Creatives'=>array());
	
	// loop through all orders
	for ($i = 0; $i < $num_orders; $i++) {
		$order_account_id = $orders['objects'][$i]['account_id'];
		
		// if the order belongs to this advertiser account, then add it to the account's 
		// array of orders
		if ($order_account_id == $account_id) {
			$order_name = $orders['objects'][$i]['name'];
			$order_uid = $orders['objects'][$i]['uid'];
			$order_status = $orders['objects'][$i]['status'];
			$order_start_date = $orders['objects'][$i]['start_date'];

			$order = array(
				'Order Name'=>$order_name, 
				'Order UID'=>$order_uid, 
				'Order Status'=>$order_status,
				'Order Start Date'=>$order_start_date, 
				'Lineitems'=>array());
			
			// loop through all lineitems
			for ($j = 0; $j < $num_lineitems; $j++) {
				$lineitem_order_uid = $lineitems['objects'][$j]['order_uid'];

				// if the lineitem belongs to this order, then add it to the order's
				// array of lineitems
				if ($lineitem_order_uid == $order_uid) {
					$lineitem_name = $lineitems['objects'][$j]['name'];
					$lineitem_uid = $lineitems['objects'][$j]['uid'];
					$lineitem_status = $lineitems['objects'][$j]['status'];
					$lineitem_start_date = $lineitems['objects'][$j]['start_date'];

					$lineitem = array(
						'Lineitem Name'=>$lineitem_name, 
						'Lineitem UID'=>$lineitem_uid, 
						'Lineitem Status'=>$lineitem_status, 
						'Lineitem Start Date'=>$lineitem_start_date, 
						'Ads'=>array());

					// loop thorugh all ads
					for ($k = 0; $k < $num_ads; $k++) {
						$ad_lineitem_uid = $ads['objects'][$k]['lineitem_uid'];
						// if the ad belongs to this lineitem, then add it to the 
						// lineitem's array of ads
						if ($ad_lineitem_uid == $lineitem_uid) {
							$ad_name = $ads['objects'][$k]['name'];
							$ad_uid = $ads['objects'][$k]['uid'];
							$ad_status = $ads['objects'][$k]['status'];
							$ad_type = $ads['objects'][$k]['type_full'];
							$ad_size = $ads['objects'][$k]['size'];
							$ad_start_date = $ads['objects'][$k]['start_date'];

							$ad = array(
								'Ad Name'=>$ad_name, 
								'Ad UID'=>$ad_uid, 
								'Ad Status'=>$ad_status, 
								'Ad Type'=>$ad_type, 
								'Ad Size'=>$ad_size,
								'Ad Start Date'=>$ad_start_date);

							array_push($lineitem['Ads'], $ad);
						}

					}	
					array_push($order['Lineitems'], $lineitem);
				}
			}
			array_push($account['Orders'], $order);
		}
	}

	// loop through all creatives
	for ($i = 0; $i < $num_creatives; $i++) {
		$creative_account_uid = $creatives['objects'][$i]['account_uid'];
		
		// if the creative belongs to this advertiser account, then add it to the account's 
		// array of creatives
		if ($creative_account_uid == $account_uid) {
			$creative_name = $creatives['objects'][$i]['name'];
			$creative_uid = $creatives['objects'][$i]['uid'];
			$creative_date_created = $creatives['objects'][$i]['created_date'];
			$creative_uri = $creatives['objects'][$i]['uri'];

			$creative = array(
				'Creative Name'=>$creative_name, 
				'Creative UID'=>$creative_uid, 
				'Creative Date Created'=>$creative_date_created, 
				'Creative Uri'=>$creative_uri);

			array_push($account['Creatives'], $creative);
		}
	}

print_r($account);

if ($CSV_OUTPUT == 1) {
	@csv_output($account, $CSV_FILE);
}
if ($TEXT_OUTPUT == 1) {
	text_output($account, $TEXT_FILE);
}

?>



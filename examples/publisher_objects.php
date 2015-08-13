<?php
/* This file includes a script which prints/exports all current objects for a 
publisher account specified by an account uid. */

// change to 'OX3_Api_Client2.php' if using Zend 2
require_once 'OX3_Api_Client.php';
require_once 'CSV_TXT_export_functions.php';

/*TO BE READ BY USER---------------------------------------------------------------------------------------*/

/* Change $uid to equal the account uid of the advertiser account you wish 
to view the objects of. */
$uid = '60090aeb-accf-fff1-8123-0c9a66';

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

// get all sites
$sites = json_decode($client->get('/site')->getBody(), true);
$num_sites = sizeof($sites['objects']);

// get all sitesections
$sitesections = json_decode($client->get('/sitesection')->getBody(), true);
$num_sitesections = sizeof($sitesections['objects']);

// get all adunit groups
$adunit_groups = json_decode($client->get('/adunitgroup')->getBody(), true);
$num_adunit_groups = sizeof($adunit_groups['objects']);

// get all adunits
$adunits = json_decode($client->get('/adunit')->getBody(), true);
$num_adunits = sizeof($adunits['objects']);

// get the publisher account associated with the specified account uid
$pub_account = json_decode($client->get("/account/{$uid}")->getBody(), true)['0'];

$account_name = $pub_account['name'];
$account_uid = $pub_account['uid'];
$account_status = $pub_account['status'];
$account_id = $pub_account['id'];

$account = array(
	'Account Name'=>$account_name, 
	'Account UID'=>$account_uid, 
	'Account Status'=>$account_status, 
	'Account ID'=>$account_id,
	'Sites'=>array());
	
	// loop through all sites
	for ($i = 0; $i < $num_sites; $i++) {
		$site_account_id = $sites['objects'][$i]['account_id'];

		// if the site belongs to this publisher account, then add it to the account's 
		// array of sites
		if ($site_account_id == $account_id) {
			$site_name = $sites['objects'][$i]['name'];
			$site_uid = $sites['objects'][$i]['uid'];
			$site_id = $sites['objects'][$i]['id'];
			$site_date_created = $sites['objects'][$i]['created_date'];
			$site_status = $sites['objects'][$i]['status'];
			$site_url = $sites['objects'][$i]['url'];
		

			$site = array(
				'Site Name'=>$site_name, 
				'Site UID'=>$site_uid, 
				'Site ID'=>$site_id, 
				'Site Date Created'=>$site_date_created, 
				'Site Status'=>$site_status,
				'Site URL'=>$site_url, 
				'Sitesections'=>array(), 
				'Adunit Groups'=>array(), 
				'Adunits'=>array()); 

			// loop thorugh all sitesections
			for ($j = 0; $j < $num_sitesections; $j++) {
				$sitesection_site_uid = $sitesections['objects'][$j]['site_uid'];

				// if the sitesection belongs to this site, then add it to the site's
				// array of sitesections
				if ($sitesection_site_uid == $site_uid) {
					$sitesection_name = $sitesections['objects'][$j]['name'];
					$sitesection_uid = $sitesections['objects'][$j]['uid'];
					$sitesection_status = $sitesections['objects'][$j]['status']; 
					$sitesection_date_created = $sitesections['objects'][$j]['created_date'];

					$sitesection = array(
						'Sitesection Name'=>$sitesection_name, 
						'Sitesection UID'=>$sitesection_uid, 
						'Sitesection Status'=>$sitesection_status, 
						'Sitesection Date Created'=>$sitesection_date_created);

					array_push($site['Sitesections'], $sitesection);
				}
			}

			// loop through all adunit groups
			for ($k = 0; $k < $num_adunit_groups; $k++) {
				$adunit_group_site_uid = $adunit_groups['objects'][$k]['site_uid'];

				// if the adunit group belongs to this site, then add it to the site's 
				// array of adunit groups
				if ($adunit_group_site_uid == $site_uid) {
					$adunit_group_name = $adunit_groups['objects'][$k]['name'];
					$adunit_group_uid = $adunit_groups['objects'][$k]['uid'];
					$adunit_group_status = $adunit_groups['objects'][$k]['status']; 
					$adunit_group_date_created = $adunit_groups['objects'][$k]['created_date'];

					$adunit_group = array( 
						'Adunit Group Name'=>$adunit_group_name, 
						'Adunit Group UID'=>$adunit_group_uid, 
						'Adunit Group Status'=>$adunit_group_status, 
						'Adunit Group Date Created'=>$adunit_group_date_created);

					array_push($site['Adunit Groups'], $adunit_group);
				}
			}

			// loop through all adunits
			for ($n = 0; $n < $num_adunits; $n++) {
				$adunit_site_uid = $adunits['objects'][$n]['site_uid'];

				// if the adunit belongs to this site, then add it to the site's 
				// array of adunits
				if ($adunit_site_uid == $site_uid) {
					$adunit_name = $adunits['objects'][$n]['name'];
					$adunit_uid = $adunits['objects'][$n]['uid'];
					$adunit_status = $adunits['objects'][$n]['status'];
					$adunit_primary_size = $adunits['objects'][$n]['primary_size']; 
					$adunit_date_created = $adunits['objects'][$n]['created_date'];

					$adunit = array(
						'Adunit Name'=>$adunit_name, 
						'Adunit UID'=>$adunit_uid, 
						'Adunit Status'=>$adunit_status, 
						'Adunit Primary Size'=>$adunit_primary_size, 
						'Adunit Date Created'=>$adunit_date_created);

					array_push($site['Adunits'], $adunit);
				}
			}

			array_push($account['Sites'], $site);
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

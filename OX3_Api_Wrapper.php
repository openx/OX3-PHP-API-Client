<?php

/**
 * OO wrappers around a few OX objects, transparently handles v3 and v4.
 *
 * PHP version 5
 *
 * @package    OX_API
 * @author     Harold Martin <harold.martin@openx.com>
 * @version    0.4
 * @link       https://github.com/openx/OX3-PHP-API-Client/tree/wrapper
 * @see        OX3_Api_Client.php
 */

require_once 'OX3_Api_Client.php';

class OX_API
{
	protected $client;
	protected $v;
	protected $hash;
	public function __construct($ox_api_config)
	{
		$sso = array(
		    'siteUrl'           => $ox_api_config['sso'] . '/api/index/initiate',
		    'requestTokenUrl'   => $ox_api_config['sso'] . '/api/index/initiate',
		    'accessTokenUrl'    => $ox_api_config['sso'] . '/api/index/token',
		    'authorizeUrl'      => $ox_api_config['sso'] . '/login/login',
		    'loginUrl'          => $ox_api_config['sso'] . '/login/process',
		);
		if ($ox_api_config['version'] == 4) {
			$version_path = '/ox/4.0';
			$this->v = 4;
		} else {
			$version_path = '/ox/3.0';
			$this->v = 3;
		}
		$this->hash = $ox_api_config['hash'];
		$this->client = new OX3_API_Client($ox_api_config['uri'], $ox_api_config['email'], $ox_api_config['password'], $ox_api_config['key'], $ox_api_config['secret'], $ox_api_config['realm'],  (dirname(__FILE__) . '/' . $ox_api_config['realm'] . '.txt'), $sso, '', $version_path);
	}
	public function get_hash() {
		return $this->hash;
	}
	public function v1_to_uuid($obj_type, $obj_id) {
		$TYPES_UUID_DICT = array(
		    'account'         => 0xaccf,
		    'user'            => 0xacc0,
		    'accountrelationship' => 0xacc1,
		    'paymenthistory'  => 0xacc2,
		    'site'            => 0xe000,
		    'sitesection'     => 0xe001,
		    'adunit'          => 0xe0ad,
		    'adunitgroup'     => 0xe003,
		    'audiencesegment' => 0xe004,
		    'optimization'    => 0xf001,
		    'adproduct'       => 0xf002,
		    'conversiontag'   => 0xceaf,
		    'creative'        => 0xceae,
		    'competitiveexclusion'=> 0xcead,
		    'order'           => 0xc001,
		    'lineitem'        => 0xc002,
		    'ad'              => 0xc0ad,
		    'report'          => 0xa010,
		);
		$api_version = 0xfff1;
		$clk_data = 0x8123;
		return sprintf('%08x-%04x-%04x-%04x-%s', $obj_id, $TYPES_UUID_DICT[$obj_type], $api_version, $clk_data, substr($this->hash, -6));
	}
	public function adunit($id)
	{
		if ($this->v == 3) {
			return json_decode($this->client->get(('/a/adunit/'. $id), array('overload' => 'medium'))->getBody(), true);
		}
		else if ($this->v == 4) {
			return json_decode($this->client->get('/adunit/'. (is_numeric($id) ? $this->v1_to_uuid('adunit', $id) : $id))->getBody(), true)[0];
		}
	}
	public function adunit_list($id)
	{
		if ($this->v == 3) {			
			return json_decode($this->client->get('/a/site/'. $id . '/listAdUnits')->getBody(), true);
		}
		else if ($this->v == 4) {
			$res = $this->client->get('/adunit', array('site_uid' => (is_numeric($id) ? $this->v1_to_uuid('site', $id) : $id)));
			return json_decode($res->getBody(), true)['objects'];
		}
	}
	public function site($id)
	{
		if ($this->v == 3) {
			return json_decode($this->client->get(('/a/site/'. $id), array('overload' => 'medium'))->getBody(), true);
		}
		else if ($this->v == 4) {
			return json_decode($this->client->get('/site/'. (is_numeric($id) ? $this->v1_to_uuid('site', $id) : $id))->getBody(), true)[0];
		}
	}
	public function site_list($id)
	{
		if ($this->v == 3) {			
			return json_decode($this->client->get('/a/account/' . $id . '/listSites')->getBody(), true);
		}
		else if ($this->v == 4) {
			$res = $this->client->get('/site', array('account_uid' => (is_numeric($id) ? $this->v1_to_uuid('account', $id) : $id)));
			return json_decode($res->getBody(), true)['objects'];
		}
	}
	public function account($id)
	{
		if ($this->v == 3) {
			return json_decode($this->client->get(('/a/account/'. $id), array('overload' => 'medium'))->getBody(), true);
		}
		else if ($this->v == 4) {
			return json_decode($this->client->get('/account/'. (is_numeric($id) ? $this->v1_to_uuid('account', $id) : $id))->getBody(), true)[0];
		}
	}
	public function publisher_list()
	{
		if ($this->v == 3) {
		  $accts = json_decode($this->client->get('/a/acl/account', array('overload' => 'medium'))->getBody(), true);
		  foreach($accts as $elementKey => $element) {
		    if ($element['account_type_id'] != 2 && $element['account_type_id'] != 8) {
		      unset($accts[$elementKey]);
		    }
		  }
			return $accts;
		}
		else if ($this->v == 4) {
			$res = $this->client->get('/account', array('type_full' => 'account.publisher'));
			return json_decode($res->getBody(), true)['objects'];
		}
	}
}

?>

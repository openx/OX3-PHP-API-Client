<?php

require_once 'Zend/Rest/Client.php';
require_once 'Zend/Http/CookieJar.php';
require_once 'Zend/Oauth/Consumer.php';

class OX3_Api_Client extends Zend_Rest_Client 
{
    var $path_prefix = '/ox/3.0';

    public function __construct($uri, $email, $password, $consumer_key, $consumer_secret, $oauth_realm, $cookieJarFile = './OX3_Api_CookieJar.txt', $sso = array())
    {
        parent::__construct($uri);
        $aUrl = parse_url($uri);

        if (empty($sso)) {
            $sso = array(
                'siteUrl'           => 'https://sso.openx.com/api/index/initiate',
                'requestTokenUrl'   => 'https://sso.openx.com/api/index/initiate',
                'accessTokenUrl'    => 'https://sso.openx.com/api/index/token',
                'authorizeUrl'      => 'https://sso.openx.com/login/login',
                'loginUrl'          => 'https://sso.openx.com/login/process',
            );
        }
        
        // Initilize the cookie jar, from the $cookieJarFile if present
        $client = self::getHttpClient();
        $cookieJar = false;
        if (is_readable($cookieJarFile)) {
            $cookieJar = @unserialize(file_get_contents($cookieJarFile));
        }
        if (!$cookieJar instanceof Zend_Http_CookieJar) {
            $cookieJar = new Zend_Http_CookieJar();
        }
        $client->setCookieJar($cookieJar);
        $result = $this->put('/a/session/validate');
        
        // See if the openx3_access_token is still valid...
        if ($result->isError()) {
            // Get Request Token
            $config = array(
                // The default behaviour of Zend_Oauth_Consumer is to use 'oob' when callbackUrl is NOT set
                //'callbackUrl'       => 'oob',
                'siteUrl'           => $sso['siteUrl'],
                'requestTokenUrl'   => $sso['requestTokenUrl'],
                'accessTokenUrl'    => $sso['accessTokenUrl'],
                'authorizeUrl'      => $sso['authorizeUrl'],
                'consumerKey'       => $consumer_key,
                'consumerSecret'    => $consumer_secret,
                'realm'             => $oauth_realm,
            );
            $oAuth = new OX3_Oauth_Consumer($config);
            $requestToken = $oAuth->getRequestToken();

            // Authenticate to SSO
            $loginClient = new Zend_Http_Client($sso['loginUrl']);
            $loginClient->setCookieJar();
            $loginClient->setParameterPost(array(
                'email'         => $email,
                'password'      => $password,
                'oauth_token'   => $requestToken->getToken(),
            ));
            $loginClient->request(Zend_Http_Client::POST);
            $loginBody = $loginClient->getLastResponse()->getBody();

            // Parse response, sucessful headless logins will return oob?oauth_token=<token>&oauth_verifier=<verifier> as the body
            if (substr($loginBody, 0, 4) == 'oob?') {
                $vars = array();
                @parse_str(substr($loginBody, 4), $vars);
                if (empty($vars['oauth_token'])) {
                    throw new Exception('Error parsing SSO login response');
                }

                // Swap the (authorized) request token for an access token:
                $accessToken = $oAuth->getAccessToken($vars, $requestToken)->getToken();
                
                $client->setCookie(new Zend_Http_Cookie('openx3_access_token', $accessToken, $aUrl['host']));
                $result = $this->put('/a/session/validate');
                if ($result->isSuccessful()) {
                    file_put_contents($cookieJarFile, serialize($client->getCookieJar()), LOCK_EX);
                    chmod($cookieJarFile, 0666);
                }
            } else {
                throw new Exception('SSO Authentication error');
            }
        }
    }
 
    /**
     * Overriding the __call() method so that I can return the $response object directly
     * since the Zend_Rest_Client's __call() method *requires* the response to be (valid) XML
     *
     * @param string $method
     * @param array $args
     * @return string The body of the request
     */
    public function __call($method, $args)
    {
        $methods = array('post', 'get', 'delete', 'put');

        if (in_array(strtolower($method), $methods)) {
            if (!isset($args[0])) {
                $args[0] = $this->_uri->getPath();
            }
            if (isset($args[1]) && is_array($args[1])) {
                foreach ($args[1] as $key => $value) {
                    $this->_data[$key] = $value;
                }
            }
            //$this->_data['rest'] = 1;
            $response = $this->{'rest' . $method}($this->path_prefix . $args[0], $this->_data);
            $this->_data = array();//Initializes for next Rest method.
            return $response;
        } else {
            // More than one arg means it's definitely a Zend_Rest_Server
            if (sizeof($args) == 1) {
                // Uses first called function name as method name
                if (!isset($this->_data['method'])) {
                    $this->_data['method'] = $method;
                    $this->_data['arg1']  = $args[0];
                }
                $this->_data[$method]  = $args[0];
            } else {
                $this->_data['method'] = $method;
                if (sizeof($args) > 0) {
                    foreach ($args as $key => $arg) {
                        $key = 'arg' . $key;
                        $this->_data[$key] = $arg;
                    }
                }
            }
            return $this;
        }
    }

    /**
     * Perform a POST or PUT
     *
     * Performs a POST or PUT request. Any data provided is set in the HTTP
     * client. String data is pushed in as raw POST data; array or object data
     * is pushed in as POST parameters.
     *
     * @param mixed $method
     * @param mixed $data
     * @return Zend_Http_Response
     *
     * NOTE: Overload Zend_Rest_Client method to support file uploads.
     */
    protected function _performPost($method, $data = null)
    {
        $client = self::getHttpClient();
        if (is_string($data)) {
            $client->setRawData($data);
        } elseif (is_array($data) || is_object($data)) {
            $client->setParameterPost((array) $data);
        }
        if (isset($this->files) && is_array($this->files) && count($this->files) > 0)
        {
            foreach ($this->files as $file)
            {
                call_user_func_array(array($client, 'setFileUpload'), $file);
            }
        }
        return $client->request($method);
    }

    /**
     * Set a file to upload (using a POST request)
     *
     * Can be used in two ways:
     *
     * 1. $data is null (default): $filename is treated as the name if a local file which
     *    will be read and sent. Will try to guess the content type using mime_content_type().
     * 2. $data is set - $filename is sent as the file name, but $data is sent as the file
     *    contents and no file is read from the file system. In this case, you need to
     *    manually set the Content-Type ($ctype) or it will default to
     *    application/octet-stream.
     *
     * @param string $filename Name of file to upload, or name to save as
     * @param string $formname Name of form element to send as
     * @param string $data Data to send (if null, $filename is read and sent)
     * @param string $ctype Content type to use (if $data is set and $ctype is
     *     null, will be application/octet-stream)
     *
     * NOTE: Function prototype taken from Zend_Http_Client class.
     */
    public function setFileUpload($filename, $formname, $data = null, $ctype = null)
    {
        if (!isset($this->files)) $this->files = array();
        $this->files[] = func_get_args();
    }

    /**
     * Set config values on the HTTP client object
     *
     * @param array $array An array of config key=values to be set on the HTTP client
     */
    function setHttpConfig($config)
    {
        $client = self::getHttpClient();
        $client->setConfig($config);
    }

    /**
     * This is a wrapper method to simplify the process of updating a field in an entity
     * It GETs the existing object, GETs the requiredFields for that object and then POSTs
     * the updated data back to the API
     *
     * @param string  $entity The entity type being updated, e.g. account/user/role etc
     * @param integer $id The ID of the item being updated
     * @param array   $data Array of key=values to be updated in the object
     */
    function update($entity, $id, $data)
    {
        $current         = json_decode($this->get('/a/' . $entity . '/' . $id)->getBody());

        $params = array('action' => 'update');
        $requiredFields  = json_decode($this->get('/a/' . $entity . '/requiredFields', $params)->getBody());
        $availableFields = json_decode($this->get('/a/' . $entity . '/availableFields')->getBody());
        foreach ($requiredFields as $field => $type) {
            if ($availableFields->$field->has_dependencies) {
                $params[$field] = $current->$field;
            }
        }
        $requiredFields  = json_decode($this->get('/a/' . $entity . '/requiredFields', $params)->getBody());
        $update = array();
        foreach($requiredFields as $requiredField => $type) {
            if (property_exists($current, $requiredField)) {
                $update[$requiredField] = (is_null($current->$requiredField)) ? 'null' : $current->$requiredField;
            }
        }
        foreach ($data as $key => $value) {
            $update[$key] = (is_null($value)) ? 'null' : $value;
        }
        return $this->post('/a/' . $entity . '/' . $id, $update);
    }
}

/**
 * @category   Zend
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 *
 * Note - Zend_Oauth_Consumer is extended to make use of the OX3_OAuth_Config and OX3_Oauth_Http_RequestToken classes
 *      - These two classes extend their Zend base classes to add support for 'realm'
 *
 * These changes have been tested against ZendFramework v1.10.6, v1.11.5, v1.11.6 and -trunk (rev 24024)
 */
class OX3_Oauth_Consumer extends Zend_Oauth_Consumer
{
    /**
     * Constructor; create a new object with an optional array|Zend_Config
     * instance containing initialising options.
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        $this->_config = new OX3_Oauth_Config;
        if ($options !== null) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }
            $this->_config->setOptions($options);
        }
    }

    /**
     * Attempts to retrieve a Request Token from an OAuth Provider which is
     * later exchanged for an authorized Access Token used to access the
     * protected resources exposed by a web service API.
     *
     * @param  null|array $customServiceParameters Non-OAuth Provider-specified parameters
     * @param  null|string $httpMethod
     * @param  null|Zend_Oauth_Http_RequestToken $request
     * @return Zend_Oauth_Token_Request
     */
    public function getRequestToken(
        array $customServiceParameters = null,
        $httpMethod = null,
        OX3_Oauth_Http_RequestToken $request = null
    ) {
        if ($request === null) {
            $request = new OX3_Oauth_Http_RequestToken($this, $customServiceParameters);
        } elseif($customServiceParameters !== null) {
            $request->setParameters($customServiceParameters);
        }
        if ($httpMethod !== null) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }
        $this->_requestToken = $request->execute();
        return $this->_requestToken;
    }

    /**
     * Retrieve an Access Token in exchange for a previously received/authorized
     * Request Token.
     *
     * @param  array $queryData GET data returned in user's redirect from Provider
     * @param  Zend_Oauth_Token_Request Request Token information
     * @param  string $httpMethod
     * @param  Zend_Oauth_Http_AccessToken $request
     * @return Zend_Oauth_Token_Access
     * @throws Zend_Oauth_Exception on invalid authorization token, non-matching response authorization token, or unprovided authorization token
     */
    public function getAccessToken(
        $queryData,
        Zend_Oauth_Token_Request $token,
        $httpMethod = null,
        Zend_Oauth_Http_AccessToken $request = null
    ) {
        $authorizedToken = new Zend_Oauth_Token_AuthorizedRequest($queryData);
        if (!$authorizedToken->isValid()) {
            require_once 'Zend/Oauth/Exception.php';
            throw new Zend_Oauth_Exception(
                'Response from Service Provider is not a valid authorized request token');
        }
        if (is_null($request)) {
            $request = new OX3_Oauth_Http_AccessToken($this);
        }

        // OAuth 1.0a Verifier
        if (!is_null($authorizedToken->getParam('oauth_verifier'))) {
            $params = array_merge($request->getParameters(), array(
                'oauth_verifier' => $authorizedToken->getParam('oauth_verifier')
            ));
            $request->setParameters($params);
        }
        if (!is_null($httpMethod)) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }
        if (isset($token)) {
            if ($authorizedToken->getToken() !== $token->getToken()) {
                require_once 'Zend/Oauth/Exception.php';
                throw new Zend_Oauth_Exception(
                    'Authorized token from Service Provider does not match'
                    . ' supplied Request Token details'
                );
            }
        } else {
            require_once 'Zend/Oauth/Exception.php';
            throw new Zend_Oauth_Exception('Request token must be passed to method');
        }
        $this->_requestToken = $token;
        $this->_accessToken = $request->execute();
        return $this->_accessToken;
    }
}

class OX3_Oauth_Http_AccessToken extends Zend_Oauth_Http_AccessToken
{
    /**
     * Generate and return a HTTP Client configured for the Header Request Scheme
     * specified by OAuth, for use in requesting an Access Token.
     *
     * @param  array $params
     * @return Zend_Http_Client
     */
    public function getRequestSchemeHeaderClient(array $params)
    {
        $params      = $this->_cleanParamsOfIllegalCustomParameters($params);
        $headerValue = $this->_toAuthorizationHeader($params, $this->_consumer->getRealm());
        $client      = Zend_Oauth::getHttpClient();

        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setHeaders('Authorization', $headerValue);
        $client->setMethod($this->_preferredRequestMethod);

        return $client;
    }
}

class OX3_Oauth_Http_RequestToken extends Zend_Oauth_Http_RequestToken
{
    /**
     * Generate and return a HTTP Client configured for the Header Request Scheme
     * specified by OAuth, for use in requesting a Request Token.
     *
     * @param array $params
     * @return Zend_Http_Client
     */
    public function getRequestSchemeHeaderClient(array $params)
    {
        $headerValue = $this->_httpUtility->toAuthorizationHeader(
            $params,
            $this->_consumer->getRealm()
        );
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getRequestTokenUrl());
        $client->setHeaders('Authorization', $headerValue);
        $rawdata = $this->_httpUtility->toEncodedQueryString($params, true);
        if (!empty($rawdata)) {
            $client->setRawData($rawdata, 'application/x-www-form-urlencoded');
        }
        $client->setMethod($this->_preferredRequestMethod);
        return $client;
    }
}

class OX3_Oauth_Config extends Zend_Oauth_Config
{
    /**
     * Parse option array or Zend_Config instance and setup options using their
     * relevant mutators.
     *
     * @param  array|Zend_Config $options
     * @return Zend_Oauth_Config
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'realm':
                    $this->setRealm($value);
                    break;
            }
        }
        return $this;
    }

    /**
     * Set OAuth realm
     *
     * @param  string $realm
     * @return Zend_Oauth_Config
     */
    public function setRealm($realm)
    {
        $this->_realm = $realm;
        return $this;
    }

    /**
     * Get OAuth realm
     *
     * @return string
     */
    public function getRealm()
    {
        return $this->_realm;
    }
}

?>

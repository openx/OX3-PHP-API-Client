<?php
/* This file includes the PHP client class supprting Zend Framework Version 2. */

require_once 'autoload.php';
require_once 'zendframework/zendrest/library/ZendRest/Client/RestClient.php';
require_once 'zendframework/zend-http/src/Cookies.php';
require_once 'zendframework/zendoauth/library/ZendOAuth/Consumer.php';

class OX3_Api_Client2 extends ZendRest\Client\RestClient 
{
    var $path_prefix = '/ox/4.0';

    public function __construct($uri, $email, $password, $consumer_key, $consumer_secret, $oauth_realm, $cookieJarFile = './OX3_Api_CookieJar.txt', $sso = array(), $proxy = array(), $path = '/ox/4.0')
    {
        if (preg_match('#/ox/[0-9]\.0#', $path)) {
            $this->path_prefix = $path;
        } else {
            throw new Exception ("Invalid path prefix specified");
        }
        if (empty($proxy)) { $proxy = array(); }
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
        // Set the proxy['adapter'] if $proxy config was passed in
        if (!empty($proxy)) {
          $proxy['adapter'] = 'Zend_Http_Client_Adapter_Proxy';
        }        
        // Initilize the cookie jar, from the $cookieJarFile if present
        $client = self::getHttpClient();
        $cookieJar = false;
        if (is_readable($cookieJarFile)) {
            $cookieJar = @unserialize(file_get_contents($cookieJarFile));
        }
        if (!$cookieJar instanceof Zend\Http\Cookies) {
            $cookieJar = new Zend\Http\Cookies();
        }
        
        $client->setCookies($cookieJar->getAllCookies(Zend\Http\Cookies::COOKIE_STRING_ARRAY));
        $client->setOptions($proxy);
        $result = $this->_checkAccessToken();

        // See if the openx3_access_token is still valid...
        if ($result->isClientError()) {
            // Get Request Token
            $config = array(
                // The default behaviour of Zend_Oauth_Consumer is to use 'oob' when callbackUrl is NOT set
                //'callbackUrl'       => 'oob',
                'siteUrl'           => $sso['siteUrl'],
                'requestTokenUrl'   => $sso['requestTokenUrl'],
                'accessTokenUrl'    => $sso['accessTokenUrl'],
                'authorizeUrl'      => $sso['authorizeUrl'],
                'consumerKey'       => $consumer_key,
                'consumerSecret'    => $consumer_secret
            );
            $oAuth = new ZendOauth\Consumer($config);
            // in order to enforce the Content-Length header to be set, pass a dummy param
            $requestToken = $oAuth->getRequestToken(array('k' => 'v'));
            // Authenticate to SSO
            $loginClient = new Zend\Http\Client($sso['loginUrl']);
            $loginClient->setOptions($proxy);
            $loginClient->setParameterPost(array(
                'email'         => $email,
                'password'      => $password,
                'oauth_token'   => $requestToken->getToken(),
            ));
            $loginClient->setMethod('POST')->send();
            $loginBody = $loginClient->getResponse()->getBody();

            // Parse response, sucessful headless logins will return oob?oauth_token=<token>&oauth_verifier=<verifier> as the body
            if (substr($loginBody, 0, 4) == 'oob?')  {
                $vars = array();
                @parse_str(substr($loginBody, 4), $vars);

                if (empty($vars['oauth_token'])) {
                    throw new Exception('Error parsing SSO login response');
                }
                // Swap the (authorized) request token for an access token:
                $accessToken = $oAuth->getAccessToken($vars, $requestToken)->getToken();
            
                $cookie = new Zend\Http\Header\SetCookie('openx3_access_token', $accessToken);
                $cookie->setDomain($aUrl['host']);
                $client->addCookie($cookie);

                $result = $this->_checkAccessToken();
                if ($result->isSuccess()) {
                    file_put_contents($cookieJarFile, serialize($client->getCookies()), LOCK_EX);
                    chmod($cookieJarFile, 0666);
                }
            } else {
                throw new Exception('SSO Authentication error');
            }
        }
    }
 
    protected function _checkAccessToken()
    {
        switch ($this->path_prefix) {
            case '/ox/3.0':
                return $this->_checkAccessTokenV3();
                break;
            case '/ox/4.0':
                return $this->_checkAccessTokenV4();
                break;
            default:
                throw new Exception('Unknown API path');
                break;
        }
    }
    
    protected function _checkAccessTokenV3()
    {
        $result = $this->put('/a/session/validate');
        return $result;
    }
    
    protected function _checkAccessTokenV4()
    {
        $result = $this->get('/user');
        return $result;
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
                $args[0] = $this->uri->getPath();
            }
            if (isset($args[1]) && is_array($args[1])) {
                foreach ($args[1] as $key => $value) {
                    $this->data[$key] = $value;
                }
            }
            //$this->_data['rest'] = 1;
            $response = $this->{'rest' . $method}($this->path_prefix . $args[0], $this->data);
            $this->data = array();//Initializes for next Rest method.
            return $response;
        } else {
            // More than one arg means it's definitely a Zend_Rest_Server
            if (sizeof($args) == 1) {
                // Uses first called function name as method name
                if (!isset($this->data['method'])) {
                    $this->data['method'] = $method;
                    $this->data['arg1']  = $args[0];
                }
                $this->data[$method]  = $args[0];
            } else {
                $this->data['method'] = $method;
                if (sizeof($args) > 0) {
                    foreach ($args as $key => $arg) {
                        $key = 'arg' . $key;
                        $this->data[$key] = $arg;
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
    protected function performPost($method, $data = null)
    {
        $client = $this->getHttpClient();
        $client->setMethod($method);
        $request = $client->getRequest();
        if (is_string($data)) {
            $client->setContent($data);
        } elseif (is_array($data) || is_object($data)) {
            switch ($this->path_prefix) {
                case '/ox/3.0':
                    $request->getPost()->fromArray((array) $data);
                    break;
                case '/ox/4.0':
                    $rawData = $client->setRawBody(json_encode((array) $data));
                    $headers = $client->setHeaders(array('Content-Type: application/json'));
                    break;
            }
        }
        if (isset($this->files) && is_array($this->files) && count($this->files) > 0)
        {
            foreach ($this->files as $file)
            {
                call_user_func_array(array($client, 'setFileUpload'), $file);
            }
        }
        return $client->send($request);
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
        $client = $this->getHttpClient();
        $client->setOptions($config);
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
        switch ($this->path_prefix) {
            case '/ox/3.0':
                return $this->updateV3($entity, $id, $data);
                break;
            case '/ox/4.0':
                return $this->updateV4($entity, $id, $data);
                break;
        }   
    }
    
    function updateV3($entity, $id, $data)
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
    
    function updateV4($entity, $id, $data)
    {
        return $this->put('/' . $entity . '/' . $id, $data);
    }
}

?>

# ox3-php-api-client
This library provides a client class with examples to facilitate access to the OpenX API.
It handles, most of all, the OAuth1 login process used by OpenX API, and some basic path operations.

In `src\OpenX\PlatformAPI` directory,
* `OXApiClient.php` (using patched Zend2) is the one supported by composer and autoloader, a fully namespaced client to be used in PHP7+

and if you need an older approach and want to use/copy just the file...
* `OX3_Api_Client.php` supports Zend Framework Version 1 
* `OX3ApiClient2.php` supports Zend Framework Version 2     


## Help appreciated

As we do not actively maintain PHP projects, any help in 
straightening up the package and readme will be appreciated.

What might need a review:
- better or less mixed install instructions
- let us know if you need modern (composer, namespaces) or old (dirty file copying, old PHP) setup support
- the `/3.0/` paths of the API are obsolete and not supported anymore, we can clean them up from the code
- make the package independent from frameworks
  - especially ones that are obsolete (Zend)

We'd like to thank:
- Fei Song for a push towards making
this a proper composer package.


## Installation 

**(recommended) if working with Composer and Zend2**

* See the example `Dockerfile` for inspiration
* You should only need to add `openx/ox3-php-api-client` as dependency
* Then you can use the class `OpenX\PlatformAPI\OXApiClient` in your project.

### Try it out in the docker image

* `docker build . -t openx-test`
* `docker run -it openx-test bash`
  * `export OPENX_URI=http://your-ui.openx.net`
  * `export OPENX_EMAIL=john.doe@company.com`
  * `export OPENX_PASWORD="My password satisfies the sane requirements for a long p@assword!"`
  * `export OPENX_KEY=c0n5um3rK3y`
  * `export OPENX_SECRET=c0n5um3rS3cR3T`
  * `export OPENX_REALM="your_realm" # (usually same as hostname prefix)`
  * `php example.php`


## Older install instructions, to be reviewed:

**If Working with Zend Framework 1:**  
1) Install Zend Framework 1.12.13 (link: http://framework.zend.com/downloads/latest#ZF2).  
2) Set include_path:  
* Method 1: Navigate to /private/etc/php.ini and set include_path to the path to the Zend Framework under the   "/library" directory.  
````
include_path = ".:/Users/.../ZendFramework-1.12.13/library/"   
````
* Method 2: Alternatively, you can create a file called 'set_path.php' and set your paths there.  If you chose to set the path using this option, the file 'set_path.php' should look like:  
````
<?php  
$path1 = '/Users/.../ZendFramework-1.12.13/library/';  

set_include_path($path1);  
?>  
````
** If you use this option make sure to create the file in the same folder as the folder which contains the   OX3_API_Client.php file. Also make sure to require the file by adding the line "require_once 'set_path.php'; " to the top of the OX3_Api_Client.php file.

**If Working with Zend Framework 2:**  
1) Install Zend Framework 2 (link: http://framework.zend.com/downloads/latest#ZF2).    
2) Install Composer in your working directory (link: https://getcomposer.org/doc/00-intro.md).  
3) Run the command "php composer.phar init". After doing this. composer will guide you through the process of   configuring a composer.json file. To allow the default configuration, press enter or "yes" when prompted.   
4) Install the required Zend packages, ZendOAuth and ZendRest:     
To do so, add the "repositories" and "require" sections in the composer.json file so the file looks    
as follows:
```json
{
  "name": "",
  "authors": [
    {
      "name": "",
      "email": ""
    }
  ],
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.zendframework.com/"
    }
  ],
  "require": {
    "zendframework/zendoauth": "2.0.*",
    "zendframework/zendrest": "2.0.*"
  }
}
```

5) Run the command "php composer.phar install" to install the packages and their dependencies.   
6) Set include_path:  
* Method 1: Navigate to /private/etc/php.ini and set include_path to the paths to the Zend Framework under the     ".../library/" directory and to the newly installed packages under the  ".../vendor/" directory. The "vendor"   folder should appear in your working directory after installing the packages with composer.          
```ini
include_path = ".:/Users/.../ZendFramework-2.4.5/library/:/Users/.../vendor/" 
```
* Method 2: Alternatively, you can create a file called 'set_path.php' and set your paths there.  If you chose to set the path using this option, the file set_path.php should look like:  
```php
<?php  
$path1 = '/Users/.../ZendFramework-2.4.5/library/';
$path2 = '/Users/.../vendor/';  

set_include_path($path1 . PATH_SEPARATOR . $path2);  
?>  
```
** If you use this option make sure to create the file in the same folder as the folder which contains the   OX3_API_Client2.php file. Also make sure to require the file by adding the line "require_once 'set_path.php'; " to the top of the OX3_Api_Client2.php file.  

#Authentication/Example Scripts  
Add this to your code to authenticate with Oauth:  
```injectablephp
<?php
// If using Zend Framework 1
require_once 'OX3_Api_Client.php';
// if Using Zend Framework 2
require_once 'OX3_Api_Client2.php';
// if using the composer autoloader with the namespaced client
require_once('vendor/autoload.php');

$uri      = 'http://host';  
$email    = 'root@openx.org';  
$password = '';  
$key      = '';  
$secret   = '';  
$realm    = '';  

// If using Zend Framework 1
$client = new OX3_API_Client($uri, $email, $password, $key, $secret, $realm); 
// if Using Zend Framework 2
$client = new OX3_API_Client2($uri, $email, $password, $key, $secret, $realm);
// if using the new class 
$client = new \OpenX\PlatformAPI\OXApiClient($uri, $email, $password, $key, $secret, $realm);
?>
````
** Note that when running the example scripts, OX3_Api_Client.php/OX3_Api_Client2.php must be in the same folder  as the script. Also note that the example scripts contain some user configurable variables (besides the authentication section), which are described at the top of the scripts. 

#Usage
* To see the results in a friendly format on the command line, use the functions json_decode, getBody, and print_r.
Ex.:

      $result = $client->get('/account');
      print_r(json_decode($result->getBody(), true));

**GET REQUESTS:**  
* To get all current objects of a certain type, use the following request: 
```injectablephp
$result = $client->get('/"object_type"');  
// Example:
$result = $client->get('/account');  
```

* To get the object(s) with a specific value for some attribute(s), pass in the value of the desired attribute as an array along with the path: 
```injectablephp
$query = array("attribute"=>"value");  
$result = $client->get('/object_type', $query)  
// Example:
$query1 = array('name'=>'OpenX');  
result1 = $client->get('/account', $query1);  
// --> Returns the account(s) with the name OpenX  
```

* Many fields have multiple options for what value they can take on. To see these options, use the following   request:
```injectablephp
$result = $client->get('/options/:field_options')  
// Example:
$content_types = $client->get('/options/content_type_options');
```

**POST REQUESTS:**
* To create an object, make a post request, passing in the path along with an array which includes the values of the fields for the object 
```injectablephp
$query = array(  
'account_uid'=>"...",   
'currency'=>"...",   
.  
.  
.  
'timezone'=>"...");  
$result = $client->post('/:object_type/', $query);    
```

**PUT REQUESTS:**  
* To update an object, make a put resquest, passing in the path along with an array which includes the parameters that are being updated  
```injectablephp
$query = array('timezone'=>'updated_value');  
$result = $client->put('/:object_type/:object_uid', $query);   
```

**DELETE REQUESTS:**  
* To delete an object, make a delete request, passing in the path including the uids/id of the object that is to be deleted: 
```injectablephp
$result = $client->delete('/:object_type/:object_uid');
// Example:  
$result = $client->delete('/site/6003a1c2-e000-fff1-8123-0c9a66');    
```

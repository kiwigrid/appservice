<?php
/* ************************************************************************* *
 *                                                                           *
*   Copyright (c) 2012 KIWIGRID GmbH                                        *
*                                                                           *
*   All Rights Reserved                                                     *
*                                                                           *
*   This source code is property of KIWIGRID GmbH. Any redistribution of    *
*   the source code (modified or not modified) is protected by copyright.   *
*   You must not copy, distribute, compile or decompile this code           *
*   or parts of it.                                                         *
*                                                                           *
*   http://www.kiwigrid.com                                                 *
*                                                                           *
* ************************************************************************* */

/**
 *
 * Basic setup for a SSL Testclient to call the kiwigrid REST API at https://appservice.kiwidev.com/
 * Take a look at the autoload.php in the "app" directory for including third party libs (like these) into symfony2
 * The Testclient first initializes a session with either a basic authentication via login/password or a given ssoToken
 * Both information are posted as json with SSL client certification and return json objects.
 * some change
 *
 * @author max.kruse
 *
 */

namespace AppService\Core;

use Monolog\Logger;

use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AclAfterInvocationProvider;

/**
 * This class provides basic functionalities for making REST calls to the Kiwigrid WebAPI. In Symfony2 this class will be
 * instanciated as a service as configured in the global config.yml. The the locations for the needed certificates, its
 * password, the base URL and the session handler are configured.
 *
 * After succefully initilizing a session every call will include this session-id. To make a REST call use the call
 * function.
 *
 * @author soeren.lubitz
 *
 */
use Symfony\Component\HttpFoundation\Request;

class AppService {

	// security Context for a appservice user binding to symfony roles
	protected $securityContext;

	//test with fully baseURI
	private $baseURI = 'https://appservice.kiwidev.com/';
	private $sId = null;
	private $sessionHandler = null;

	// standard values for cURL Session
	private $options = array(
			CURLOPT_HTTPHEADER => array('Content-type: application/json'),
			CURLOPT_HEADER => 0,
			CURLOPT_VERBOSE => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_TIMEOUT => 1500);

	/**
	 * sets the ssl credentials to request as authenticated client against appservice
	 *
	 * @param string $ssl_cert		path to client ssl certificate
	 * @param string $ssl_pass		password for ssl certificate
	 * @param string $ssl_key		path to client ssl key
	 *
	 */
	private function setAuthentication($ssl_cert, $ssl_pass, $ssl_key, $ca_cert) {
		$this->options[CURLOPT_SSLCERT] = $ssl_cert;
		$this->options[CURLOPT_SSLKEY] = $ssl_key;
		$this->options[CURLOPT_SSLCERTPASSWD] = $ssl_pass;
		$this->options[CURLOPT_SSLKEYTYPE] = 'PEM';
		$this->options[CURLOPT_CAINFO] = $ca_cert;
	}

	/**
	 * Inits the AppService Provider
	 *
	 *
	 * @param iAppserviceSessionHandler $sessionHandler		a session Handler implementing this interface
	 * @param string $ssl_cert								the location of the ssl certificate file
	 * @param string $ssl_key								the location of the ssl key file
	 * @param string $ssl_pass								the ssl password
	 * @param string $baseUrl								the base url all REST call should be called to
	 *
	 * @throws RuntimeException								If curl is not loaded.
	 */
	public function __construct(iAppserviceSessionHandler $sessionHandler, $ssl_cert, $ssl_key, $ssl_pass, $ca_cert, $baseUrl, Request $request) {

		// check if CURL is enabled.
		if (!function_exists('curl_init')) {
			throw new \RuntimeException('Sorry cURL is not installed! Please refer to the php.ini to enable the cURL extension. ');
		}

		$this->sessionHandler = $sessionHandler;

		$this->setAuthentication($ssl_cert, $ssl_pass, $ssl_key, $ca_cert);

		if ($baseUrl) {
			$this->baseURI = $baseUrl;
			if (substr($this->baseURI, -1) != '/') {
				$this->baseURI .= '/';
			}
		}

		$ssoToken = $request->get('ssoToken', null);

		$postTitleJS = $request->get('postTitleJS', null);

		if ($ssoToken) {

			$this->initSessionWithToken($ssoToken);
			$user = self::call('users/-/user', 'GET');
			if ($user) {
				$this->setUpUser ($user, $request);
			}

			if ($postTitleJS) {
				$this->sessionHandler->set('postTitleJS', $postTitleJS);
			}
		} else {
			$this->sId = $this->sessionHandler->retrieveSession();
		}
	}

	public function setUpUser ($user, $request ) {

		$channelname = $user['user']['channel'];
		$language = $user['user']['language'];
		$role = $user['user']['userType'];
		$this->sessionHandler->set('channelname', $channelname);
		$this->sessionHandler->set('language', $language);
		$this->sessionHandler->set('_locale', $language);
		$this->sessionHandler->set('role' , $role);
		// remember the locallistener is executed before executing the appservice methods, so there could be locale property set from previous symfony 2.1 session when starting the app again
		// imagine you start the app in german, the locale listener checks for session locale and write it to the request prop
		// when restarting the app again with the e.g. locale englisch the locale listener is executed before the app service method, so it sets the locale to the previously saved locale
		// so at the end the retrieved user language comes into late and the correct language is only set when the user restarts the app once again
		// to avoid the locale listener to take the saved locale from previous app session just overwrite the locale when initializing the session here
		if (isset($language)) {
			$request->setLocale($language);
		}

		$login = isset($user['user']['login']) ? $user['user']['login'] : null;
		$firstName = isset($user['user']['firstName']) ? $user['user']['firstName'] : null ;
		$lastName = isset($user['user']['lastName']) ? $user['user']['lastName'] : null;
		if (isset($firstName) && isset($lastName)) {
			$fullName = $firstName.' '.$lastName;
		} else {
			$fullName = $login;
		}
	}

	/**
	 * Inits the webservice call via backend session with given (restricted) login.
	 *
	 * @param string $login
	 *
	 * @return string sessionId
	 */

	public function initBackendSessionWithLogin ($login) {
		try {
			$user = self::call('users/-/user', 'GET');
		} catch (\Exception $e) {
			return $this->initSession(array('login' => $login));
		}
	}

	/**
	 * Inits the webservice call with given login and password.
	 *
	 * @param string $login
	 * @param string $password
	 *
	 * @return string sessionId
	 * @throws RuntimeException			If the session cannot be initialized, the given files cannot be read, the request fails or the answer cannot be parsed.
	 */
	public function initSessionWithCredentials($login, $password) {
		return $this->initSession(array('login' => $login, 'password' => $password));
	}

	/**
	 * Inits the webservice call with given token.
	 *
	 * @param string $ssoToken
	 *
	 * @return string sessionId
	 * @throws RuntimeException			If the session cannot be initialized, the given files cannot be read, the request fails or the answer cannot be parsed.
	 */
	public function initSessionWithToken($token) {
		return $this->initSession(array('ssoToken' => $token));
	}

	/**
	 * Inits the webservice call with given arguments.
	 *
	 * @param array $bodyObjects		An array of request bodyobjects from form input must be given either as login/password combination xor ssoToken
	 *
	 * @throws RuntimeException			If the session cannot be initialized, the given files cannot be read, the request fails or the answer cannot be parsed.
	 */
	private function initSession($bodyObject) {

		$this->sId = null;
		$this->sessionHandler->storeSession($this->sId);

		// API query parameter for creating sessionId
		$output = self::call('sessions','POST', null, $bodyObject);

		$sessionId = isset($output['sessionId']) ? $output['sessionId'] : null;

		if (!$sessionId) return false;

		$this->sId = $sessionId;
		$this->sessionHandler->storeSession($sessionId);

		return $this->sId;
	}

	/**
	 * Makes the actual http call to the REST API. All or no Parameters must be specified via the queryParams argument.
	 *
	 *
	 * @param string $query			the rest path (nodes/-/nodes)
	 * @param string $method		the HTTP method (GET, POST, PUT, DELETE) are currently supported by the API
	 * @param array $queryParams	a key->value array for parameters that should be included in the url
	 * @param array $requestBody	an array structure the represents the request body. Will be converted to json.
	 * @param array $options		a key->value array for additional curl options
	 *
	 * @return array				The response body
	 * @throws \RuntimeException
	 */
	public function call($query, $method = 'GET', $queryParams = array(), $requestBody = array(), $options = array()) {

		$queryParams = ($queryParams)?$queryParams:array();
		$requestBody = ($requestBody)?$requestBody:array();
		$sessionIdHeader = 'Session-Id: '.$this->sId;

		if(!in_array($sessionIdHeader, $this->options[CURLOPT_HTTPHEADER])) {
			 $this->options[CURLOPT_HTTPHEADER][] = 'Session-Id: '.$this->sId;
		}
		$requestString = $query;
		$queryString = '?';
		$and = ($queryString != '?') ? '&' : '';

		foreach ($queryParams as $key => $value) {
			if (is_array($value)) {
				if (count($value)) {
					foreach ($value as $v) {
						$queryString .= $and . $key . '[]=' . urlencode($v);
						$and = '&';
					}
				} else {
					$queryString .= $and . $key . '[]=';
				}
			}
			else {
				$queryString .= $and . $key . '=' . urlencode($value);

			}
			$and = '&';
		}

		$requestString .= ($queryString != '?') ? $queryString : '';

		// load curl stuff.
		$ch = curl_init($this->baseURI . $requestString);

		if ($ch === false) {
			throw new \RuntimeException('Error calling: "'.$this->baseURI.$requestString.'"');
		}

		// copy default options
		$callOptions = $this->options;

		//merge new option
		foreach($options as $k => $v)
		{
			$callOptions[$k] = $v;
		}

		$callOptions[CURLOPT_CUSTOMREQUEST] = $method;
		$callOptions[CURLOPT_POSTFIELDS] = json_encode($requestBody);

		// set up cURL session
		curl_setopt_array($ch, $callOptions);
		//execute cURL session
		$result = curl_exec($ch);

		//cURL Error Output - in this case every cURL errormessage are handled as this sample textmessage
		if (curl_errno($ch)) {
			$e = new \RuntimeException('Error calling: "'.$this->baseURI.$requestString."\" \nCurl Error " . curl_errno($ch) . ': ' . curl_error($ch));
			curl_close($ch);
			throw $e;
		} else {
			// close cURL session debug messages
			curl_close($ch);
			// output result as json accociative array
			$output = json_decode($result, true);

			return $output;
		}
	}

}

<?php
/**
 * A simple WebService Behavior class that eases POST & GET requests to foreign pages
 * Entirely PHP based, does not require and modules or cURL.
 * Also has ability to create and send XMLRPC requests.
 * 
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * Based on the WebserviceModel authored by Felix Geisendörfer (http://debuggable.com)
 *
 * @author Mark Story (mark-story.com)
 * @revision $Revision: 93 $ 
 */
App::import('Core', 'Socket');

class WebserviceBehavior extends ModelBehavior {
   
/**
 * User Agent to use for Requests
 *
 * @var string
 **/
	public $userAgent = 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.8.1.14) Gecko/20080404 Firefox/2.0.0.14';
	
/**
 * String of Content Types accepted.
 *
 * @var string
 **/
	public $acceptTypes = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
	
/**
 * Accept-Language Header
 *
 * @var string
 **/
	public $acceptLanguage = 'en-us,en;q=0.5';
/**
 * Cookies that come from requests
 *
 * @var array
 */	
	public $cookies = array();
	
/**
 * Contain settings indexed by model name.
 *
 * @var array
 */
	private $__settings = array();
	
/**
 * The valid request types for the behaviour
 * @var array
 */
	protected $_validRequests = array('get', 'post', 'xml');
	
/**
 * Information about the last made Request, useful for debugging.
 *
 * @var array
 **/
	protected $_lastInfo = array();
	
/**
 * Formatted Data to be sent.
 *
 * @var string
 **/
	protected $_data = null;
	
/**
 * Instance of CakeSocket
 *
 * @var Object
 **/
	protected $Socket = null;

/**
 * Settings can be set with the following:
 *
 * timeout   - 	The time to wait before Timing out on a connection.
 *				defaults to 30 sec.
 *
 * persistent - Keep the connection alive between calls.
 *				
 * defaultUrl - The default URL to use for requests. Useful if you have a webservice with only
 *				one URL.  
 *
 * port -       The remote port to use if not 80				
 * 
 */	
	private $__defaults = array(
		'timeout' => 30,
		'persistent' => false,
		'defaultUrl' => null,
		'port' => 80,
		'protocol' => 'tcp',
		'followRedirect' => true
	);
    
	public function setup(&$Model, $settings = array()) {
		$options = am($this->__defaults, $settings);
	
		$this->__settings[$Model->name] = $options;
		
		if ($options['persistent']) {
			$this->serviceConnect($Model, $options['defaultUrl'], $options);
		}
	}
/**
 * Request
 *
 * Make/Send Requests.  Supports GET, POST and XMLRPC.
 *
 * @param string $type The type of request to make get, post, xml are valid options.
 * @param Array $params Array of Options see below.
 * @return mixed Resulting Page if successful request or false if time out or connection failure.
 *
 * Options:
 *	data    - mixed  - Array of data to send in the request, will be serialized to the correct type. 
 *	url     - string - An alternate URL to use for this request if different from the defaultUrl
 *	headers - array  - Optional Additional Headers you may wish to set.  'headername' => 'value'
 *	options - array  - Additional Connection options to use for this request
 **/
	public function request(&$Model, $type = 'get', $params = array()) {
		if (!in_array($type, $this->_validRequests)) {
			return false;
		}
		
		$this->_lastInfo = array();
		
		$defaults = array('data' => array(), 'url' => null, 'headers' => array(), 'options' => array());
		$params = array_merge($defaults, $params);
				
		switch ($type) {
			case 'get':
			case 'post':
				$this->_formatUrlData($params['data']);
				break;
			case 'xml':
				$this->_formatXmlData($params['data']);
				break;
		}
		
		//switch url if necessary
		if (!empty($params['url'])) {
			$this->serviceConnect($Model, $params['url'], $params['options']);
		} elseif (!empty($this->__settings[$Model->name]['defaultUrl'])){
			$this->serviceConnect($Model, $this->__settings[$Model->name]['defaultUrl'], $params['options']);
		} else {
			return false;
		}
		
		//make request.
		$out = $this->{'_'.$type}($Model, $params);
		$this->resetService();
		return $out;
	}
	
/**
 * Connect the Behavior to a new URL
 *
 * @param string $url The URL to connect to.
 * @param array $options Options Array for the new connection. 
 * @return bool success
 **/
	public function serviceConnect(&$Model, $url, $options = array()) {
		$options = array_merge($this->__settings[$Model->name], $options);
		$path = $this->_setPath($url);			
		$options['host'] = $path['host'];
		
		if ($this->Socket === null) {
			$this->Socket = new CakeSocket($options);	
		} else {
			if ($this->Socket->connected && $this->__settings[$Model->name]['persistent'] == false) {
				$this->serviceDisconnect($Model);
			}
			$this->Socket->config = $options;
		}
		$this->__setInfo(array('connection' => $options, 'host' => $path['host'], 'path' => $path['path']));
		return $this->Socket->connect();
	}
	
/**
 * Disconnect / Reset the Webservice Socket.
 *
 * @return boolean
 **/
	public function serviceDisconnect(&$Model) {
		if ($this->Socket !== null) {
			$this->Socket->disconnect();
			$this->Socket->reset();
		}	
	}

/**
 * Reset the WebService Behavior
 *
 * @return void
 **/
	public function resetService() {
		$this->_headers = array();
		$this->_data = null;
		$this->_rawCookies = null;
		$this->cookies = null;
	}

/**
 * Get the last requests' information, good for debugging.
 *
 * @return array
 **/
	public function getRequestInfo() {
		return $this->_lastInfo;
	}
	
/**
 * Set Cookie data to the Webservice
 *
 * @param string $cookieData Raw cookie Strings. 
 * @return bool
 */
	public function setCookie($cookieData) {
		$parts = explode('; ', $cookieData);
		foreach ($parts as $part) {
			list($name, $value) = explode('=', $part);
			$cookie[$name] = $value;
		}
		$this->cookies[] = $cookie; 		
		$this->__setInfo('cookie', $cookie);
		return true;
	}
	
/**
 * GET Request
 *
 * @return Mixed data retrieved from Request
 **/
	protected function _get(&$Model, $params = array()) {
		if (!empty($this->_data)) {
			$addr = $this->_path . '?' . $this->_data;
 		} else {
			$addr = $this->_path;
		}
		$params['headers']['Host'] = $this->_host;
		$params['headers']['Connection'] = 'Close';
		
		$this->_formatHeaders($params['headers']);
						
		$request = "GET {$addr} HTTP/1.0\r\n";
		$request .= $this->_headers;
		$request .= "\r\n\r\n";
		
		$this->__setInfo('requestHeaders', $request);
		
		$this->Socket->write($request);		
		$response = '';		
		while ($data = $this->Socket->read()) {
			$response .= $data;
		}
		$this->_parseResponse($response);
		
		if ($this->__settings[$Model->name]['followRedirect'] && array_key_exists('Location', $this->response['headers'])) {
			$this->serviceConnect($Model, $this->response['headers']['Location'], $params);
			$this->_get($Model);
		}
		return $this->response['body'];
	}
	
/**
 * POST Request
 *
 * @return Mixed data retrieved from Request
 **/
	protected function _post(&$Model, $params = array()) {				
		$postHeaders = array(
			'Host' => $this->_host,
			'Connection' => 'Close',
			'Content-Length' => strlen($this->_data),
		);
		$params['headers'] = array_merge($params['headers'], $postHeaders);
		if (!isset($params['headers']['Content-Type'])) {
			$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		}
		
		$this->_formatHeaders($params['headers']);
		
		$request = "POST {$this->_path} HTTP/1.0\r\n";
		$request .= $this->_headers . "\r\n";
		$request .= "\r\n";
		$request .= $this->_data;
		
		$this->__setInfo('requestHeaders', $request);
		
		$this->Socket->write($request);		
		$response = '';		
		while ($data = $this->Socket->read()) {
			$response .= $data;
		}
		$this->_parseResponse($response);
		
		if ($this->__settings[$Model->name]['followRedirect'] && array_key_exists('Location', $this->response['headers'])) {
			$this->serviceConnect($Model, $this->response['headers']['Location'], $params);
			$this->_data = null;
			$this->_get($Model, $params);
		}
		return $this->response['body'];
	}	
	
/**
 * XMLRPC Request
 *
 * @return Mixed data retrieved from Request
 **/
	protected function _xml(&$Model, $params = array()) {
		$additionalHeaders = array(
			'Content-Type' => 'text/xml'
		);
		$params['headers'] = array_merge($params['headers'], $additionalHeaders);
		
		return $this->_post($Model, $params);
	}
	
/**
 * Parse the Reponse from the request, separating the headers from the content.
 *
 * @return void
 **/
	protected function _parseResponse($response) {
		$headers = substr($response, 0, strpos($response, "\r\n\r\n"));
		$body = substr($response, strlen($headers));
		
		//split up the headers
		$parts = preg_split("/\r?\n/", $headers, -1, PREG_SPLIT_NO_EMPTY);
		$heads = array();
		for ($i = 1, $total = sizeof($parts); $i < $total; $i++ ) {
			list($name, $value) = explode(': ', $parts[$i]);
			$heads[$name] = $value;
		}
		if (array_key_exists('Set-Cookie', $heads)) {
			$this->setCookie($heads['Set-Cookie']);
		}
		$this->__setInfo('responseHeaders', $heads);
		
		$this->response['headers'] = $heads;
		$this->response['body'] = trim($body);		
	}
	
/**
 * Set the host and path for the webservice.
 * @param string $url The complete url you want to connect to.
 * @return array Host & Path
 **/
	protected function _setPath($url) {
		$port = 80;
		if (preg_match('/^https?:\/\//', $url)) {
			$url = substr($url, strpos($url, '://') + 3);			
		}
		if (strpos($url, '/') === false) {
			$host = $url;
			$path = '/';
		} else {
			$host = substr($url, 0, strpos($url, '/'));
			$path = substr($url, strlen($host));
		}
		if ($path == '') {
			$path = '/';
		}
		$this->_host = $host;
		$this->_path = $path;
		return array('host' => $host, 'path' => $path, 'port' => $port);
	}
		
/**
 * Formats Additional Request Headers 
 *
 * @return void
 **/
	protected function _formatHeaders($headers = array()) {
		$headers['User-Agent'] = $this->userAgent;
		$headers['Accept'] = $this->acceptTypes;
		$headers['Accept-Language'] = $this->acceptLanguage;

		if (!empty($this->cookies)) {
			foreach ($this->cookies as $cookie) {
				reset($cookie);
				$key = key($cookie);
				$value = $cookie[$key];
				$cooks[] = "$key=$value";
			}
			$headers['Cookie'] = implode('; ', $cooks);
		}
		
		foreach ($headers as $name => $value) {
			$tmp[] = "$name: $value";
		}		
		$header = implode("\r\n", $tmp);
		$this->__setInfo('requestHeaders', $header);
		$this->_headers = $header;
	}
	
/**
 * Format data for HTTP get/post requests
 *
 * @return void
 **/
	protected function _formatUrlData($params) {
		$postData = array();
        if (is_array($params)) {
			$this->_data = http_build_query($params, '', '&');
		} else {
			$this->_data = urlencode($params);
		}
		$this->__setInfo('data', $this->_data);
	}
	
/**
 * Format data for XmlRpc requests.
 *
 * XMLRPC Serialization is performed here. Params for XMLRPC are a bit different than simple post/get.
 * be sure to specify a methodName in $params.  The data will be auto-typed based on the Data type in PHP
 * If arrays have any non-numeric keys they will become <structs> If you wish to force a type you can do so by changing
 * the element to an array. See the example below.
 *
 * usage. $this->request('xml', array('data' => $bigArray, 'methodName' => 'getImages'));
 *
 * Data array Sample:
 *
 * $bigArray = array(
 *		'simpleString' => 'sample',	
 *		'integerVal' => 1,
 *		'doubleVal' => 3.145,
 * 		'forcedInt' => array('value' => '1', 'type' => 'int'),
 *		'arrayType' => array('value' => array(2, 3, 4), 'type' => 'array'),
 *	);
 *
 * Keep in mind that when coercing types bad things can happen, if you are incorrect in your assumptions.
 *
 * @return void
 **/
	protected function _formatXmlData($params) {
		if (!class_exists('Xml')) {
			App::import('Core', 'Xml');
		}
		$defaults = array('methodName' => '', 'data' => array());
		$params = array_merge($defaults, $params);
		
		$message = new XmlRpcMessage();
		$message->methodName = $params['methodName'];
		$message->setData($params['data']);
		$result = $message->toString();
	
		$this->_data = $result;	
	}
	
/**
 * Add into the lastInfo array.  Works like Controller::set();
 *
 * @return void
 **/
	private function __setInfo($one, $two = null) {
		$data = array();

		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}
		$this->_lastInfo = array_merge($this->_lastInfo, $data);
	}

/**
 * Destructor, used to disconnect from current connection.
 *
 */
	function __destruct() {
		$Model = null;
		$this->serviceDisconnect($Model);
	}
}


/**
 * XmlRpcMessage
 *
 * A Simple Class that creates a wrapper for formatting and creating XMLRPC requests
 *
 * @package webservice.behavior
 * @author Mark Story
 **/
class XmlRpcMessage extends Object {
/**
 * Instance of XML object
 *
 * @var object
 **/
	protected $_xml = null;
/**
 * Request Method Name
 *
 * @var string
 **/
	public  $methodName = '';
/**
 * Data the payload of the XMLRPC message
 *
 * @var mixed
 **/
	protected $_data = array();

/**
 * Data Types that can be used
 *
 * @var array
 */
	protected $_dataTypes = array(
		 'double', 'int', 'date', 'string', 'array', 'struct' 
	);
/**
 * Constructor
 *
 **/
	public function __construct() {
		$this->_xml = new Xml(null, array('format' => 'tags'));
	}
	
/**
 * Convert Message to XML string
 *
 * @return string of Parsed XMLRPC message
 **/
	public function toString() {
		$this->_createXml();
		return $this->_xml->toString(array('cdata' => false, 'header' => true));
	}
	
/**
 * Set the Data array, clears and sets the data internal data structure
 * Checks for type casting and auto type casts if necessary 
 *
 * Data array Sample:
 *
 * $bigArray = array(
 *		'simpleString' => 'sample',	
 *		'integerVal' => 1,
 *		'doubleVal' => 3.145,
 * 		'forcedInt' => array('value' => '1', 'type' => 'int'),
 *		'arrayType' => array('value' => array(2, 3, 4), 'type' => 'array'),
 *	);
 *
 * Keep in mind that when coercing types bad things can happen, if you are incorrect in your assumptions.
 *
 * @return bool
 **/
	public function setData($data) {
		if (!is_array($data)) {
			$data = (array)$data;
		}
		foreach ($data as $param) {
			if (is_array($param) && isset($param['type']) && isset($param['value']) && count($param) == 2) {
				$this->addParam($param['value'], $param['type']);				
			} else {
				$this->addParam($param);
			}
		}
		return true;
	}
	
/**
 * Add a parameter to the Internal Data array
 * Data array Sample:
 *
 * Keep in mind that when coercing types bad things can happen, if you are incorrect in your assumptions.
 *
 * @param string $value 
 * @param string $type 
 * @return bool
 */
	public function addParam($value, $type = null) {
		if (is_null($type)) {
			$type = $this->_typecast($value);
		}
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$t = $this->_typecast($v);
				$value[$k] = array('value' => $v, 'type' => $t);
			}
		}
		$this->_data[] = array('type' => $type, 'value' => $value);
	}
/**
 * Get the data inside the XmlRpcMessage
 *
 * @return mixed
 */
	public function getData() {
		return $this->_data;
	}
	
/**
 * Reset the Message and start over
 *
 * @return void
 */
	public function reset() {
		$this->methodName = null;
		$this->_data = array();
		$this->_xml = new Xml(null, array('format' => 'tags'));
	}
/**
 * Typecast a value
 * Retrieve the proper XMLRPC data type for a value
 *
 * @param string $value 
 * @return string Type identifier
 */
	protected function _typecast($value) {
		$type = null;		

		if (is_string($value)) {
			$type = 'string';
		}
		if (is_int($value)) {
			$type = 'int';
		}
		if (is_float($value)) {
			$type = 'double';
		}
		if (is_bool($value)) {
			$type = 'boolean';
		}
		if (is_array($value)) {
			$type = 'array';
			
			$valueKeys = array_keys($value);
			foreach($valueKeys as $vk) {
				if (!is_numeric($vk)) {
					$type = 'struct';
					break;
				}
			}
		}
		return $type;
	}
/**
 * Convert internal data to Xml
 *
 * @return void
 **/
	protected function _createXml() {
		$methodCall = $this->_xml->createElement('methodCall', null);
		$methodCall->createElement('methodName', $this->methodName);	
		$this->_paramsEl = $methodCall->createElement('params', null);	
		
		$this->__parseData($this->_data, $this->_paramsEl, false);
	}

/**
 * Parse internal data structure into XML data structures.
 * Auto type casts data and checks for forcing.
 *
 * @return Array of xmlobjects
 **/
	private function __parseData($data, $parent, $inner = false) {
		$out = array();
		foreach ($data as $param) {
			extract($param);
			
			$valueElement = $parent->createElement('value', null);
			
			switch ($type) {
				case 'array':
					$arrayEl = $valueElement->createElement('array', null);
					$dataEl = $arrayEl->createElement('data', null);				
					$this->__parseData($value, $dataEl, true);
					break;
				case 'struct':
					$structEl = $valueElement->createElement('struct', null);
					foreach ($value as $memberKey => $memberValue) {
						$memberEl = $structEl->createElement('member', null);
						$memberEl->createElement('name', $memberKey);
						$this->__parseData(array($memberValue), $memberEl, true);
					}
					break;
				case 'date':
					$valueElement->createElement('dateTime.iso8601', date('Ymd\TH:i:s', strtotime($value) ));
					break;
				case 'base64':
				case 'string':
				case 'int':
				case 'double':
					$valueElement->createElement($type, $value);
					break;
				case 'boolean':
					$bool = (boolean)$value ? 'true' : 'false';
					$valueElement->createElement('boolean', $bool);
				break;				
			}

			if ($inner == false) {
				$paramElement = $parent->createElement('param', null);
				$valueElement->setParent($paramElement);
			} else {
				$paramElement = $valueElement;
			}
			$out[] = $paramElement;
		}
		return $out;		
	}
	
} // END class XmlRpcMessage extends Object
?>
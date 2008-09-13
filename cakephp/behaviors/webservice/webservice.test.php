<?php


App::import('Behavior', 'Webservice');

class TestWebserviceBehavior extends WebserviceBehavior {
	
	public function testXML(&$model, $input) {
		$this->_formatXmlData($input);
		$result = str_replace(array("\t", "\n"), array('', ''), $this->_data);
		return $result;
	}
}

/**
 * Base model that to load Webservice behavior on every test model.
 *
 * @package app.tests
 * @subpackage app.tests.cases.behaviors
 */
class WebserviceTestModel extends CakeTestModel
{
	/**
	 * Behaviors for this model
	 *
	 * @var array
	 * @access public
	 */
	var $actsAs = array('TestWebservice' => array('defaultUrl' => 'www.cakephp.org'));
	
	var $useTable = false;
}

/**
 * Model used in test case.
 *
 * @package	app.tests
 * @subpackage app.tests.cases.behaviors
 */
class Service extends WebserviceTestModel {
	/**
	 * Name for this model
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'Service';
}

class WebserviceTestCase extends CakeTestCase {
/**
 * Method executed before each test
 *
 * @access public
 */
	function startTest() {
		$this->Service =& new Service();
	}
	
	function testHeaderFormatting() {
		$this->Service->request('get', array('headers' => array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest')));
		$info = $this->Service->getRequestInfo();
		$this->assertPattern("/HTTP_X_REQUESTED_WITH: XMLHttpRequest\r\n/", $info['requestHeaders']);
		$this->assertPattern("/User-Agent: /", $info['requestHeaders']);
		$this->assertPattern("/Accept: /", $info['requestHeaders']);
		
		$this->Service->Behaviors->TestWebservice->userAgent = 'CakePHP WebService';
		$this->Service->Behaviors->TestWebservice->acceptTypes = 'text/html';
		$this->Service->request();
		$result = $this->Service->getRequestInfo();
		$this->assertPattern("/User-Agent: CakePHP WebService\r\n/", $result['requestHeaders']);
		$this->assertPattern("/Accept: text\/html/", $result['requestHeaders']);
	}
	
	function testGetRequest() {		
		$result = $this->Service->request();
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/CakePHP/', $result);
		$this->assertPattern('/<\/html>/', $result);
		
		$result = $this->Service->request('get');
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/CakePHP/', $result);
		$this->assertPattern('/<\/html>/', $result);
		
		$result = $this->Service->request('get', array('url' => 'www.google.com'));	
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/Google/', $result);
		$this->assertPattern('/<\/html>/', $result);
	
		$data = array('q' => 'cakePHP');
		$result = $this->Service->request('get', array('url' => 'www.google.com/search', 'data' => $data));
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/Google/', $result);
		$this->assertPattern('/cakephp.org/', $result);
		$this->assertPattern('/<\/html>/', $result);
	}
	
	function testPostRequest() {				
		$vars = array('data[User][username]' => 'test-account', 'data[User][psword]' => 'totally-wrong-password', 'data[User][redirect]' => '', '_method' => 'POST');
		$result = $this->Service->request('post', array('data' => $vars, 'url' => 'book.cakephp.org/users/login/'));
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/CakePHP/', $result);
		$this->assertPattern('/<\/html>/', $result);
		$this->assertPattern('/Login failed. Invalid username or password/', $result);
			
		$vars = array('param' => 'val ue', 'foo' => 'b>r');
		$this->Service->request('post', array('data' => $vars));				
		$info = $this->Service->getRequestInfo();
		$expected = 'param=val+ue&foo=b%3Er';
		$this->assertEqual($info['data'], $expected);
	}
	
	function testXmlRpcRequest() {
		//string and int types
		$vars = array(
			'methodName' => 'testFunc',
			'data' => array(
				'foo', 'bar', 1
			)
		);
		$result = $this->Service->testXml($vars);
		
		$expected = '<?xml version="1.0" encoding="UTF-8" ?><methodCall><methodName>testFunc</methodName><params><param><value><string>foo</string></value></param><param><value><string>bar</string></value></param><param><value><int>1</int></value></param></params></methodCall>';
		$this->assertEqual($result, $expected);
		
		//array
		$input = array(
			'methodName' => 'testFunc',
			'data' => array(
				array(6, 9, 4)
			)
		);
		$result = $this->Service->testXml($input);
		$expected = '<?xml version="1.0" encoding="UTF-8" ?><methodCall><methodName>testFunc</methodName><params><param><value><array><data><value><int>6</int></value><value><int>9</int></value><value><int>4</int></value></data></array></value></param></params></methodCall>';
		$this->assertEqual($result, $expected);

		// struct
		$input = array(
			'methodName' => 'testFunc',
			'data' => array(
				array('foo' => 'bar', 'two' => 9)
			)
		);
		$result = $this->Service->testXml($input);
		$expected = '<?xml version="1.0" encoding="UTF-8" ?><methodCall><methodName>testFunc</methodName><params><param><value><struct><member><name>foo</name><value><string>bar</string></value></member><member><name>two</name><value><int>9</int></value></member></struct></value></param></params></methodCall>';
		$this->assertEqual($result, $expected);
		
		// date
		$input = array(
			'methodName' => 'testFunc',
			'data' => array(
				array('type' => 'date', 'value' => '2005-06-12 12:30:30')
			)
		);
		$result = $this->Service->testXml($input);
		$expected = '<?xml version="1.0" encoding="UTF-8" ?><methodCall><methodName>testFunc</methodName><params><param><value><dateTime.iso8601>20050612T12:30:30</dateTime.iso8601></value></param></params></methodCall>';
		$this->assertEqual($result, $expected);	
		
	}
}




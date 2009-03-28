<?php

/**
* ProcessManager 
*
* A singleton Manager Class much like
* ConnectionManager
*
*/
class ProcessManager {

/**
 * Currently Running Processes
 *
 * @var array
 * @access protected
 **/
	var $_processes = array();

/**
 * Get Instance
 *
 * @return void
 **/	
	function &getInstance() {
		static $instance = array();
		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new ProcessManager();
		}
		return $instance[0];
	}

/**
 * Create a new Process
 *
 * @param string $command The command you wish to run
 * @param string $key optional key, if null, key will be slugged command name.
 * @param array $path Path the command is on.
 * @access public
 * @return object CakeProcess
 */
	function create($command, $key = null, $path = null) {
		$_this =& ProcessManager::getInstance();
		if (!$key) {
			$key = Inflector::slug($command);
		}
		if (!isset($_this->_processes[$key])) {
			$_this->_processes[$key] = new CakeProcess($command, $path);
		}
		return $_this->_processes[$key];
	}
	
/**
 * Kill A Process
 *
 * @param string $key Key name of process to kill.
 * @access public
 * @return void
 */
	function kill($key) {
		$_this =& ProcessManager::getInstance();
		if (isset($_this->_processes[$key])) {
			$_this->_processes[$key]->kill();
			unset($_this->_processes[$key]);
		}
		return false;
	}
/**
 * Get A Process Reference
 * 
 * @param string $key name of the process to get.
 * @return object 
 **/
	function &getProcess($key) {
		$false = false;
		$_this =& ProcessManager::getInstance();
		if (isset($_this->_processes[$key])) {
			$return =& $_this->_processes[$key];
			return $return;
		}
		return $false;
	}
	
/**
 * Get the names of all the running processes
 *
 * @access public
 * @return void
 */	
	function getKeys() {
		$_this =& ProcessManager::getInstance();
		return array_keys($_this->_processes);
	}
	
/**
 * killAll
 *
 * Kill All running processes.
 *
 * @access public
 * @return boolean
 */
	function killAll() {
		$_this =& ProcessManager::getInstance();
		$runningProcesses = $_this->getKeys();
		foreach ($runningProcesses as $name) {
			$_this->_processes[$name]->kill();
			unset($_this->_processes[$name]);
		}
		return true;
	}


}


/**
 * CakeProcess 
 *
 * Wraps a single forked Process and provides a way
 * to interact and introspect on it.
 *
 */
class CakeProcess extends Object {
	
	var $_process = null;
/**
 * stream spec for captured process
 *
 * @var string
 **/
	var $streamSpec = array (
		0 => array('pipe', 'r'), // STDIN 
		1 => array('pipe', 'w'), // STDOUT
		2 => array('pipe', 'w'), // STDERR
	);	
/**
 * File Stream handles
 *
 * @var array
 **/
	var $handles = array();
/**
 * Time to sleep between input and output default 50 u seconds
 *
 * @var int
 */
	var $sleepTime = 50;
/**
 * Start a sub process
 * @param string $command cake Console command i.e. cake bake
 * @param string $path Absolute Path to find the command on.  defaults to cake/console
 * @return void
 */
	function __construct($command, $path = null) {
		set_time_limit(0);
		$this->__initEnvironment();
		
		if (is_null($path)) {
			$path = ROOT . DS . CAKE . 'console' . DS;
		}
		
		$this->_process = proc_open($path . $command, $this->streamSpec, $this->handles, $this->wd, $this->env);
		if (is_resource($this->_process)) {
			stream_set_timeout($this->handles[1], 2);
			stream_set_blocking($this->handles[1], false);

			stream_set_blocking($this->handles[2], false);
			stream_set_timeout($this->handles[2], 2);
		}
		return true;
	}
	
/**
 * Set up the Terminal environment.
 *
 * @access public
 * @return void
 */	
	function __initEnvironment() {
		$this->wd = APP;
		$this->env['TERM'] = 'cons25';
	}
	
/**
 * Simulate an interactive process.  
 *
 * Supply all the answers for the prompts.
 *
 * @param array $answers Answers to the command's prompts in the order they are required
 * @return string output from interactive process
 **/
	function interactive($answers = array()) {
		$output = '';
		$output .= $this->getOutput();
		foreach ($answers as $response) {
			$output .= $response . "\n";
			$output .= $this->respond($response);
		}
		return $output;
	}
	
/**
 * Respond to a running Shell process
 *
 * @param string $response 
 * @return void string Next response from shell process.
 **/	
	function respond($response) {
		fwrite($this->handles[0], $response . "\n");
		usleep($this->sleepTime);
		return $this->getOutput();
	}
	
/**
 * Get the Output or Error if an error was raised.
 *
 * @return string Either output generated or error message
 **/
	function getOutput() {
		$output = '';
		while (!feof($this->handles[1])) {
			$output .= fread($this->handles[1], 8192);
			if (substr_count($output, '>') > 0) {
				break;
			}
		}
		
		$error = '';
		while (!feof($this->handles[2])) {
			$err = fread($this->handles[2], 8192);
			if ($err == '') {
				break;
			}
			$error .= $err;
		}
		if (!empty($error)) {
			return $error;
		}
		return $output;
	}
/**
 * Get the status of a process
 *
 * @access public
 * @return mixed Returns false in PHP4 and an array of process meta data in PHP5
 */
	function status() {
		if (PHP5) {
			return proc_get_status($this->_process);
		}
		return false;
	}

/**
 * Kill a process
 * If PHP5 insta-kills process with proc_terminate
 *
 * @return bool
 **/	
	function kill() {
		if (PHP5 && is_resource($this->_process)) {
			proc_terminate($this->_process);
		}
		return $this->__destruct();
	}
	
/**
 * destructor
 *
 * @return bool
 **/
	function __destruct() {
		if (is_resource($this->_process)) {
			fclose($this->handles[0]);
			fclose($this->handles[1]);
			fclose($this->handles[2]);
			return proc_close($this->_process);
		}
		return true;
	}
	
}
?>
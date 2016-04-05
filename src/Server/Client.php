<?php

namespace Dream\Daemon\Server;

use Dream\Daemon\Socket;
use Dream\Daemon\Exception;

class Client {
	
	private $_socket;
	
	public function __construct($client, $key = NULL) {
		
		$this->_socket = new Socket($client, $key);
		
	}
	
	public function write($data) {
		
		return $this->_socket->write($data);
		
	}

	public function listen($timeout = 5, $expect = 0) {
		
		return $this->_socket->listen($timeout, $expect);
		
	}
	
}


<?php

/**
 * This file is part of Dream\Daemon, a simple way to create socket server/client in PHP.
 */

namespace Dream\Daemon;

use Dream\Daemon\Exception;
use Dream\Daemon;

class Server {
	
	private $_socket;
	
	private $_key;
	
	public function __construct($daemonName, $key = NULL) {
		
		if (!preg_match('/^[a-z0-9]+$/', $daemonName)) {
			throw new Exception('Daemon name can only be lowercase alnum chars');
		}
		
		$socketPath = APPLICATION_PATH . '/../temp/sockets/dream-daemon-' . $daemonName;
		
		// Clear the socket file if it exists
		@unlink($socketPath);

		// Create a unix socket server
		$this->_socket = stream_socket_server('unix://' . $socketPath, $errno, $errstr) or die($errstr);
		
		// Allow www-data group to read and write
		chgrp($socketPath, 'www-data');
		chmod($socketPath, 0760);
		
		// Keep key private
		$this->_key = $key;
		
	}
	
	public function accept() {
		
		$client = stream_socket_accept($this->_socket, -1);
		
		if ($client) {
			return new Daemon\Client($client, $this->_key);
		}
		
		return NULL;
		
	}

}


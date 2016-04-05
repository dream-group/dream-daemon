<?php

/**
 * This file is part of Dream\Daemon, a simple way to create socket server/client in PHP.
 */

namespace Dream\Daemon;

use Dream\Daemon\Socket;
use Dream\Daemon\Exception;

class Client {
	
	private static $_sockets = [];
	
	private static function _getSocket($daemonName, $key = NULL) {
		
		if (!preg_match('/^[a-z0-9]+$/', $daemonName)) {
			throw new Exception('Daemon name can only be lowercase alnum chars');
		}
				
		if (empty(self::$_sockets[$daemonName])) {
			self::$_sockets[$daemonName] = Socket::factory('unix://' . APPLICATION_PATH . '/../temp/sockets/dream-daemon-' . $daemonName, $key);
		}
		
		return self::$_sockets[$daemonName];
		
	}
	
	public static function write($daemonName, $data, $key = NULL) {

		return self::_getSocket($daemonName, $key)->write($data);
		
	}
	
	public static function listen($daemonName, $timeout = 30, $expect = 1, $key = NULL) {
		
		return self::_getSocket($daemonName, $key)->listen($timeout, $expect);
		
	}
	
}


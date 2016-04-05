<?php

/**
 * This file is part of Dream\Daemon, a simple way to create socket server/client in PHP.
 */

namespace Dream\Daemon;

use Dream\Daemon\Exception;

class Socket {

	private $_sock;
	private $_key;
	
	const STX = "\x02";
	const ETX = "\x03";
	const ACK = "\x06";
	
	private $_buffer = '';
	
	public static function factory($socketName, $key = NULL) {
		
		$sock = stream_socket_client(
			$socketName,
			$errorCode, $errorString,
			30, // connection timeout
			STREAM_CLIENT_CONNECT
		);
		
		return new self($sock, $key);
		
	}
	
	public function __construct($sock, $key = NULL) {
		
		$this->_sock = $sock;
		$this->_key  = $key;
		
		stream_set_blocking($this->_sock, 1); // blocks
		stream_set_timeout($this->_sock, 0, 10000); // 10 ms
		
	}
	
	public function __destruct() {

		if (is_resource($this->_sock)) {
			fclose($this->_sock);
		}

	}

	public function write($data) {
		
		if (!is_string($data)) {
			throw new Exception('Only accepts strings as data');
		}
		
		// Wrap the data to form a packet
		$data = self::STX . $data . self::ACK . md5($this->_key . $data) . self::ETX;
		
		// Writing to a network stream may end before the whole string is written
		for ($written = 0; $written < strlen($data); $written += $fwrite) {
			
			// Attempt to write the remaining bytes; get the nr of bytes actually written
			$fwrite = fwrite($this->_sock, $w = substr($data, $written));
			
			// MUST check the lenght written, as fwrite will return false
			// only when argumetns are not proper. In all other errors (broken pipe etc.)
			// it will usually return (int) 0
			if ($fwrite === false || $fwrite != strlen($w)) {
				throw new Exception('Error writing data to the socket');
			}
			
		}
		
		return $written - 3 - 32; // disregard the tabs and signature
		
	}
	
	public function listen($timeout = 30, $expect = 1) {
		
		$packets = array();
		
		// Compile the regex for matching individual packets and the remainders
		$pattern = '/'.preg_quote(self::STX).'.+'.preg_quote(self::ACK).'[a-z0-9]{32}'.preg_quote(self::ETX).'/Ums';
		
		// Start listeing
		$begin = time();
		while (true) {
			
			// Read for some data to the buffer (read sleeps)
			try {
				$this->_buffer .= $this->_read();
			} catch (Exception $e) { 
				/* connection was probably lost */
			}
			
			// Look for the first message packet in the beginning of the buffer
			while (preg_match($pattern, $this->_buffer, $matches, PREG_OFFSET_CAPTURE)) {
				
				// Get the matched packet & offset
				list($packet, $offset) = $matches[0];
				
				// Shift the matched packet off the buffer, discarding the leading part
				$this->_buffer = substr($this->_buffer, $offset + strlen($packet));
				
				// Dismiss the STX & ETX
				$packet = substr($packet, 1, -1);
				
				// Get the real packet and signature
				list($packet, $signature) = explode(self::ACK, $packet);

				// If signature is invalid
				if (md5($this->_key . $packet) != $signature) {
					throw new Exception('Invalid signature!');
				}
				
				// Stack the packet
				$packets[] = $packet;
				
			}
			
			// If we have satisfied the criteria
			if (count($packets) >= $expect) {
				return $packets;
			}
			
			// If timeout reached, stop the loop
			if ($timeout > 0 && time() > ($begin + $timeout)) {
				break;
			}
			
		}
		
		if (count($packets) < $expect) {
			throw new Exception('Expected number of packets was not received!');
		}
		
		return $packets;
		
	}
	
	private function _read() {
		
		// Reads in the stream until the timeout occurs
		// or connection is closed by the remote party
		$data = stream_get_contents($this->_sock);
		
		// If empty string and connection appears to be closed
		if ($data === '' && feof($this->_sock)) {
			throw new Exception('Connection was lost');
		}
		
		return $data;
		
	}
	
	
}
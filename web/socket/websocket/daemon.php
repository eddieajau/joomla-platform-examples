<?php
/**
 * @package     Joomla.Platform
 * @subpackage  WebSocket
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access
defined('_JEXEC') or die;

/**
 * WebSocket implements the basic websocket protocol handling initial handshaking and also
 * dispatching requests up to the clients bound to the socket.
 */
class JWebSocketDaemon
{
	private $_listeningAddress;
	private $_listeningPort;

	private static $_instance;

	private $_users = array();

	private $_sockets = array();

	private $_controller;

	protected function __construct($address, $port, JWebSocketController $controller)
	{
		$this->_controller = $controller;
		$this->_listeningAddress = $address;
		$this->_listeningPort = $port;
	}

	protected function connect($socket)
	{
		$user = new JWebSocketUser;
		$user->setSocket($socket);
		array_push($this->_users, $user);
		array_push($this->_sockets, $socket);
	}

	public function disconnect($socket)
	{
		JLog::add('Disconnecting ' . $socket, JLog::INFO, 'WebSocket');
		$found = null;
		$n = count($this->_users);
		for ($i = 0; $i < $n; $i++)
		{
			if ($this->_users[$i]->socket() == $socket)
			{
				$found = $i;
				break;
			}
		}
		if (!is_null($found))
		{
			array_splice($this->_users, $found, 1);
		}
		$index = array_search($socket, $this->_sockets);
		socket_close($socket);
		if ($index >= 0)
		{
			array_splice($this->_sockets, $index, 1);
		}
	}

	protected function doHandshake($socket, JWebSocketUser $user)
	{
		JLog::add($socket . ' performing handshake', JLog::INFO, 'WebSocket');
		$bytes = @socket_recv($socket, $buffer, 2048, 0);
		if ($bytes == 0)
		{
			JWebSocketDaemon::getInstance()->disconnect($socket);
			JLog::add($socket . ' DISCONNECTED!', JLog::INFO, 'WebSocket');
			return;
		}

		$headers = $this->parseHeaders($buffer);
		if (count($headers) == 0 || !isset($headers['Upgrade']))
		{
			// Not good send back an error status
			$this->sendFatalErrorResponse();
		}
		else
		{
			if (strtolower($headers['Upgrade']) != 'websocket')
			{
				$this->sendFatalErrorResponse();
			}
			// now get the handshaker for this request
			$hs = $this->getHandshake($headers);
			if (!$hs->dohandshake($user, $headers))
			{
				throw new RuntimeException('Handshake failed');
			}
			JLog::add($socket . ' Handshake Done', JLog::INFO, 'WebSocket');
		}
	}

	/**
	 * Looks at the headers to determine which handshaker to
	 * use
	 * $headers are the headers in the request
	 */
	private function getHandshake($headers)
	{
		// Lets check which handshaker we need
		if (isset($headers['Sec-WebSocket-Version']))
		{
			if ($headers['Sec-WebSocket-Version'] === '8')
			{
				// This is the HyBI handshaker
				return new JWebSocketHandshakeHyBi();
			}
			// Not a version we support
			$this->sendFatalErrorResponse();
		}
		else if (isset($headers['Sec-WebSocket-Key1']) && isset($headers['Sec-WebSocket-Key2']))
		{
			// Draft 76
			return new JWebSocketHandshake76();
		}
		// Must be draft 75

		return new JWebSocketHandshake75();
	}

	public static function getInstance($address, $port, JWebSocketController $controller)
	{
		if (!(self::$_instance instanceof JWebSocketDaemon))
		{
			self::$_instance = new JWebSocketDaemon($address, $port, $controller);
		}

		return self::$_instance;
	}

	/**
	 * Creates and returns the socket on which the server will listen
	 * $address is the address at which the server is listening
	 * $port is the port at which the server is listening
	 */
	protected function getSocket($address, $port)
	{
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!is_resource($socket))
		{
			JLog::add('socket_create() failed', JLog::ERROR, 'WebSocket');
			throw new RuntimeException('socket_create() failed');
		}

		if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))
		{
			JLog::add('socket_option() failed', JLog::ERROR, 'WebSocket');
			throw new RuntimeException('socket_option() failed');
		}

		if (!socket_bind($socket, $address, $port))
		{
			JLog::add('socket_bind() failed', JLog::ERROR, 'WebSocket');
			throw new RuntimeException('socket_bind() failed');
		}

		if (!socket_listen($socket, 20))
		{
			JLog::add('socket_listen() failed', JLog::ERROR, 'WebSocket');
			throw new RuntimeException('socket_listen() failed');
		}

		return $socket;
	}

	protected function getUserBySocket($socket)
	{
		$found = null;
		foreach ($this->_users as $user)
		{
			if ($user->socket() == $socket)
			{
				$found = $user;
				break;
			}
		}
		return $found;
	}

	/**
	 * Entry point for all client requests. This function
	 * determines if handshaking has been done and if not selects the
	 * specific handshaking protocol and invokes it.
	 *
	 * If handshaking has been done this function dispatches the request
	 * to the service bound to the request associated with the user object
	 */
	function handleRequest($socket, JWebSocketUser $user)
	{
		// Check the handshake required
		if (!$user->handshakeDone())
		{
			$this->doHandshake($socket, $user);
		}

		try
		{
			$protocol = $user->protocol();
			if (isset($protocol))
			{
				$protocol->setSocket($socket);
				$result = $protocol->read();
				$bytesRead = $result['size'];

				if ($bytesRead !== -1 && $bytesRead !== -2)
				{
					$message = json_encode($this->_controller->onMessage($result));
					$protocol->send(array('size' => strlen($message), 'frame' => $message));
				}
				else
				{
					$this->_controller->onError();
					// badness must close
					$protocol->close();
					$this->disconnect($socket);

					return;
				}
			}
			else
			{
				$this->sendFatalErrorResponse();
				return;
			}
		}
		catch (WSClientClosedException $e)
		{
			$this->_controller->onClose();
		}
	}

	public function start()
	{
		$master = $this->getSocket($this->_listeningAddress, $this->_listeningPort);

		JLog::add('> Server started : ' . date('Y-m-d H:i:s'), JLog::INFO, 'WebSocket');
		JLog::add('> Master socket  : ' . $master, JLog::INFO, 'WebSocket');
		JLog::add('> Listening on   : ' . $this->_listeningAddress . ' port ' . $this->_listeningPort, JLog::INFO, 'WebSocket');

		$this->_sockets = array(
			$master
		);
		$users = array();

		// The main process
		while (true)
		{
			$changed = $this->_sockets;
			socket_select($changed, $write = NULL, $except = NULL, NULL);
			foreach ($changed as $socket)
			{
				try
				{
					if ($socket == $master)
					{
						$client = socket_accept($master);
						if ($client < 0)
						{
							JLog::add('socket_accept() failed', JLog::ALERT, 'WebSocket');
							continue;
						}
						else
						{
							$this->connect($client);
							JLog::add($client . ' CONNECTED', JLog::INFO, 'WebSocket');
						}
					}
					else
					{
						JLog::add($client . ': Processing request', JLog::INFO, 'WebSocket');
						$user = $this->getUserBySocket($socket, $users);
						$this->handleRequest($socket, $user);
					}
				}
				catch (Exception $e)
				{
					JLog::add($socket.' disconnected', JLog::ALERT, 'WebSocket');
					echo "\n".$e->getMessage();
					$this->disconnect($socket);
				}
			}
		}
	}

	protected function parseHeaders($headers = false)
	{
		if ($headers === false)
		{
			return false;
		}
		$statusDone = false;
		$headers = str_replace("\r", "", $headers);
		$headers = explode("\n", $headers);
		foreach ($headers as $value)
		{
			$header = explode(": ", $value);
			if (count($header) == 1)
			{
				// if($header[0] && !$header[1]){
				if (!$statusDone)
				{
					$headerdata['status'] = $header[0];
					$statusDone = true;
				}
				else
				{
					$headerdata['body'] = $header[0];
					//return $headerdata;
				}
			}
			elseif ($header[0] && $header[1])
			{
				$headerdata[$header[0]] = $header[1];
			}
		}

		var_dump($headerdata);
		return $headerdata;
	}

	/**
	 * Takes the appropriate action to close the connection down
	 */
	private function sendFatalErrorResponse()
	{
		// Just close the socket if in handhake mode
		if (!$user->handshakeDone())
		{
			JWebSocketDaemon::getInstance()->disconnect($user->socket());
			return;
		}
		else
		{
			//send a status code and then close
		}
	}

}

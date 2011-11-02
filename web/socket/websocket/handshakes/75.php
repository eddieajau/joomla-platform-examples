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
 * A class to implement version 75 of the handshake
 */
class JWebSocketHandshake75 implements JWebSocketHandshake
{
	/**
	 * Perform the handshake
	 * $user - The user/client that requests the websocket connection
	 * $headers - an array containing the HTTP headers sent
	 */
	function doHandshake(JWebSocketUser $user, $headers)
	{
		$origin = $headers['Origin'];
		$host = $headers['Host'];
		$status = $headers['status'];
		$statusFields = explode(' ', $status);
		$resource = $statusFields[1];

		$upgrade = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" . "Upgrade: WebSocket\r\n" . "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Protocol: " . $app . "\r\n" . "Sec-WebSocket-Origin: " . $origin . "\r\n" . "Sec-WebSocket-Location: ws://" . $host
			. $statusFields[1] . "\r\n" . "\r\n" . "\r\n";

		socket_write($user->socket(), $upgrade, strlen($upgrade));
		$user->setHandshakeDone();
		$user->setProtocol(new JWebSocketProtocol76);

		return true;
	}
}

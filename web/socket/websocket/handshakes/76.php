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
 * Class that implements an 76 handshake
 */
class JWebSocketHandshake76 implements JWebSocketHandshake
{
	/**
	 * Perform the handshake
	 *
	 * $user - the user/client that initiated connection
	 * $headers - an array of HTTP headers sent by the user
	 */
	function doHandshake(JWebSocketUser $user, $headers)
	{
		// Grab the security keys
		$strkey1 = $headers['Sec-WebSocket-Key1'];
		$strkey2 = $headers['Sec-WebSocket-Key2'];

		// Grab the other items needed to reply
		$data = $headers['body'];
		$origin = $headers['Origin'];
		$host = $headers['Host'];
		$status = $headers['status'];
		$statusFields = explode(' ', $status);
		$resource = $statusFields[1];

		// Compute the hash from the keys provided
		$pattern = '/[^\d]*/';
		$replacement = '';
		$numkey1 = preg_replace($pattern, $replacement, $strkey1);
		$numkey2 = preg_replace($pattern, $replacement, $strkey2);

		$pattern = '/[^ ]*/';
		$replacement = '';
		$spaces1 = strlen(preg_replace($pattern, $replacement, $strkey1));
		$spaces2 = strlen(preg_replace($pattern, $replacement, $strkey2));

		if ($spaces1 == 0 || $spaces2 == 0 || fmod($numkey1, $spaces1) != 0 || fmod($numkey2, $spaces2) != 0)
		{
			echo ("failed handshake\n");
			return false;
		}

		$ctx = hash_init('md5');

		// Pack the has for tranmission
		hash_update($ctx, pack("N", $numkey1 / $spaces1));
		hash_update($ctx, pack("N", $numkey2 / $spaces2));
		hash_update($ctx, $data);
		$hash_data = hash_final($ctx, true);

		// Send the upgrade response
		if (isset($headers['Sec-WebSocket-Protocol']))
		{
			$upgrade = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" . "Upgrade: WebSocket\r\n" . "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Protocol: " . $app . "\r\n" . "Sec-WebSocket-Origin: " . $origin . "\r\n" . "Sec-WebSocket-Location: ws://" . $host
				. $statusFields[1] . "\r\n" . "\r\n" . $hash_data;
		}
		else
		{
			$upgrade = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" . "Upgrade: WebSocket\r\n" . "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Origin: " . $origin . "\r\n" . "Sec-WebSocket-Location: ws://" . $host . $statusFields[1] . "\r\n" . "\r\n"
				. $hash_data;

		}

		socket_write($user->socket(), $upgrade, strlen($upgrade));
		$user->setHandshakeDone();
		// $user->setTranscoder(new BasicTranscoder());
		$user->setProtocol(new JWebSocketProtocol76());
		return true;
	}
}

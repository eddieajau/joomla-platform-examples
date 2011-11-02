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

class JWebSocketUser
{
	private $id;
	private $socket;
	private $handshake = false;
	private $transcoder;
	private $protocol;

	/**
	 * Class Constructor for the WsUser Object
	 *
	 */
	public function __construct()
	{
		$this->id = uniqid();
	}

	function id()
	{
		return $this->id;
	}

	function setSocket($socket)
	{
		$this->socket = $socket;
	}

	function socket()
	{
		return $this->socket;
	}

	function setHandshakeDone()
	{
		$this->handshake = true;
	}

	function handshakeDone()
	{
		return $this->handshake;
	}

	function setTranscoder(MessageTranscoder $transcoder)
	{
		$this->transcoder = $transcoder;
	}

	function transcoder()
	{
		return $this->transcoder;
	}

	function setProtocol(JWebSocketProtocol $protocol)
	{
		$this->protocol = $protocol;
	}

	function protocol()
	{
		return $this->protocol;
	}
}

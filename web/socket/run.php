#!/usr/bin/php
<?php
/**
 * A "hello world" command line application built on the Joomla Platform.
 *
 * To run this example, adjust the executable path above to suite your operating system,
 * make this file executable and run the file.
 *
 * Alternatively, run the file using:
 *
 * php -f run.php
 *
 * @package    Joomla.Examples
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// We are a valid Joomla entry point.
// This is required to load the Joomla Platform import.php file.
define('_JEXEC', 1);

// Increase error reporting to that any errors are displayed.
// Note, you would not use these settings in production.
error_reporting(E_ALL);
ini_set('display_errors', true);

// Setup the base path related constant.
// This is one of the few, mandatory constants needed for the Joomla Platform.
define('JPATH_BASE', dirname(__FILE__));

// Bootstrap the application.
require dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';

// Import the JCli class from the platform.
jimport('joomla.application.cli');

require 'websocket/import.php';

/**
 * A "hello world" command line application class.
 *
 * Simple command line applications extend the JCli class.
 *
 * @package  Joomla.Examples
 * @since    11.3
 */
class SocketServer extends JCli implements JWebSocketController
{
	private $_protocol;

	/**
	 * Execute the application.
	 *
	 * The 'execute' method is the entry point for a command line application.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 * @throws  RuntimeException
	 */
	public function doExecute()
	{
		// Send a string to standard output.
		$this->out('Starting server...');

		$host = '127.0.0.1';
		$port = 8080;

		JWebSocketDaemon::getInstance($host, $port, $this)->start();
	}

	function onClose()
	{
		$this->out('Socket connection closing.');
	}

	function onError($err)
	{
		$this->out('Socket error.');
	}

	function onMessage($msg)
	{
		$size = $msg['size'];
		$data = $msg['frame'];
		return 'Pong';
	}

	function setProtocol(JWebSocketProtocol $protocol)
	{
		$this->_protocol = $protocol;
	}
}

// Wrap the execution in a try statement to catch any exceptions thrown anywhere in the script.
try
{
	// Instantiate the application object, passing the class name to JCli::getInstance
	// and use chaining to execute the application.
	JCli::getInstance('SocketServer')->execute();
}
catch (Exception $e)
{
	// An exception has been caught, just echo the message.
	fwrite(STDOUT, $e->getMessage() . "\n");
	exit($e->getCode());
}

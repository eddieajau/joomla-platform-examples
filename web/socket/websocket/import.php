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

// Concrete classes.
JLoader::register('JWebSocketDaemon', __DIR__.'/daemon.php');
JLoader::register('JWebSocketUser', __DIR__.'/user.php');

// Interfaces.
JLoader::register('JWebSocketController', __DIR__.'/controller.php');
JLoader::register('JWebSocketHandshake', __DIR__.'/handshake.php');
JLoader::register('JWebSocketProtocol', __DIR__.'/protocol.php');

// Adapters.
JLoader::discover('JWebSocketHandshake', __DIR__.'/handshakes');
JLoader::discover('JWebSocketProtocol', __DIR__.'/protocols');

#!/usr/bin/php
<?php
/**
 * An example command line application built on the Joomla Platform.
 *
 * To run this example, adjust the executable path above to suite your operating system,
 * make this file executable and run the file.
 *
 * Alternatively, run the file using:
 *
 * php -f run.php
 *
 * Note, this application requires configuration.php and the connection details
 * for the database may need to be changed to suit your local setup.
 *
 * @package    Joomla.Examples
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// We are a valid Joomla entry point.
define('_JEXEC', 1);

// Setup the base path related constant.
define('JPATH_BASE', dirname(__FILE__));

// Bootstrap the application.
require dirname(dirname(dirname(__FILE__))).'/bootstrap.php';

// Import the JCli class from the platform.
jimport('joomla.application.cli');

/**
 * An example command line application class.
 *
 * This application shows how to build an application that could serve as a cron manager
 * that makes use of Joomla plugins.
 *
 * @package  Joomla.Examples
 * @since    11.3
 */
class CronPluginApp extends JCli
{
	/**
	 * A database object for the application to use.
	 *
	 * @var    JDatabase
	 * @since  11.3
	 */
	protected $dbo = null;

	/**
	 * Class constructor.
	 *
	 * This constructor invokes the parent JCli class constructor,
	 * and then creates a connector to the database so that it is
	 * always available to the application when needed.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function __construct()
	{
		// Call the parent __construct method so it bootstraps the application class.
		parent::__construct();

		//
		// Prepare the logger.
		//

		// Include the JLog class.
		jimport('joomla.log.log');

		// Get the date so that we can roll the logs over a time interval.
		$date = JFactory::getDate()->format('Y-m-d');

		// Add the logger.
		JLog::addLogger(
			// Pass an array of configuration options.
			// Note that the default logger is 'formatted_text' - logging to a file.
			array(
				// Set the name of the log file.
		        'text_file' => 'cron.'.$date.'.php',
		        // Set the path for log files.
		        'text_file_path' => __DIR__.'/logs'
			)
		);

		//
		// Prepare the database connection.
		//

		jimport('joomla.database.database');

		// Note, this will throw an exception if there is an error
		// creating the database connection.
		$this->dbo = JDatabase::getInstance(
			array(
				'driver' => $this->get('dbDriver'),
				'host' => $this->get('dbHost'),
				'user' => $this->get('dbUser'),
				'password' => $this->get('dbPass'),
				'database' => $this->get('dbName'),
				'prefix' => $this->get('dbPrefix'),
			)
		);
	}

	/**
	 * Custom doExecute method.
	 *
	 * This method loads a list of the published plugins from the 'cron' group,
	 * then loads the plugins and registers them against the 'doCron' event.
	 * The event is then triggered and results logged.
	 *
	 * Any configuration for the cron plugins is done via the Joomla CMS
	 * administrator interface and plugin parameters.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */
	public function doExecute()
	{
		//
		// Check we have some critical information.
		//

		if (!defined('JPATH_PLUGINS') || !is_dir(JPATH_PLUGINS))
		{
			throw new Exception('JPATH_PLUGINS not defined');
		}

		// Add a start message.
		JLog::add('Starting cron run.');

		//
		// Prepare the plugins
		//

		// Get the quey builder class from the database.
		$query = $this->dbo->getQuery(true);

		// Get a list of the plugins from the database.
		$query->select('p.*')
			->from('#__extensions AS p')
			->where('p.enabled = 1')
			->where('p.type = '.$this->dbo->quote('plugin'))
			->where('p.folder = '.$this->dbo->quote('cron'))
			->order('p.ordering');

		// Push the query builder object into the database connector.
		$this->dbo->setQuery($query);

		// Get all the returned rows from the query as an array of objects.
		$plugins = $this->dbo->loadObjectList();

		// Log how many plugins were loaded from the database.
		JLog::add(sprintf('.loaded %d plugin(s).', count($plugins)));

		// Loop through each of the results from the database query.
		foreach ($plugins as $plugin)
		{
			// Build the class name of the plugin.
			$className = 'plg'.ucfirst($plugin->folder).ucfirst($plugin->element);
			$element = preg_replace('#[^A-Z0-9-~_]#i', '', $plugin->element);

			// If the class doesn't already exist, try to load it.
			if (!class_exists($className))
			{
				// Compute the path to the plugin file.
				$path = sprintf(rtrim(JPATH_PLUGINS, '\\/').'/cron/%s/%s.php', $element, $element);

				// Check if the file exists.
				if (is_file($path))
				{
					// Include the file.
					include $path;

					// Double check if we have a valid class.
					if (!class_exists($className))
					{
						// Log a warning and contine to the next record.
						JLog::add(sprintf('..plugin class for `%s` not found in file.', $element), JLog::WARNING);
						continue;
					}
				}
				else
				{
					// Log a warning and contine to the next record.
					JLog::add(sprintf('..plugin file for `%s` not found.', $element), JLog::WARNING);
					continue;
				}
			}

			JLog::add(sprintf('..registering `%s` plugin.', $element));

			// Register the event.
			$this->registerEvent(
				// Register the event name.
				'doCron',
				// Create and register the event plugin.
				new $className(
					$this->dispatcher,
					array(
						'params' => new JRegistry($plugin->params)
					)
				)
			);
		}

		//
		// Run the cron plugins.
		//
		// Each plugin should have been installed in the Joomla CMS site
		// and must include a 'doCron' method. Configuration of the plugin
		// is done via plugin parameters.
		//

		JLog::add('.triggering `doCron` event.');

		// Trigger the event and let the Joomla plugins do all the work.
		$results = $this->triggerEvent('doCron');

		// Log the results.
		foreach ($this->triggerEvent('doCron') as $result)
		{
			JLog::add(sprintf('..plugin returned `%s`.', var_export($result, true)));
		}

		JLog::add('Finished cron run.');
	}
}

// Wrap the execution in a try statement to catch any exceptions thrown anywhere in the script.
try
{
	// Instantiate the application object, passing the class name to JCli::getInstance
	// and use chaining to execute the application.
	JCli::getInstance('CronPluginApp')->execute();
}
catch (Exception $e)
{
	// An exception has been caught, echo the message.
	fwrite(STDOUT, $e->getMessage() . "\n");
	exit($e->getCode());
}

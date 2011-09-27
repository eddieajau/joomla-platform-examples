Joomla Platform Examples
========================

These examples are provided to help you learn how to structure and build applications with the Joomla Platform.

Installation
============

Clone or download the https://github.com/joomla/joomla-platform and
https://github.com/joomla/joomla-platform-examples under the same parent folder.
Then copy the ``bootstrap.dist.php`` file to ``bootstrap.php``.

Running Examples
================

All the examples in the ``cli`` folder are run from the command line or terminal.
All you need is PHP configured to run from the command line.

All the examples in the ``web`` folder are run through the browser. The easiest way to set
these up is to soft link or completely copy the folder into your web server document root.

Command Line Applications
=========================

The examples found in the ``cli`` folder are all based on the new ``JCli`` class.
The is a base level class purpose built for running applications from the command line.

101-hello-world
---------------

This is a simple example application that outputs "Hello World" to the terminal.

argv
----

This application shows how to access command line arguments.

changelog
---------

This application builds the HTML version of the Joomla Platform change log from the Github API
that is used in news annoucements. It provides an example of how to use the ``JHttp`` class.

cron-plugins
------------

This application show you how you could use JCli to build a cron manager for Joomla CMS plugins.
The plugins would be configured via parameters in the CMS itself, but run via this command line
application. It makes use of JLog for logging activity in rolling daily log files. The
application would simply be added to any available scheduling software and run at appropriate
intervals.

While this example shows how to run all the plugins at the same time, it would not be difficult
to add an additional database table to support staggered running of individual plugins.

database
--------

This application shows how to override the constructor and connect to the database.

show-config
-----------

Web Applications
================

The examples found in the ``web`` folder are all based on the new ``JWeb`` class.
This is a base level class purpose built to serve web content for a range of applications
including, but not limited to, web services, standalone web sites and CMS's.

101-hello-www
-------------

This is a simple web example that outputs "Hello WWW" to the browser.

detect-client
-------------

This is a simple web example that shows you how to detect the client properties (browser, platform, etc)
that your application is being viewed in.

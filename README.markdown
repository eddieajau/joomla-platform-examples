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

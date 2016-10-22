.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _config_options:


Configuration options
=====================

We have provided an editor for the configuration file settings:

.. figure:: ../../Images/Config/typo3_backend_01.png
	:width: 350px
	:align: left
	:alt: Backend settings editor

Runtime
-------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Disable output                 | | Is kreXX actually active?                                                                                                            | - true                    |
|                                | | Here you can disable kreXX on a global level without uninstalling it.                                                                | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| IP Range                       | | IP whitelist (comma separated) with wildcard (*) support.                                                                            | 192.168.0.110,192.168.1.* |
|                                | | List of IPs who can trigger kreXX. You can do something like '192.168.1.*', but not something like '192.168.*.110'.                  |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Try to detect AJAX requests    | | Shall kreXX try to detect an AJAX request?                                                                                           | - true                    |
|                                | | If set to true, there will be no output when AJAX is detected, to prevent a js error.                                                | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum nesting level          | | How deep shall we analyse objects?                                                                                                   | 5                         |
|                                | | When kreXX reaches a certain level, it simply stops there and won’t go any further.                                                  |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum amount of calls        | | How often can you call kreXX in one run?                                                                                             | 10                        |
|                                | | kreXX can generate a lot of data, and depending on your settings increasing this number might not be a good idea.                    |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Output
------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Skin                           | You can change the CSS/HTML skin. We included the Hans and Smoky-Grey skin                                                             | - hans                    |
|                                |                                                                                                                                        | - smoky-grey              |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Destination                    | Will the output be sent to the frontend or the logfolder?                                                                              | - file                    |
|                                |                                                                                                                                        | - frontend                |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum files in the logfolder | How many files should it keep? Files will only get deleted, when a new one is created.                                                 | 10                        |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Properties
----------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Analyse protected properties   | | Shall kreXX create a reflection and poll it for data?                                                                                | - true                    |
|                                | | kreXX will analyse all protected properties of a class.                                                                              | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse private properties     | | The same as :literal:`Analyse protected properties`, only for private properties.                                                    | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse constants              | | kreXX will analyse all pconstants of a class.                                                                                        | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse traversable data       | Shall kreXX try to traverse through the object?                                                                                        | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Methods
-------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Analyse protected methods      | Shall kreXX analyse all protected methods of an object?                                                                                | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
|Analyse private methods         | Shall kreXX analyse all private methods of an object?                                                                                  | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| List of debug methods to poll  | | Which additional methods shall be called from the object?                                                                            | debug,toArray,__toString, |
| for data                       | | Comma separated list of methods. These methods are called without parameters. They also might do stuff which might be unwanted.      | toString                  |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Error Handling and Backtrace
----------------------------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Register fatal error handler   | When set to "true", kreXX will register the handler as soon as it's loaded. When a fatal error occurs, kreXX will offer a backtrace    | - true                    |
| automatically                  | and an analysis of all objects in it. PHP always clears the stack in case of a fatal error, so kreXX has to keep track of it.          | - false                   |
|                                | Be warned: This option will dramatically slow down your requests.                                                                      |                           |
|                                |                                                                                                                                        |                           |
|                                | | Use this only when you have to. It is much better to register the error handler yourself with                                        |                           |
|                                | | :literal:`\krexx::registerFatal();`                                                                                                  |                           |
|                                | | and later unregister it with                                                                                                         |                           |
|                                | | :literal:`\krexx::unregisterFatal();`                                                                                                |                           |
|                                | | to prevent a slowdown.                                                                                                               |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


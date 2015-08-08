.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _config-options:


Configuration options
=====================

You do not have to configure kreXX. It works very well out of the box.

These settings can be used in the configuration file editor, as well as in the configuration cookie editor.

Render
------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Skin                           | You can change the CSS/HTML skin. We only included the Hans skin                                                                       | - hans                    |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Minimum amout of memory [MB]   | kreXX checks regularly how much memory is left. Here you can adjust the amount where it will trigger an emergengy break. Unit of       | 64                        |
|                                | measurement is MB.                                                                                                                     |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum Runtime [Seconds]      | kreXX checks during the analysis how much time has elapsed since start. Here you can adjust how many seconds can pass until an         | 60                        |
|                                | emergency break will be triggered. Unit of measurement is seconds.                                                                     |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Logging
-------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Folder                         | Where shall kreXX save it's logfiles?                                                                                                  | log                       |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum files in the logfolder | How many files should it keep? Files will only get deleted, when a new one is created.                                                 | 10                        |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


Output
------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Destination                    | Will the output be sent to the frontend or the logfolder?                                                                              | - file                    |
|                                |                                                                                                                                        | - frontend                |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum amount of calls        | | How often can you call kreXX in one run?                                                                                             | 10                        |
|                                | kreXX can generate a lot of data, and depending on your settings increasing this number might not be a good idea.                      |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Disable output                 | | Is kreXX actually active?                                                                                                            | - true                    |
|                                | Here you can disable kreXX on a global level without uninstalling it.                                                                  | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Try to detect AJAX requests    | | Shall kreXX try to detect an AJAX request?                                                                                           | - true                    |
|                                | If set to true, there will be no output when AJAX is detected, to prevent a js error.                                                  | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Deep
----
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Analyse protected properties   | | Shall kreXX create a reflection and poll it for data?                                                                                | - true                    |
|                                | kreXX will analyse all protected properties of a class.                                                                                | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse private properties     | | Shall kreXX create a reflection and poll it for data?                                                                                | - true                    |
|                                | kreXX will analyse all private properties of a class.                                                                                  | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse traversable data       | Shall kreXX try to traverse through the object?                                                                                        | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| List of debug methods to poll  | | Which additional methods shall be called from the object?                                                                            | debug,toArray,__toString, |
| for data                       | Comma separated list of methods. These methods are called without parameters. They also might do stuff which might be unwanted.        | toString                  |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum nesting level          | | How deep shall we analyse objects?                                                                                                   | 5                         |
|                                | When kreXX reaches a certain level, it simply stops there and won’t go any further.                                                    |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Methods
-------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Analyse public methods         | Shall kreXX analyse all public methods of an object?                                                                                   | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse protected methods      | Shall kreXX analyse all protected methods of an object?                                                                                | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
|Analyse private methods         | Shall kreXX analyse all private methods of an object?                                                                                  | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Error Handling and Backtrace
----------------------------
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Register fatal error handler   | When set to “true”, kreXX will register the handler as soon as it’s loaded. When a fatal error occures, kreXX will offer a backtrace   | - true                    |
| automatically                  | and an analysis of all objects in it. PHP always clears the stack in case of a fatal error, so kreXX has to keep track of it.          | - false                   |
|                                | Be warned: This option will dramatically slow down your requests.                                                                      |                           |
|                                |                                                                                                                                        |                           |
|                                | | Use this only when you have to. It is much better to register the error handler yourself with                                        |                           |
|                                | | :literal:`\krexx::registerFatal();`                                                                                                  |                           |
|                                | | and later unregister it with                                                                                                         |                           |
|                                | | :literal:`\krexx::unregisterFatal();`                                                                                                |                           |
|                                | | to prevent a slowdown.                                                                                                               |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Backtrace analysis settings    | | Shall kreXX do a "deep" analysis of  the backtrace?                                                                                  | - normal                  |
|                                | Be warned, a deep analysis can produce a lot of output. A "normal" analysis will use the configured settings, while a "deep" analysis  | - deep                    |
|                                | will get as much data from the object as possible.                                                                                     |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


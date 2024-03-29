.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _config_options:


Configuration options
=====================


Output
------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Disable kreXX                  | Is kreXX actually active?                                                                                                              | - true                    |
|                                |                                                                                                                                        | - false                   |
|                                | Here you can disable kreXX on a global level without uninstalling it.                                                                  |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| IP Range                       | IP white list (comma separated) with wildcard (*) support.                                                                             | 192.168.0.110,192.168.1.* |
|                                |                                                                                                                                        |                           |
|                                | List of IPs who can trigger kreXX. You can do something like '192.168.1.*', but not something like '192.168.*.110'.                    |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Try to detect AJAX requests    | Shall kreXX try to detect an AJAX request?                                                                                             | - true                    |
|                                |                                                                                                                                        | - false                   |
|                                | If set to true, there will be no output when AJAX is detected, to prevent a js error.                                                  |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


Behavior
--------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Skin                           | You can change the CSS/HTML skin. We included the Hans and Smoky-Grey skin                                                             | - hans                    |
|                                |                                                                                                                                        | - smoky-grey              |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Destination                    | Will the output be sent to the frontend or the logfolder?                                                                              | - Browser shutdown phase  |
|                                |                                                                                                                                        | - File                    |
|                                |                                                                                                                                        | - Browser immediately     |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum files in the logfolder | How many files should it keep? Files will only get deleted, when a new one is created.                                                 | 10                        |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Language                       | The language of the kreXX debug output.                                                                                                | - English                 |
|                                |                                                                                                                                        | - Deutsch                 |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


Prune output
------------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Maximum steps in the backtrace | A backtrace tends to produce a lot of output, and browsers tend to have problems with more than 100MB output in HTML text.             | 10                        |
|                                | Normally it is not unnecessary to go back more than 10 steps, but if you need to, you can increase this number here.                   |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum array size before      | Huge arrays with lots of objects or other array have the tendency to produce a lot of output. To prevent output that a browser can not | 300                       |
| fallback to simplified         | handel, kreXX uses a simplified array analysis. Simple values will be analysed normally, objects will only display their name, key and |                           |
| analysis                       | type.                                                                                                                                  |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum nesting level          | How deep shall we analyse objects?                                                                                                     | 5                         |
|                                |                                                                                                                                        |                           |
|                                | When kreXX reaches a certain level, it simply stops there and won’t go any further.                                                    |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+


Properties
----------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Analyse protected properties   | Shall kreXX create a reflection and poll it for data?                                                                                  | - true                    |
|                                |                                                                                                                                        | - false                   |
|                                | kreXX will analyse all protected properties of a class.                                                                                |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse private properties     | The same as :literal:`Analyse protected properties`, only for private properties.                                                      | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Deep analysis for scalar types | Doing a deep analysis of scalar types.                                                                                                 | - true                    |
|                                | kreXX will decode / parse XML and JSONs. File paths and callbacks will also be analysed.                                               | - false                   |
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
| Analyse private methods        | Shall kreXX analyse all private methods of an object?                                                                                  | - true                    |
|                                |                                                                                                                                        | - false                   |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Analyse getter methods         | Shall kreXX try to determine the output of getter methods?                                                                             | - true                    |
|                                | Getter methods will NOT get called to get a result.                                                                                    | - false                   |
|                                | Instead, kreXX tries to get the (possible) result from the properties of this class. If the getter method is used to compute this      |                           |
|                                | value, the values here may be inaccurate.                                                                                              |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| List of debug methods to poll  | Which additional methods shall be called from the object?                                                                              | debug,toArray,__toString, |
| for data                       |                                                                                                                                        | toString                  |
|                                | Comma separated list of methods. These methods are called without parameters. They also might do stuff which might be unwanted.        |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

Emergency stop
--------------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Maximum amount of calls        | How often can you call kreXX in one run?                                                                                               | 10                        |
|                                |                                                                                                                                        |                           |
|                                | kreXX can generate a lot of data, and depending on your settings increasing this number might not be a good idea.                      |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Minimum amount of memory [MB]  | kreXX checks regularly how much memory is left. Here you can adjust the amount where it will trigger an emergency break. Unit of       | 64                        |
|                                | measurement is MB.                                                                                                                     |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Maximum Runtime [Seconds]      | kreXX checks during the analysis how much time has elapsed since start. Here you can adjust how many seconds can pass until an         | 60                        |
|                                | emergency break will be triggered. Unit of measurement is seconds.                                                                     |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+

TYPO3 specific
--------------

+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Option                         | Description                                                                                                                            | Possible values / example |
+================================+========================================================================================================================================+===========================+
| Activate the TYPO3 FileWriter  | Shall kreXX tab into the TYPO3 file logging?                                                                                           | - true                    |
|                                |                                                                                                                                        | - false                   |
|                                | If you are trying to ge more info about a :literal:`Oops an error occurred!` error, set this to :literal:`true`.                       |                           |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
| Log level of the FileWriter    | The log level of the file writer. Depending on the setting, the file writer will produce a lot of (unnecessary) output.                | - Debug                   |
|                                |                                                                                                                                        | - Info                    |
|                                | Why trying to get to the bottom of a :literal:`Oops an error occurred!` error, set this to :literal:`Error`.                           | - Notice                  |
|                                |                                                                                                                                        | - Error                   |
|                                |                                                                                                                                        | - Alert                   |
|                                |                                                                                                                                        | - Critical                |
|                                |                                                                                                                                        | - Emergency               |
+--------------------------------+----------------------------------------------------------------------------------------------------------------------------------------+---------------------------+
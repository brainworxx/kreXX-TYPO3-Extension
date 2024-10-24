.. image:: https://github.com/brainworxx/kreXX/actions/workflows/php.yml/badge.svg?branch=main
   :target: https://github.com/brainworxx/kreXX/actions/workflows/php.yml
.. image:: https://api.codeclimate.com/v1/badges/c9d414a09928ede869c7/maintainability
   :target: https://codeclimate.com/github/brainworxx/kreXX/maintainability
.. image:: https://api.codeclimate.com/v1/badges/c9d414a09928ede869c7/test_coverage.svg
   :target: https://codeclimate.com/github/brainworxx/kreXX/test_coverage
.. image:: https://poser.pugx.org/brainworxx/krexx/v/stable
   :target: https://packagist.org/packages/brainworxx/krexx
.. image:: https://poser.pugx.org/brainworxx/krexx/license
   :target: https://packagist.org/packages/brainworxx/krexx

==============
kreXX Debugger
==============

|

.. figure:: https://cloud.githubusercontent.com/assets/11192910/15508189/c3e07482-21ce-11e6-90e0-03cbe5dff276.png
  :alt: kreXX logo



Key features:
	- Dumping of protected variables
	- Dumping of private variables
	- Dumping of traversable data
	- Configurable debug callbacks, which will be called on objects (if present). The output will then be dumped.
	- Analysis of the methods of objects (comments, where declared, parameters). Comment dumping supports :literal:`{@inheritdoc}`.
	- Output is draggable and has a closing button.
	- All features can be globally configured in a configuration file.
	- All features can be locally configured in the browser. The settings will be stored in a cookie.
	- Configurable local opening function, to prevent other developers from calling your debug commands.
	- Output can be saved to an output folder. Very useful in m2m communication.
	- Several security measures to prevent prevent hangups with too large memory usage or a timeout.
	- Benchmarking
	- Fatal error handler with a full backtrace
	- Code generation to reach the displayed values, if possible.


.. figure:: https://cloud.githubusercontent.com/assets/11192910/19618053/3e67850a-9840-11e6-96a5-e20ffb67918c.png
  :alt: Analysis of an extbase query result

  Analysis of an extbase query result
  
Installation
============

Manual installation
^^^^^^^^^^^^^^^^^^^

1) Upload the whole kreXX directory to your webserver. Put it somewhere, where you are able to include it to your project.
2) Include as early as possible the file bootstrap.php into your project. Normally this is the index.php.
    
Using composer
^^^^^^^^^^^^^^

.. code-block:: shell

	composer require brainworxx/krexx`

Our composer page can be found here: https://packagist.org/packages/brainworxx/krexx

Usage inside the PHP code
=========================
kreXX will be called from within the PHP source code:

Main analytic function
^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	krexx($myObject);
	// or as an alias:
	\Krexx::open($myObject)

Benchmarking
^^^^^^^^^^^^
.. code-block:: php

	// start the benchmark test and define a "moment" during the test
	\Krexx::timerMoment('meaningful string, like started db query 123');
	// display the result
	\Krexx::timerEnd();


Backtrace
^^^^^^^^^
.. code-block:: php

	\Krexx::backtrace();


Fatal error handler
^^^^^^^^^^^^^^^^^^^
.. code-block:: php

	// PHP 5 only.
	// Register the fatal error handler
	\Krexx::registerFatal();
	// Unregister the fatal error handler
	\Krexx::unregisterFatal();


Edit your settings
^^^^^^^^^^^^^^^^^^
.. code-block:: php

	// display the edit settings dialog
	\Krexx::editSettings();


Scope analysis
^^^^^^^^^^^^^^
Often enough a kreXX call will look like this:


.. code-block:: php

	krexx($this);

Analysing "$this" means, that all protected and private values and methods are reachable from this point inside the code. When kreXX notices this, it will analyse all reachable variables and methods of this class.


Force logging
^^^^^^^^^^^^^
.. code-block:: php

	// The following commands create a log file instead of a browser output.
	\Krexx::log($myObject);
	\Krexx::logBacktrace();
	\Krexx::logTimerEnd();


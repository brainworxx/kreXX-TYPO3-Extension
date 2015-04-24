.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _users-manual:

Usage inside the PHP code
=========================

Main analytic function
^^^^^^^^^^^^^^^^^^^^^^
.. code-block:: php

	krexx($myObject);
	// or as an alias:
	\kreXX::open($myObject)

Benchmarking
^^^^^^^^^^^^
.. code-block:: php

	// start the benchmark test
	\kreXX::timerStart();
	// define a "moment" during the test
	\kreXX::timerMoment('meaningfull string, like started db query 123');
	// display the result
	\kreXX::timerEnd();


Backtrace
^^^^^^^^^
.. code-block:: php

	\kreXX::backtrace();


Fatal error handler
^^^^^^^^^^^^^^^^^^^
.. code-block:: php

	// register the fatal error handler
	\kreXX::registerFatal();
	// unregister the fatal error handler
	\kreXX::unregisterFatal();


Edit your settings
^^^^^^^^^^^^^^^^^^
.. code-block:: php

	// display the edit settings dialog
	\kreXX::editSettings();
.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _usage:

Usage inside the PHP code
=========================

Main analytic function
^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	krexx($myObject);
	// or as an alias:
	\kreXX::open($myObject)

.. figure:: ../Images/main_function.png
	:width: 678px
	:alt: analysis of an extbase query result

Benchmarking
^^^^^^^^^^^^

.. code-block:: php

	\Krexx::timerMoment('get all rows');
	$notizs = $this->notizRepository->findByPageId($GLOBALS['TSFE']->id);
	\Krexx::timerMoment('assign rows to view');
	$this->view->assign('notizs', $notizs);
	\Krexx::timerEnd();

.. figure:: ../Images/Usage/timer.png
	:width: 678px
	:alt: benchmarking result


Backtrace
^^^^^^^^^

.. code-block:: php

	\kreXX::backtrace();

.. figure:: ../Images/Usage/backtrace.png
	:width: 920px
	:alt: kreXX backtrace

Fatal error handler
^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	// register the fatal error handler
	\Krexx::registerFatal();
	// call undefined function to cause an error
	undefinedFunctionCall();
	// unregister the fatal error handler
	\Krexx::unregisterFatal();

.. figure:: ../Images/Usage/fatal.png
	:width: 1049px
	:alt: kreXX fatal error handler

Scope analysis
^^^^^^^^^^^^^^
Often enough a kreXX call will look like this:


.. code-block:: php

	krexx($this);

Analysing "$this" means, that all protected and private values and methods are reachable from this point inside the code. When kreXX notices this, it will analyse all reachable variables and methods of this class.

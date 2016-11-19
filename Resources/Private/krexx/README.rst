.. figure:: https://cloud.githubusercontent.com/assets/11192910/15508189/c3e07482-21ce-11e6-90e0-03cbe5dff276.png

==============
kreXX Debugger
==============

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

Usage inside the PHP code
=========================
kreXX will be called from within the PHP source code:

Main analytic function
^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	krexx($myObject);
	// or as an alias:
	\kreXX::open($myObject)

Benchmarking
^^^^^^^^^^^^
.. code-block:: php

	// start the benchmark test and define a "moment" during the test
	\kreXX::timerMoment('meaningful string, like started db query 123');
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


Scope analysis
^^^^^^^^^^^^^^
Often enough a kreXX call will look like this:


.. code-block:: php

	kreXX($this);

Analysing "$this" means, that all protected and private values and methods are reachable from this point inside the code. When kreXX notices this, it will analyse all reachable variables and methods of this class.


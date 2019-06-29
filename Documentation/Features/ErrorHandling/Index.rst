.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _fatal-error:


Error handling
==============


| Normally, when a exception happens, php will tell you the name of the error, the filename and the line number.
| When you look at the amount of data available from the TYPO3 error handler for example, you will clearly see that this is a little bit, ummm discreet.
|

kreXX will offer you the following information:
	- Snippet of the sourcecode, where the error has happened
	- Complete backtrace of what happened before the error occurred
	- Snippets of sourcecode is added to each step of the backtrace
	- Each object in the backtrace is fully analysed with all its data
	- . . .

Catching Exceptions
^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	// register the exception handler
    \Krexx::registerExceptionHandler();
    // call undefined function to cause an error
    undefinedFunctionCall();
    // unregister the exception handler
    \Krexx::unregisterExceptionHandler();



Catching PHP5 fatal errors
^^^^^^^^^^^^^^^^^^^^^^^^^^

When a fatal error occurs, the only way to actually do something is the shutdown function. The main problem here is, that the backtrace will not contain anything useful, you can not rely on php to provide these values.
Since php won't do this, kreXX will have to keep track of the backtrace. But by doing this, it will slow down your system to the extreme. This is why you will have to activate the handler.


.. code-block:: php

	// register the fatal error handler
	\kreXX::registerFatal();
	// unregister the fatal error handler
	\kreXX::unregisterFatal();

|
|

.. figure:: ../../Images/Usage/error_handler.png
	:width: 1049px
	:alt: kreXX error handler

	kreXX error handler features a completely analysed backtrace as well as parts of the sourcecode.
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


Set up an exception handler
^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	// register the exception handler
    \Krexx::registerExceptionHandler();
    // call undefined function to cause an error
    undefinedFunctionCall();
    // unregister the exception handler
    \Krexx::unregisterExceptionHandler();


Use kreXX in a try/catch
^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

    try {
        // Execute the stuff where the error may occur.
        $this->doSomething();
    } catch (\Throwable $e) {
        krexx($e);
    }

The :literal:`\Throwable $e` will catch everything bigger than warnings. During development it is a good idea to do this, because you will get a good grip at what is going wrong in there.
But when everything works as it should, you should narrow this down, so you can handle each exception type specifically.


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
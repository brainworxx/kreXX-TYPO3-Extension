.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _logging:

Logging
=======

Direct usage
------------

Normally, the output of kreXX is send straight to the browser. But there are always situations when this is highly impractical:

- Debugging the server side of an ajax request
- Dealing with a REST / soap service
- Sending other developers the kreXX output
- . . .

You can tell kreXX to write it's data in a file instead of trying to send it to the browser. How to do this, is explained in the chapter :ref:`config_options`.
Just change the output option **Destination** from **Browser** to **File**.

Alternatively, you can use the forced-logging methods:

.. code-block:: php

	// Force kreXX to write an analysis into a log file.
	\Krexx::log($myObject);
	// Force kreXX to write a backtrace into a log file
	\Krexx::logBacktrace();
	// Force the timer output into a log file
	\Krexx::logTimerEnd();


Wen using the forced logging, the following things will happen:

- Output destination is set to file by force.
- Ajax requests will get logged by force.

kreXX will store all logfiles inside the directory

.. code-block:: typoscript

	typo3temp/tx_includekrexx/log


The logfiles can be accessed here: :ref:`accesslogfiles`


Integration into the TYPO3 loging
---------------------------------

You can also use kreXX as a TYPO3 standard logger. Simply register it in your site package :literal:`ext_localconf.php`.

.. code-block:: php

	// Register kreXX as a new log writer
	$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
		// Using the error level, and above.
        \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
            \Brainworxx\Includekrexx\Log\FileWriter::class => [
				// Overwriting an existing configuration, allowing the scalar analysis.
                \Brainworxx\Krexx\Service\Config\ConfigConstInterface::SETTING_ANALYSE_SCALAR => 'true'
            ]
        ]
    ];

`Here <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Logging/Configuration/Index.html#configuration-of-the-logging-system />`_ you can find additional documentation about the TYPO3 logging system.

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _oops:

Oops an error occurred
======================

Getting any information out of the :literal:`Oops an error occurred` can be a little bit difficult.

Whenever a :literal:`Oops an error occurred` is thrown on the frontend, TYPO3 normally creates a log entry with some infos about what has happened.
The provided info about what happened tends to be a little bit sparse in the log file. kreXX however can provide much more information.

Simply

- activate the FileWriter integration here ref:`logging`
- flush the cache
- refresh the frontend where the :literal:`Oops` is shown.


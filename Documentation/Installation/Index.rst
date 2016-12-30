.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _installation:

Installation
=========================

Installation is very straight forward. You can:

- Install it normally with the extension manager
- Require it with the composer :literal:`composer require typo3-ter/includekrexx`
- Require the kreXX library itself with composer :literal:`composer require brainworxx/krexx` and then install the backend extension with the extension manager to get kreXX as early inside your system as possible

There is no typoscript or pageTSconfig at all.

.. important::
  The extension manager only installs the version that is knows about. If you have not updated your extension list for some time, it will only offer the versions that it knows about. So far, kreXX has always been under heavy development. In order to get the latest version, you might want to consider updating the extension list.

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _config:

Configuration
=============

| You do not have to configure kreXX. It works very well out of the box.
| There are two ways to configure kreXX:

1. Edit configuration file settings (we have provided an editor for this)
2. Edit local cookie settings

Factory settings
----------------
The factory settings are hardcoded default settings, in case there is no configuration available.

Configuration file settings
---------------------------
The configuration file is located here:

.. code-block:: typoscript

	typo3conf/ext/includekrexx/Resources/Private/krexx/Krexx.ini

You do not have to edit this file manually. we have provided a backend editor for this: :ref:`config_options`


Local cookie settings
---------------------
| Local cookie settings can be changed in the kreXX output window. Not all settings can be edited in the frontend, but that can be changed here: :ref:`fe-configuration`
| Please note that local cookie settings affect only your current browser.

|
|

**Table of Contents**

.. toctree::
   :maxdepth: 1
   :titlesonly:
   :glob:

   Levels/Index
   Options/Index
   Frontend/Index
   AjaxCli/Index
   DevHandle/Index

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _config_levels:

Configuration levels
====================
There are 3 levels of configuration:

Factory settings
^^^^^^^^^^^^^^^^

The factory settings are hardcoded default settings, in case there is no configuration available.

Configuration file settings
^^^^^^^^^^^^^^^^^^^^^^^^^^^

The configuration file settings can be edited in the TYPO3 backend. The file settings overwrite the factory settings.

Local cookie settings
^^^^^^^^^^^^^^^^^^^^^

Local cookie settings can be changed in the kreXX output window. The cookie settings overwrite the file settings.

To wipe these settings, simply click the button :literal:`Reset local cookie settings` in the backend module, or the button :literal:`Reset local settings` in the kreXX debug output.

Exception to this hierarchy rule
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* Once you disable kreXX on file or local level, it stays disabled.
* If you set the output to file inside the configuration file, it will not be overwritten with any cookie settings.

But remember: Local settings only apply to your current browser.

Not all settings can be edited in the kreXX output window. What can be edited, and how to change this is documented here: :ref:`fe-configuration`

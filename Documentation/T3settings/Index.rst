.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _t3-dev-settings:


TYPO3 Development Settings
==========================

.. important::
	**Do not use these settings on a productive site!** Never debug a productive system.


When developing TYPO3 extensions or editing template files, people often find themselves unable to get any useful error messages from the system.
And cleaning the cache for every page refresh can be taxing.

Like everything in TYPO3, there is a configuration for this.

Typoscript
^^^^^^^^^^

.. code-block:: typoscript

	# Activeate the error handler to show on the frontend want is going on.
	config.contentObjectExceptionHandler = 0
	# Deacivaate the frontend cache.
	config.no_cache = 1

Disabling the frontend cache lets you see your changes right away, but it does have it's drawbacks:

The page may behave differently when you reactivate the cache. Caching a shopping cart is not a good idea, but you will only notice it, when you reactivate hte cache again.
Just remember to test your changes with **and** without cache.


Install Tool
^^^^^^^^^^^^

The install tool does have 2 configuration presets for debugging:

	- Live
	- Debug

What these presets do and where you can find them is documented `here <https://docs.typo3.org/typo3cms/InstallationGuide/In-depth/TheInstallTool/Index.html#configuration-presets />`_.

.. _devsettings:

TYPO3 Development Settings
==========================

.. warning::
	**Do not use these settings on a productive site!**

.. tip::
    These are TYPO3 standard settings, that have nothing to do with kreXX.

When developing TYPO3 extensions or editing template files, people often find themselves unable to get any useful error messages from the system. And cleaning the cache for every page refresh can be taxing.

There is a standard configuration for this.


Typoscript
^^^^^^^^^^

.. code-block:: typoscript

	# Activate the error handler to show on the frontend want is going on.
	config.contentObjectExceptionHandler = 0
	# Deactivate the frontend cache.
	config.no_cache = 1

Disabling the frontend cache lets you see your changes right away, but it does have it's drawbacks:

The page may behave differently when you reactivate the cache. Caching a shopping cart is not a good idea, but you will only notice it, when you reactivate hte cache again.
Just remember to test your changes with **and** without cache.


Install Tool
^^^^^^^^^^^^

The install tool does have 2 configuration presets for debugging:

- Live
- Debug

What these presets do and where you can find them is documented `here <https://docs.typo3.org/m/typo3/tutorial-getting-started/main/en-us/Troubleshooting/TYPO3.html#debug-settings>`__.
.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _scope:

Scope analysis
==============

When kreXX is called from the php code, it tires to analyse all class characteristics, that are accessible from the point where it was called. This is especially useful when calling something like:

.. code-block:: php

	krexx($this);

A lot more stuff can be reached from inside a class, and kreXX will analyse these properties and methods. The scope analysis can be considered as the auto config mode. It will automatically overwrite the settings
for protected and private properties/methods. It will not remove analysis results, only add everything that can be reached by normal means.

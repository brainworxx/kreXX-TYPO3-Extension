.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _getter:

Getter analysis
===============

Getter methods are class methods that return values or objects. Most of these are stored inside the class in protected properties. kreXX will try to guess which property belong to these getters and analyse them.
This results in a much more complete overview of the class that is being analysed.

These getters will **not** get called, and this guessing may fail with the following results:

+---------+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| NULL    | This means that the property itself exists, but has no value. The getter may create this value and only cache it inside the protected property. But since the method was not called, this value is NULL. |
+---------+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| nothing | This means that kreXX was not able to get anything from the getter method.                                                                                                                               |
+---------+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+

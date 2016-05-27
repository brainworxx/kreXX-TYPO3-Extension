.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _fe-configuration:


Edit FE Configuration
=====================

Being able to edit all settings on the fly inside the debug output might not be such a good idea. Like with the configuration, we have provided kreXX with factory settings, disabling the more "dangerous" options.

With factory settings active, you can not edit the following settings:

- deep --> debugMethods
- output --> destination

The following settings are not even displayed:

- logging --> maxfiles
- logging --> folder

kreXX will not accept any cookie settings for these options.

This can also be changed, of course. For every control, you can set 3 different settings:

+---------+-------------------------------------------------------------------------------------------------------------------------------+
| Value   | meaning                                                                                                                       |
+=========+===============================================================================================================================+
| full    | You can fully edit this value in the frontend                                                                                 |
+---------+-------------------------------------------------------------------------------------------------------------------------------+
| display | kreXX will only display its current value. If there are any leftover settings in the settings-cookie, they will be ignored.   |
+---------+-------------------------------------------------------------------------------------------------------------------------------+
| none    | kreXX will not display this control or it's value. Leftover settings will be ignored.                                         |
+---------+-------------------------------------------------------------------------------------------------------------------------------+

You can change which values can be edited on the frontend. To do this, we've also provided an editor:

.. figure:: ../../Images/Config/typo3_backend_02.png
	:width: 350px
	:align: left
	:alt: Here you can change what options are available in the frontend.
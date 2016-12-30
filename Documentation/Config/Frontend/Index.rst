.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _fe-configuration:


Edit FE Configuration
=====================

Being able to edit all settings on the fly inside the debug output might not be such a good idea. Like with the configuration, we have provided kreXX with factory settings, disabling the more "dangerous" options.

This can also be changed, of course. For (nearly) every control, you can set 3 different settings:

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
	:alt: Here you can change what options are available in the frontend.

|

However, the following options will never be editable on the frontend:

- Output --> Destination
- Output --> Folder
- Output --> Maximum files in the logfolder
- Methods --> List of debug methods to poll for data

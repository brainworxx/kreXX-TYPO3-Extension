.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _dataviewer:


DataViewer fluid support
========================

Everybody who uses DataViewer knows, that there is some magic happening inside the :literal:`record` variable.

The DataView plugin is taking a closer look at this, and provides a better analysis for the record values you want to display.
You don't not have to switch back and forth from debug mode inside the plugin to get the information you  need.

Simply call

.. code-block:: html

    <krexx:debug>{record}</krexx:debug>

and open the :literal:`Getter` section in the output.

More Information about fluid debugging can be found here: :ref:`fluid`

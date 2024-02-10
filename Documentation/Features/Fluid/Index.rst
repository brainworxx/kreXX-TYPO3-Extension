.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _fluid:


Fluid debugger
==============


kreXX does not only work in PHP. We have added a debug viewhelper for fluid.

Analytic function
^^^^^^^^^^^^^^^^^

Using the viewhelper is pretty much straight forward:

.. code-block:: html

    <!-- Normal frontend output -->
    <krexx:debug>{_all}</krexx:debug>
    <!-- Use this part if you don't want fluid to escape your string or if you are stitching together an array. -->
    <krexx:debug value="{my: 'value', to: 'analyse'}" />
    <!-- Force the logging into a file -->
    <krexx:log>{_all}</krexx:log>
    <krexx:log value="{my: 'value', to: 'analyse'}" />

|
|

.. figure:: ../../Images/FluidDebugger/fluid_debugger.png
	:width: 946px
	:alt: Screenshot of the fluid debugger output

	Fluid debugger output with source generation for fluid.

kreXX will then try to analyse everything inside the variable given to it.
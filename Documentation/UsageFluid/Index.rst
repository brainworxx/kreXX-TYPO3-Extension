.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _usagefluid:

Usage inside the Fluid template
===============================


kreXX does not only work in PHP. We have added a debug viewhelper for fluid.

The debugging ViewHelpers
^^^^^^^^^^^^^^^^^^^^^^^^^

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

.. figure:: ../Images/FluidDebugger/fluid_debugger.png
	:width: 946px
	:alt: Screenshot of the fluid debugger output

	Fluid debugger output with source generation for fluid.

kreXX will then try to analyse everything inside the variable given to it.

Benchmarking ViewHelpers
^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: html

    <!-- Take a moment -->
    <krexx:timer.moment value="Start rendering"/>
    <!-- A lot more template code -->
    . . . .
    <!-- Take another  moment -->
    <krexx:timer.moment value="Start meue rendering"/>
    <!-- Even more template code -->
    . . . .
    <!-- Output the benchmark -->
    <krexx:timer.end/>

kreXX measures the time between every call, and then outputs it as a benchmark.

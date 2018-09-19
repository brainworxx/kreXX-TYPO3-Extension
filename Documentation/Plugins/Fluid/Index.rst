.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _fluid:


Fluid debugger
==============


kreXX does not only work in PHP. We have added a debug viewhelper for fluid.

Namespace
^^^^^^^^^

When using TYPO3 6.2 until 8.4, you need to declare the namespace first. If you are using TYPO3 8.5 and later, this will not be necessary anymore.

.. code-block:: html

    {namespace krexx=Brainworxx\Includekrexx\ViewHelpers}
    or
    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:krexx="http://typo3.org/ns/Brainworxx/Includekrexx/ViewHelpers"
          data-namespace-typo3-fluid="true">

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
	:width: 1081px
	:alt: Screenshot of the fluid debugger output

	Fluid debugger output with source generation for fluid.

kreXX will then try to analyse everything inside the variable given to it.
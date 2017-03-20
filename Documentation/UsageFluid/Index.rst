.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _fluid-debugger:


Usage inside the Fluid template
===============================

kreXX does not only work in PHP. We have added a debug viewhelper for fluid.

Namespace
^^^^^^^^^

When using TYPO3 4.5 until 8.4, you need to declare the namespace first. If you are using TYPO3 8.5 and later, this will not be necessary anymore.

.. code-block:: html

    {namespace krexx=Tx_Includekrexx_ViewHelpers}


Analytic function
^^^^^^^^^^^^^^^^^

Then calling the viewhelper is pretty much straight forward:

.. code-block:: html

    <krexx:debug>{_all}</krexx:debug>

or

.. code-block:: html

    <krexx:debug value="{my: 'value', to: 'analyse'}" />

Use this part if you don't want fluid to escape your string or if you are stitching together an array.

|
|

.. figure:: ../Images/FluidDebugger/fluid_debugger.png
	:width: 1081px
	:alt: Screenshot of the fluid debugger output

	Fluid debugger output with source generation for fluid.

kreXX will then try to analyse everything inside the variable given to it.


f:debug vs. krexx:debug
^^^^^^^^^^^^^^^^^^^^^^^

f:debug is a great debugger, and shows the values that are used most of the time. But it does not give you everything that there is.
The great advantages of f:debug are:

	- Easy to read
	- Easy to use

|

krexx:debug on the other hand tries to give you everything that is analysable, which may be too much.
The great advantages of krexx:debug are:

	- Nearly complete list of attributes / methods / properties
	- Output via backend logging / shutdown function minimises the interference with the TYPO3 output.
	- Source generation
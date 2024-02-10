.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


What does it do?
================

kreXX will either be called from the fluid template:

.. code-block:: html

    <!-- Normal frontend output -->
    <krexx:debug>{_all}</krexx:debug>
    <!-- Force the logging into a file -->
    <krexx:log>{_all}</krexx:log>


Or from within the PHP source code:

.. code-block:: php

    krexx($myObject);

|

**Key features:**

- Dumping of protected properties
- Dumping of private properties
- Dumping of traversable data
- Dumping of getter method data
- Dumping of class metadata like interfaces, traits, class comments, inheritance.
- Configurable debug callbacks, which will be called on objects (if present). The output will then be dumped.
- Analysis of the methods of objects (comments, where declared, parameters). Comment dumping supports :literal:`{@inheritdoc}`.
- Output is draggable and has a closing button.
- All features can be globally configured with a backend editor.
- Most features can be configured locally in the browser. The settings will be stored in a cookie.
- Configurable local opening function, to prevent other developers from calling your debug commands.
- IP mask to allow only some IPs (or IP ranges) to trigger kreXX.
- Output can be saved to an output folder. Very useful in m2m communication or ajax.
- Backend access to the logfiles with a file dispatcher.
- Frontend access to the logfiles with the 9.5'er Admin Panel.
- Several safety measures to prevent prevent hangups with too large memory usage or a timeout.
- Benchmarking
- Fatal error handler with a full backtrace (PHP5 only).
- Exception handler with a full backtrace.
- Code generation to reach the displayed values, if possible.
- Debugger for the magical methods and properties of the Aimeos shop system.
- Debugger for Fluid
- Advanced analysing of error objects
- Analysis of :literal:`JSON` and :literal:`XML` strings
- Analysis of Flexform data string.

|
|

.. figure:: ../Images/main_function.png
    :width: 714px
    :alt: Analysis of an extbase query result

    Analysis of an extbase query result

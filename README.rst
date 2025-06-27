.. |build| image:: https://github.com/brainworxx/kreXX-TYPO3-Extension/actions/workflows/php.yml/badge.svg?branch=main
   :target: https://github.com/brainworxx/kreXX-TYPO3-Extension/actions/workflows/php.yml
.. |maintainability| image:: https://qlty.sh/badges/aadffeb9-1431-4230-b271-cfece6f1a457/maintainability.svg
   :target: https://qlty.sh/gh/brainworxx/projects/kreXX-TYPO3-Extension
.. |coverage| image:: https://api.codeclimate.com/v1/badges/1e267d66a0aaf5913322/test_coverage
   :target: https://codeclimate.com/github/brainworxx/kreXX-TYPO3-Extension/test_coverage
.. |stable| image:: https://poser.pugx.org/brainworxx/includekrexx/v/stable?style=flat-square
   :target: https://packagist.org/packages/brainworxx/includekrexx
.. |license| image:: https://poser.pugx.org/brainworxx/includekrexx/license?style=flat-square
   :target: https://packagist.org/packages/brainworxx/includekrexx
.. |t310| image:: https://img.shields.io/badge/TYPO3-10-orange.svg?style=flat-square
   :target: https://get.typo3.org/version/10
.. |t311| image:: https://img.shields.io/badge/TYPO3-11-orange.svg?style=flat-square
   :target: https://get.typo3.org/version/11
.. |t312| image:: https://img.shields.io/badge/TYPO3-12-orange.svg?style=flat-square
   :target: https://get.typo3.org/version/12
.. |t313| image:: https://img.shields.io/badge/TYPO3-13-orange.svg?style=flat-square
   :target: https://get.typo3.org/version/13

|build| |maintainability| |coverage|

|stable| |t310| |t311| |t312| |t313| |license|

========================================
kreXX Debugger - TYPO3 Backend Extension
========================================


.. figure:: https://github.com/brainworxx/kreXX-TYPO3-Extension/blob/main/Documentation/Images/krexx.png
   :alt: kreXX logo


Fluid (and PHP) debugger with backend access to logfiles, code generation to reach the displayed values and much more. We added some special stuff for Aimeos.


.. list-table:: Title
   :widths: 25 25
   :header-rows: 0

   * - **kreXX mainlibrary**
     - https://github.com/brainworxx/kreXX
   * - **Documentation**
     - https://docs.typo3.org/p/brainworxx/includekrexx/main/en-us/
   * - **TER:**
     - https://extensions.typo3.org/extension/includekrexx/

================
What does it do?
================

To put it simple: kreXX is an alternative for the fluid debugger :literal:`<f:debug/>`.

The problem with :literal:`<f:debug/>` is that is can be a little bit discrete when debugging objects.
It only shows protected properties which may or may not be reachable in the template file.

kreXX gives you a good overview about the object and how to reach specific values inside.

.. code-block:: html

    <!-- Normal frontend output -->
    <krexx:debug>{_all}</krexx:debug>
    <!-- Force the logging into a file -->
    <krexx:log>{_all}</krexx:log>


.. figure:: https://raw.githubusercontent.com/brainworxx/kreXX-TYPO3-Extension/refs/heads/main/Documentation/Images/Fluid.png
    :alt: Fluid debugging with code snippet generation.

    Fluid debugging with code snippet generation.


You can also use it as a PHP debugger:

.. code-block:: php

    // Normal frontend output
    krexx($myObject);
    // Force the logging into a file
    krexxlog($myObject);

=======
Logging
=======

To use kreXX as a logger simply use the logger ViewHelper:

.. code-block:: html

    <krexx:log>{_all}</krexx:log>



The access to the logs files can be found in the first tab of the backend module. The list is automatically updated by
ajax every few seconds.

Each entry has a unique colour to make new files better recognisable,

.. figure:: https://raw.githubusercontent.com/brainworxx/kreXX-TYPO3-Extension/refs/heads/main/Documentation/Images/Logging.png
   :alt: Logfiles backend menu

    Logfiles backend menu


To make these logfiles easier accessible, we have provided a backend menu, where you can easily view them. The list is
automatically updated.

To prevent these files from clogging up your system, kreXX will only keep **10** files and automatically delete older
ones. This value can also be changed the logging option **Maximum files in the log folder** to any number bigger than **0**.

A file can be access by simply clicking on the filename. The trashcan on the right deletes the file.


.. figure:: https://raw.githubusercontent.com/brainworxx/kreXX-TYPO3-Extension/refs/heads/main/Documentation/Images/AdminPanel.png
   :alt: Logfiles in the Admin Panel

    Logfiles in the Admin Panel


Alternatively, you can access the logfiles by using the TYPO3 Admin Panel.

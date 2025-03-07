.. _whatDoesItDo:

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


.. figure:: ../../Images/Fluid.png
    :class: with-shadow d-inline-block
    :alt: Fluid debugging with code snippet generation.

    Fluid debugging with code snippet generation.


You can also use it as a PHP debugger:

.. code-block:: php

    // Normal frontend output
    krexx($myObject);
    // Force the logging into a file
    krexxlog($myObject);

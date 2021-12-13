.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _limitations:

Limitations of kreXX
====================

Content Security Policy
^^^^^^^^^^^^^^^^^^^^^^^

kreXX is very reliant on JavaScript. Without it there is not much sense to it.
The JavaScript itself is outputted inline via `<script>` tags. And that may be a problem.

A lot of websites have a content security police in place, preventing inline JavaScript from being used.
Such a policy is actually a very good idea, because it gives an extra level of security.
On the downside it also prevents kreXX on the frontend.

.. warning::
	**If you have such a Content Security Policy in place: Do NOT remove it.**

.. tip::
	Instead simply switch to logging.

Very large debug output
^^^^^^^^^^^^^^^^^^^^^^^

The kreXX library is able to create a log output larger than 1 GB.
Creating such a large amount of output has two mayor drawbacks:

#. It takes a lot of time to search through the output
#. Your browser may not be able to render so much HTML code.

The nesting level limit helps a lot with keeping the output to a manageable level.
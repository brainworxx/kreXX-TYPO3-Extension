.. _limitations:

Limitations
===========

Content Security Policy
^^^^^^^^^^^^^^^^^^^^^^^

kreXX is very reliant on JavaScript. Without it, it would not be able to do its job. The JavaScript itself is outputted
inline via :literal:`<script>` tags. And that may be a problem.

A lot of websites have a content security police in place, preventing inline JavaScript from being used.
Such a policy is actually a very good idea, because it gives an extra level of security.
On the downside it also prevents kreXX on the frontend.

We have done all we could to make kreXX work with such a policy in place, but it is not always possible. A good example
is the Aimeos Backend. The CSP policy is hardcoded in the template files, leaving no way to inject the needed
JavaScript.

.. warning::
	If you have such a Content Security Policy in place: Do **NOT** remove it.

.. tip::
	Instead simply switch to logging.

Headers already send
^^^^^^^^^^^^^^^^^^^^

When debugging, you may encounter the error message :literal:`Headers already send`. This means that PHP has already sent
some data to the browser, and kreXX is trying to send some more, causing this.

The solution is simple: Try other output methods.

You can change the output methods in the backend settings.
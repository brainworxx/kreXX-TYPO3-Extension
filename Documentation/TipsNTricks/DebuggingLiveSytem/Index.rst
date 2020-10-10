.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _debugging_live:

Debugging in a live environment
===============================


.. warning::
	Never debug a productive system.

Why this is bad
^^^^^^^^^^^^^^^

The main problem with this is security. Having a debug output in the frontend can give a possible attacker all kind of
information about your system. A hacker **will** use it against you. And having your debug output indexed by Google will make this even worse.

| So, is kreXX a security risk?
| No. But a debug output on the frontend surely is.
|

Also, showing a cryptic infobox on your website will damage the trust the visitor have in that site.

And lastly: There is always a good chance that things will become even worse than they are now.

Settings
^^^^^^^^

kreXX was not coded for the usage in a live environment. But, we are well aware that there are moments when there is no other option available.

- Talk to your customer and/or boss and tell them what you are about to do and that this is dangerous.
- If possible, take the pages offline where you use kreXX.
- Go to the backend and switch to :literal:`Destination: File`
- Switch to :literal:`Expert Mode`, go to  :literal:`Edit FE configuration` and switch everything to :literal:`Do not display`.
- Do **NOT** use the TYPO3 Development Settings. I mean it! Don't!
- Be extremely careful when editing files.

And when you are done:

- Remove **all** debug statements from the code.
- Uninstall includekrexx and delete it from the server.
- Never do this again.
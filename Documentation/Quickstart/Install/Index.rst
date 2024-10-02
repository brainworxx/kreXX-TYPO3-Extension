.. _install:

Installation
============

Includekrexx is a development tool for Fluid (and PHP). But deploying a development tool to a production system is not a
good idea. This only encourages developer to use it on a production system, which will only lead to problems. Therefore
we recommend to install it via composer require-dev on your local DDEV.


Installation via composer on DDEV
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can install includekrexx on your local DDEV by simply adding includekrexx as a development requirement:

:literal:`ddev composer require --dev brainworxx/includekrexx`

Your deployment script should only deploy the production dependencies, leaving out any development extension or libraries.

We recommend the usage of a Git Hook blacklisting the word :literal:`krexx` in the code. This will prevent the deployment
of any code containing any leftover debug calls.

Classic installation
^^^^^^^^^^^^^^^^^^^^

When using the classic installation, you can download the latest release from the TER and install it via the extension
manager. Just remember to clear the cache afterwards.


After the installation
^^^^^^^^^^^^^^^^^^^^^^

Includekrexx does not uys any database tables, so there is no need to update the database scheme.

After installing includekrexx, you should clear all caches. This will ensure that the new extension is properly loaded
and that all caches are up to date.

Go to the :literal:`Admin Tooll --> Maintenance` and click on the :literal:`Flush TYPO3 Caches` button.
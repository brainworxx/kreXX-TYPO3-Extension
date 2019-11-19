.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _installation:

Installation and upgrade
========================

Installation via extension manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The installation process is pretty much straight forward (partly copy pasta from the TER itself):

#. Go to the TER `https://extensions.typo3.org/extension/includekrexx//<https://extensions.typo3.org/extension/includekrexx/>`_.
#. Download the Zip.
#. Log into your TYPO3 backend.
#. Go to Extension Manager module.
#. Press the upload button on the top bar.
#. Select the ZIP file and upload it.


Upgrade via extension manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

kreXX has always been under heavy development. When Upgrading (or downgrading) to an other version of kreXX, you need to do an additional steps (the **bold** ones).

#. Go to the TER `https://extensions.typo3.org/extension/includekrexx//<https://extensions.typo3.org/extension/includekrexx/>`_.
#. Download the Zip.
#. Log into your TYPO3 backend.
#. Go to Extension Manager module.
#. **Deactivate the kreXX extension there.**
#. Press the upload button on the top bar.
#. Select the ZIP file.
#. **Activate the overwrite checkbox.**
#. Upload the file.
#. **Reactivate the extension.**


Installation via composer
^^^^^^^^^^^^^^^^^^^^^^^^^

Copy pasta from the TER:

#. Go to your folder where the root composer.json file is located
#. Type: :literal:`composer req brainworxx/includekrexx` to get the latest version that runs on your TYPO3 version.
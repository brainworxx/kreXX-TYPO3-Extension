.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _typo3:


TYPO3 configuration
===================

The TYPO3 plugin applies the following changes to the kreXX standard behavior:
    - The log folder is set to :literal:`typo3temp/tx_includekrexx/log`.
    - The chunks folder is set to :literal:`typo3temp/tx_includekrexx/chunks`.
    - The configuration folder is set to :literal:`typo3temp/tx_includekrexx/config`.
    - Blacklisting of several debug methods, to prevent errors.
    - Make use of the TYPO3 implementation for the IP filter, because it is more versatile thn the kreXX version.
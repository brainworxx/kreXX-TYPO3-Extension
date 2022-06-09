.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _typo3:


SQL Debugger
============

Writing a complicated SQL query with the doctrine query builder can be a difficult task. kreXX offers some assistance here:

When analysing a query builder or a query object itself, kreXX tries to extract the sql query from the object.

.. code-block:: php

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
    $queryBuilder
        ->select('uid')
        ->from('pages')
        ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter('Home')));

    krexx($queryBuilder);
    $result = $queryBuilder->execute();
    krexx($result);

|

.. figure:: ../../Images/Typo3/sql_debugger.png
    :width: 1528px
    :alt: SQL debugger in action

    SQL debugger in action
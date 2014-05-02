.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _user-clear-cache:

Clearing the cache
^^^^^^^^^^^^^^^^^^

When data is imported into your TYPO3 installation, you may want to
clear the cache for a number of pages in order for the new data to be
displayed as soon as it is available. One way to achieve this is to
rely purely on TYPO3 CMS and use the :ref:`TSconfig property <t3tsconfig:pagetcemain>`:

.. code-block:: typoscript

   TCEMAIN.clearCacheCmd = xx,yy

on the page(s) where the data is stored to automatically trigger the
clearing of the cache for the given pages (xx and yy) when any record
they contain is modified or deleted, or some new record inserted.

This works fine but has one big drawback: it is triggered for
**each** record. If you manipulate a lot of records, the cache
clearing may be called hundreds or thousands of times. This can be
very bad for your site, especially if you have a very large cache.

Since version 2.0 of External Import, it is possible to trigger the
clearing of the cache **after** the whole import process has
completed for a given configuration. Instead of using TSconfig, the
configuration would be something like:

.. code-block:: php

   $GLOBALS['TCA']['tt_news']['ctrl']['external']['0']['clearCache'] = 'xx,yy';

This will clear the cache for pages xx and yy, but only after all
records have been inserted, updated and deleted. The process still
relies on TCEmain for clearing the cache of each page, so you may rely
on the usual clear cache hooks if needed.


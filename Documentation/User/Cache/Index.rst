.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _user-clear-cache:

Clearing the cache
^^^^^^^^^^^^^^^^^^

When data is imported into your TYPO3 CMS installation, you may want to
clear the cache for a number of pages in order for the new data to be
displayed as soon as it is available. One way to achieve this is to
rely purely on TYPO3 CMS and use the :ref:`TSconfig property <t3tsconfig:pagetcemain-clearcachecmd>`:

.. code-block:: typoscript

   TCEMAIN.clearCacheCmd = xx,yy

on the page(s) where the data is stored to automatically trigger the
clearing of the cache for the given pages (xx and yy) when any record
they contain is modified or deleted, or some new record inserted.

This works fine but has one big drawback: it is triggered for
**each** record. If you manipulate a lot of records, the cache
clearing may be called hundreds or thousands of times. This can be
very bad for your site, especially if you have a very large cache.

It is also possible to trigger the
clearing of the cache **after** the whole import process has
completed for a given configuration. Instead of using TSconfig, the
configuration would be something like:

.. code-block:: php

   $GLOBALS['TCA']['tt_news']['ctrl']['external']['0']['clearCache'] = 'xx,yy';

This will clear the cache for pages "xx" and "yy", but only after all
records have been inserted, updated and deleted. The process still
relies on DataHandler for clearing the cache of each page, so you may rely
on the usual clear cache hooks if needed.

Besides pages numbers, you can also use more general cache identifiers
like "pages" (to clear the cache for all pages), cache tags, or any
other value that can be used with
:ref:`TCEMAIN.clearCacheCmd <t3tsconfig:pagetcemain-clearcachecmd>`.

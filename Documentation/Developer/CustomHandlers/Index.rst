.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _developer-handlers:

Custom data handlers
^^^^^^^^^^^^^^^^^^^^

It is possible to use a custom data handler instead of the standard
:code:`tx_externalimport_importer::handleArray()` and
:code:`tx_externalimport_importer::handleXML()`. The value declared
as a custom data handler:

.. code-block:: php

   $GLOBALS['TCA']['some_table']['ctrl']['external'][0]['data'] = 'tx_foo_bar';

is a class name. The corresponding class file should be declared with
the autoloader (or use namespaces, since TYPO3 CMS 6.0).

The class itself **must** implement the
:code:`tx_externalimport_dataHandler` interface, which contains only
the :code:`handleData()` method. This method will receive two
arguments:

- an array containing the raw data returned by the connector service

- a reference to the calling :code:`tx_externalimport_importer` object

The method is expected to return a simple PHP array, with indexed
entries, like the standard methods (:code:`tx\_externalimport\_importer::handleArray()` and
:code:`tx\_externalimport\_importer::handleXML()`).

.. note::

   This was not tested by myself (the extension author). It
   was introduced to answer the particular need to parse large arrays
   using methods similar to XPath. This would have relied on a library
   which was not considered stable enough. Having custom data handlers
   makes it possible.

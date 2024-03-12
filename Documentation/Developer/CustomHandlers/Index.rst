.. include:: /Includes.rst.txt


.. _developer-handlers:

Custom data handlers
^^^^^^^^^^^^^^^^^^^^

It is possible to use a custom data handler instead of the standard
:code:`\Cobweb\ExternalImport\Importer::handleArray()` and
:code:`\Cobweb\ExternalImport\Importer::handleXML()`. The value declared
as a custom data handler is a class name:

.. code-block:: php

   $GLOBALS['TCA']['some_table']['external']['general'][0]['data'] = Foo\MyExtension\DataHandler\CustomDataHandler::class;

The class itself **must** implement the
:php:`\Cobweb\ExternalImport\DataHandlerInterface` interface, which contains only
the :code:`handleData()` method. This method will receive two
arguments:

- an array containing the raw data returned by the connector service

- a reference to the calling :php:`\Cobweb\ExternalImport\Importer` object

The method is expected to return a simple PHP array, with indexed
entries, like the standard methods (:code:`\Cobweb\ExternalImport\Importer::handleArray()` and
:code:`\Cobweb\ExternalImport\Importer::handleXML()`).

.. note::

   This was not tested by myself (the extension author). It
   was introduced to answer the particular need to parse large arrays
   using methods similar to XPath. This would have relied on a library
   which was not considered stable enough. Having custom data handlers
   makes it possible.

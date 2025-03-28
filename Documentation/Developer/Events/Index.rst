﻿.. include:: /Includes.rst.txt


.. _developer-events:

Events
^^^^^^

Several events are triggered during the external import process in order
to provide entry points for custom actions, improving the flexibility of
the whole tool. Some events are not triggered when running in
:ref:`preview mode <user-backend-module-synchronizable-preview>`.

All events may throw the special exception :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
This will cause their "parent" step to abort. More details in the chapter about
:ref:`critical exceptions <developer-critical-exceptions>`. Any other exception
will just be logged (depending on your logging configuration).

For usage, see the :ref:`core documentation about PSR-14 events <t3coreapi:EventDispatcher>`.


.. _developer-events-dhange-configuration-before-run:

Change configuration before run
"""""""""""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: ChangeConfigurationBeforeRunEvent

   This event makes it possible to change the External Import at run-time, just before
   any of the import steps are executed. Since the :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`
   model class does not have accessor methods for every single property of the External Import
   configuration, it is not possible to change a single property.

   Basically, the :php:`\Cobweb\ExternalImport\Domain\Model\Configuration` object keeps a copy of the
   general configuration, the additional fields configuration and the columns configuration.
   The best way to change any of them is to retrieve that raw copy, modify it and set it again
   using the accessor methods provided by the event.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.

   .. php:method:: getConfiguration()

      Current instance of :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`.

   .. php:method:: setGeneralConfiguration()

      Used to set the modified version of the general configuration, previously retrieved
      using :code:`$this->getConfiguration()->getRawGeneralConfiguration()`.

   .. php:method:: setAdditionalFieldsConfiguration()

      Used to set the modified version of the additional fields configuration, previously retrieved
      using :code:`$this->getConfiguration()->getRawAdditionalFieldsConfiguration()`.

   .. php:method:: setColumnsConfiguration()

      Used to set the modified version of the columns configuration, previously retrieved
      using :code:`$this->getConfiguration()->getRawColumnsConfiguration()`.


.. _developer-events-process-connector-parameters:

Process connector parameters
""""""""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: ProcessConnectorParametersEvent

   This allows for dynamic manipulation of the
   :ref:`parameters <administration-general-tca-properties-parameters>`
   array before it is passed to the connector.

   .. note::

      This event is also triggered when displaying the configuration in the
      BE module. This way the user can see how the processed parameters
      look like.

   .. php:method:: getParameters()

      Returns the connector parameters.

   .. php:method:: setParameters(array $parameters)

      Sets the (modified) connector parameters.

   .. php:method:: getExternalConfiguration()

      Instance of :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`
      with the current import configuration.


.. _developer-events-substructure-preprocess:

Substructure Preprocess
"""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: SubstructurePreprocessEvent

   This event is triggered whenever a data structure is going to be handled by the
   :ref:`substructureFields <administration-columns-properties-substructure-fields>`
   property. It is fired just before the directives defined in the :code:`substructureFields`
   property are applied and makes it possible to change the substructure.

   .. php:method:: getSubstructureConfiguration()

      Returns the corresponding :code:`substructureFields` configuration.

   .. php:method:: getColumn()

      Returns the name of the column being handled.

   .. php:method:: getDataType()

      Returns the type of data being handled ("array" or "xml").

   .. php:method:: getStructure()

      Returns the structure being handled.

   .. php:method:: setStructure(mixed $structure)

      Sets the (modified) structure. This must be an array for array-type data or
      a :code:`\DomNodeList` for XML-type data. Check the incoming type using the
      :php:`getDataType()` method.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.

.. _developer-events-update-record-preprocess:

Update Record Preprocess
""""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: UpdateRecordPreprocessEvent

   This event is triggered just before a record is registered for update
   in the database. It is triggered for each record individually.

   The event may throw the special exception :php:`\Cobweb\ExternalImport\Exception\InvalidRecordException`,
   in which case the record will be removed from the dataset to be saved.

   .. note::

      This event listener receives records only from the main table, not from any child table.

   .. php:method:: getUid()

      Returns the primary key of the record (since we are talking about an update operation,
      the record exists in the database and thus has a valid primary key).

   .. php:method:: getRecord()

      Returns the record being handled.

   .. php:method:: setRecord(array $record)

      Sets the (modified) record.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-insert-record-preprocess:

Insert Record Preprocess
""""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: InsertRecordPreprocessEvent

   Similar to the "Update Record Preprocess" event above, but for the insert operation.
   It may also throw :php:`\Cobweb\ExternalImport\Exception\InvalidRecordException`.

   .. note::

      This event listener receives records only from the main table, not from any child table.


.. _developer-events-delete-record-preprocess:

Delete Record Preprocess
""""""""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: DeleteRecordsPreprocessEvent

   This event is triggered just before any record is deleted. It can manipulate
   the list of primary keys of records that will eventually be deleted.

   Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
   the data to update or insert will already have been saved.

   .. php:method:: getRecords()

      Returns the list of records to be deleted (primary keys).

      .. note::

         This list of contains only records from the main table, not from any child table.

   .. php:method:: setRecords(array $records)

      Sets the (modified) list of records.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-datamap-postprocess:

Datamap Postprocess
"""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: DatamapPostprocessEvent

   This event is triggered after all records have been updated or inserted using the TYPO3 Core Engine.
   It can be used for any follow-up operation. The event has the following API:

   Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
   the data to update or insert will already have been saved.

   .. note::

      This event is not triggered in preview mode.

   .. php:method:: getData()

      Returns the list of records keyed to their primary keys (including the new primary keys
      for the inserted records). Each record contains an additional field called
      :code:`tx_externalimport:status` with a value of either "insert" or "update"
      depending on which operation was performed on the record.

      .. warning::

         This structure is one-dimensional, which is buggy when multiple tables are handled by the import
         (when using the "children" property), because records with the same primary key will override each
         other. Use :code:`getStructuredData()` instead. Don't use :code:`getData()` anymore, it will be
         dropped in the future.

   .. php:method:: getStructuredData()

      Returns the list of tables and their records keyed to their primary keys (including the new primary keys
      for the inserted records). Each record contains an additional field called
      :code:`tx_externalimport:status` with a value of either "insert" or "update"
      depending on which operation was performed on the record. Example:

      .. code-block:: php

            [
                'pages' => [
                    23 => [
                        'title' => 'Page title'
                    ],
                ],
                'sys_file_reference' => [
                    47 => [
                        'title' => 'Page image'
                    ],
                ],
            ]

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-cmdmap-postprocess:

Cmdmap Postprocess
""""""""""""""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: CmdmapPostprocessEvent

   This event is triggered after all records have been deleted using the TYPO3 Core Engine.
   The event has the following API:

   Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
   the records will already have been deleted.

   .. note::

      This event is not triggered in preview mode.

   .. php:method:: getData()

      Returns the list of primary keys of the deleted records.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-report:

Report
""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: ReportEvent

   This event is triggered in the :php:`ReportEvent` step. It allows for custom reporting.
   It also triggers the :ref:`reporting webhook <user-webhook>`.

   .. php:method:: getImporter()

      Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-get-external-key:

Report
""""""

.. php:namespace:: Cobweb\ExternalImport\Event

.. php:class:: GetExternalKeyEvent

   This event is not related to the import process. It is triggered by the
   "Delete external data" reaction. It makes it possible to retrieve the key
   to the external data, if it is not stored in the "external_id" field as expected.

   .. php:method:: getConfiguration()

      Instance of :php:`\Cobweb\ExternalImport\Domain\Model\Configuration` with the targeted configuration.

   .. php:method:: getData()

      An array with the data for the item to delete.

   .. php:method:: getExternalKey()

      Value of the external key before the event is fired. It will be :code:`null` if the key was not found as expected.

   .. php:method:: setExternalKey()

      Use this method to set the value of the external key, once you have performed your custom processing of the data.

.. include:: ../../Includes.txt


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

For usage, see the :ref:`core documentation about PSR-14 events <t3api:EventDispatcher>`.


.. _developer-events-process-connector-parameters:

Process connector parameters
""""""""""""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent`

This allows for dynamic manipulation of the
:ref:`parameters <administration-general-tca-properties-parameters>`
array before it is passed to the connector. The event has the following API:

getParameters
  Returns the connector parameters.

setParameters
  Sets the (modified) connector parameters.

getExternalConfiguration
  Instance of :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`
  with the current import configuration.

.. note::

   This event is also triggered when displaying the configuration in the
   BE module. This way the user can see how the processed parameters
   look like.


.. _developer-events-substructure-preprocess:

Substructure Preprocess
"""""""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\SubstructurePreprocessEvent`

This event is triggered whenever a data structure is going to be handled by the
:ref:`substructureFields <administration-columns-properties-substructure-fields>`
property. It is fired just before the directives defined in the :code:`substructureFields`
property are applied and makes it possible to change the substructure.
The event has the following API:

getSubstructureConfiguration
  Returns the corresponding :code:`substructureFields` configuration.

getColumn
  Returns the name of the column being handled.

getStructure
  Returns the structure being handled.

setStructure
  Sets the (modified) structure.

getImporter
  Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-update-record-preprocess:

Update Record Preprocess
""""""""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\UpdateRecordPreprocessEvent`

This event is triggered just before a record is registered for update
in the database. It is triggered for each record individually.
The event has the following API:

getUid
  Returns the primary of the record (since we are talking about an update operation,
  the record exists in the database and thus has a valid primary key).

getRecord
  Returns the record being handled.

setRecord
  Sets the (modified) record.

getImporter
  Current instance of :php:`\Cobweb\ExternalImport\Importer`.

.. note::

   This event listener receives records only from the main table, not from any child table.


.. _developer-events-insert-record-preprocess:

Insert Record Preprocess
""""""""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\InsertRecordPreprocessEvent`

Similar to the "Update Record Preprocess" event, but for
the insert operation.

.. note::

   This event listener receives records only from the main table, not from any child table.


.. _developer-events-delete-record-preprocess:

Delete Record Preprocess
""""""""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\DeleteRecordsPreprocessEvent`

This event is triggered just before any record is deleted. It can manipulate
the list of primary keys of records that will eventually be deleted.

Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
the data to update or insert will already have been saved.

The event has the following API:

getRecords
  Returns the list of records to be deleted (primary keys).

  .. note::

     This list of contains only records from the main table, not from any child table.

setRecords
  Sets the (modified) list of records.

getImporter
  Current instance of :php:`\Cobweb\ExternalImport\Importer`.


.. _developer-events-datamap-postprocess:

Datamap Postprocess
"""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\DatamapPostprocessEvent`

This event is triggered after all records have been updated or inserted using the TYPO3 Core Engine.
It can be used for any follow-up operation. The event has the following API:

getData
  Returns the list of records keyed to their primary keys (including the new primary keys
  for the inserted records). Each record contains an additional field called
  :code:`tx_externalimport:status` with a value of either "insert" or "update"
  depending on which operation was performed on the record.

getImporter
  Current instance of :php:`\Cobweb\ExternalImport\Importer`.

Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
the data to update or insert will already have been saved.

.. note::

   This event is not triggered in preview mode.


.. _developer-events-cmdmap-postprocess:

Cmdmap Postprocess
""""""""""""""""""

Class: :php:`\Cobweb\ExternalImport\Event\CmdmapPostprocessEvent`

This event is triggered after all records have been deleted using the TYPO3 Core Engine.
The event has the following API:

getData
  Returns the list of primary keys of the deleted records.

getImporter
  Current instance of :php:`\Cobweb\ExternalImport\Importer`.

Note that even if this event throws the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`,
the records will already have been deleted.

.. note::

   This event is not triggered in preview mode.


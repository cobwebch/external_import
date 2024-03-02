.. include:: ../../Includes.txt


.. _appendix-hooks:
.. _developer-hooks:

Migrating hooks
^^^^^^^^^^^^^^^

.. warning::

   All hooks were removed in version 7.0.0. This chapter was preserved for those still
   using hooks, as it includes replacement instructions. Some hooks should be replaced by
   :ref:`custom process steps <developer-steps>`. For others PSR-14 compliant
   :ref:`events <developer-events>` have been introduced. See below for each hook.


processParameters
  (deprecated)

  .. warning::

     Use the :ref:`Process connector parameters event<developer-events-process-connector-parameters>` instead.

  This allows for dynamic manipulation of the
  :ref:`parameters <administration-general-tca-properties-parameters>`
  array before it is passed to the connector.

  **Example**

  Let's assume that you are using the CSV connector and that you
  would like the filename to automatically adjust to the current year.
  Your parameters could be something like:

  .. code-block:: php

        'parameters' => [
            'filename' => 'fileadmin/imports/data-%Y.csv'
        ]

  Inside the hook, you could run :code:`strftime()` on the
  :code:`filename` parameter in order to replace "%Y" with the
  current year.

  The hook receives the parameters array as the first argument and a
  reference to the current configuration object (an instance of
  class :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`)
  as second argument. It is expected to return the full parameters
  array, even if not modified.

  .. note::

     This hook is also used when displaying the configuration in the
     BE module. This way the user can see how the processed parameters
     look like.

preprocessRawRecordset
  (deprecated)

  This hook makes it possible to manipulate
  the data just after it was fetched from the remote source, but already
  transformed into a PHP array, no matter what the original format. The
  hook receives the full recordset and a back-reference to the calling
  object (an instance of class :php:`\Cobweb\ExternalImport\Importer`) as
  parameters. It is expected to return a full recordset too.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.

  .. note::

     Since External Import version 4.0.0, use a custom step instead,
     using :code:`after:\Cobweb\ExternalImport\Step\HandleDataStep`
     as a position.

validateRawRecordset
  (deprecated)

  This hook is called during the data
  validation step. It is used to perform checks on the nearly raw data
  (it has only been through "preprocessRawRecordset") and decide whether
  to continue the import or not. The hook receives the full recordset
  and a back-reference to the calling object (an instance of class
  :php:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return a boolean, true if the import may continue, false if it must
  be aborted. Note the following: if the minimum number of records
  condition was not matched, the hooks will not be called at all. Import
  is aborted before that. If several methods are registered with the
  hook, the first method that returns false aborts the import. Further
  methods are not called.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.

  .. note::

     Since External Import version 4.0.0, use a custom step instead,
     using :code:`after:\Cobweb\ExternalImport\Step\ValidateDataStep`
     as a position (or :code:`before:` if you want to shortcircuit
     the default validation process).

preprocessRecordset
  (deprecated)

  Similar to "preprocessRawRecordset", but
  after the transformation step, so just before it is stored to the
  database. The hook receives the full recordset and a back-reference to
  the calling object (an instance of class
  :php:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return a full recordset too.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.

  .. note::

     Since External Import version 4.0.0, use a custom step instead,
     using :code:`after:\Cobweb\ExternalImport\Step\TransformDataStep`
     as a position.

updatePreProcess
  (deprecated)

  .. warning::

     Use the :ref:`Update Record Preprocess event<developer-events-update-record-preprocess>` instead.

  This hook can be used to modify a record just
  before it is updated in the database. The hook is called for each
  record that has to be updated. The hook receives the complete record
  and a back-reference to the calling object (an instance of class
  :php:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return the complete record.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.

  .. note::

     This hook receives records only from the main table, not from any child table.

insertPreProcess
  (deprecated)

  .. warning::

     Use the :ref:`Insert Record Preprocess event<developer-events-insert-record-preprocess>` instead.

  Similar to the "updatePreProcess" hook, but for
  the insert operation.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.

  .. note::

     This hook receives records only from the main table, not from any child table.

deletePreProcess
  (deprecated)

  .. warning::

     Use the :ref:`Delete Record Preprocess event<developer-events-delete-record-preprocess>` instead.

     The event does not have a direct access to the main table name. It can be retrieved using:
     :code:`$event->getImporter()->getExternalConfiguration()->getTable`.

  This hook can be used to modify the list of
  records that will be deleted. As a first parameter it receives the name of the main table,
  as a second parameter a list of primary keys, corresponding to the records set for deletion. The
  third parameter is a reference to the calling object (again, an
  instance of class :php:`\Cobweb\ExternalImport\Importer`). The method invoked is
  expected to return a list of primary keys too.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
  However note that the data will already have been saved.

  .. note::

     This hook receives only the list of records to be deleted from the main table,
     not from any child table.

datamapPostProcess
  (deprecated)

  .. warning::

     Use the :ref:`Datamap Postprocess event<developer-events-datamap-postprocess>` instead.

     The event does not have a direct access to the main table name. It can be retrieved using:
     :code:`$event->getImporter()->getExternalConfiguration()->getTable`.

  This hook is called after all records have
  been updated or inserted using the TYPO3 Core Engine. It can be used for any follow-
  up operation. It receives as parameters the name of the affected
  table, the list of records keyed to their uid (including the new uid's
  for the new records) and a back-reference to the calling object (an
  instance of class :php:`\Cobweb\ExternalImport\Importer`). Each record contains
  an additional field called :code:`tx_externalimport:status` which contains
  either "insert" or "update" depending on what operation was performed
  on the record.

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
  However note that the data will already have been saved.

  .. note::

     This hook is not called in preview mode.

cmdmapPostProcess
  (deprecated)

  .. warning::

     Use the :ref:`Cmdmap Postprocess event<developer-events-cmdmap-postprocess>` instead.

     The event does not have a direct access to the main table name. It can be retrieved using:
     :code:`$event->getImporter()->getExternalConfiguration()->getTable`.

  This hook is called after all records have
  been deleted using the TYPO3 Core Engine. It receives as parameters the name of the
  affected table, the list of uid's of the deleted records and a back-
  reference to the calling object (an instance of class
  :php:`\Cobweb\ExternalImport\Importer`).

  This hook may throw the :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
  However note that the data will already have been saved.

  .. note::

     This hook is not called in preview mode.

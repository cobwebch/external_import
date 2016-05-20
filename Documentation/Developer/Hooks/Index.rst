.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _developer-hooks:

Hooks
^^^^^

The external import process contains many hooks for improved
flexibility. They are described below.

processParameters
  This allows for dynamic manipulation of the
  :ref:`parameters <administration-general-tca-properties-parameters>`
  array before it is passed to the connector.

  **Example**

  Let's assume that you are using the CSV connector and that you
  would like the filename to automatically adjust to the current year.
  Your parameters could be something like:

  .. code-block:: php

		'parameters' => array(
			'filename' => 'fileadmin/imports/data-%Y.csv'
		)

  Inside the hook, you could run :code:`strftime()` on the
  :code:`filename` parameter in order to replace "%Y" with the
  current year.

  The hook receives the parameters array as the first argument and a
  back-reference to the calling object (an instance of class :code:`\Cobweb\ExternalImport\Importer`)
  as second argument. It is expected to return the full parameters
  array, even if not modified.

  .. note::

     This hook is also used when displaying the configuration in the
     BE module. This way the user can see how the processed parameters
     look like.

preprocessRawRecordset
  This hook makes it possible to manipulate
  the data just after it was fetched from the remote source, but already
  transformed into a PHP array, no matter what the original format. The
  hook receives the full recordset and a back-reference to the calling
  object (an instance of class :code:`\Cobweb\ExternalImport\Importer`) as
  parameters. It is expected to return a full recordset too.

validateRawRecordset
  This hook is called during the data
  validation step. It is used to perform checks on the nearly raw data
  (it has only been through "preprocessRawRecordset") and decide whether
  to continue the import or not. The hook receives the full recordset
  and a back-reference to the calling object (an instance of class
  :code:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return a boolean, true if the import may continue, false if it must
  be aborted.Note the following: if the minimum number of records
  condition was not matched, the hooks will not be called at all. Import
  is aborted before that. If several methods are registered with the
  hook, the first method that returns false aborts the import. Further
  methods are not called.

preprocessRecordset
  Similar to "preprocessRawRecordset", but
  after the transformation step, so just before it is stored to the
  database. The hook receives the full recordset and a back-reference to
  the calling object (an instance of class
  :code:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return a full recordset too.

updatePreProcess
  This hook can be used to modify a record just
  before it is updated in the database. The hook is called for each
  record that has to be updated. The hook receives the complete record
  and a back-reference to the calling object (an instance of class
  :code:`\Cobweb\ExternalImport\Importer`) as parameters. It is expected
  to return the complete record.

insertPreProcess
  Similar to the "updatePreProcess" hook, but for
  the insert operation.

deletePreProcess
  This hook can be used to modify the list of
  records that will be deleted. As a first parameter it receives a list
  of primary key, corresponding to the records set for deletion. The
  second parameter is a reference to the calling object (again, an
  instance of class :code:`\Cobweb\ExternalImport\Importer`). The method invoked is
  expected to return a list of primary keys too.

datamapPostProcess
  This hook is called after all records have
  been updated or inserted using TCEmain. It can be used for any follow-
  up operation. It receives as parameters the name of the affected
  table, the list of records keyed to their uid (including the new uid's
  for the new records) and a back-reference to the calling object (an
  instance of class :code:`\Cobweb\ExternalImport\Importer`). Each record contains
  an additional field called :code:`tx_externalimport:status` which contains
  either "insert" or "update" depending on what operation was performed
  on the record.

cmdmapPostProcess
  This hook is called after all records have
  been deleted using TCEmain. It receives as parameters the name of the
  affected table, the list of uid's of the deleted records and a back-
  reference to the calling object (an instance of class
  :code:`\Cobweb\ExternalImport\Importer`).

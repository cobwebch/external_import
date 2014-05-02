.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _administration-general-tca:

General TCA configuration
^^^^^^^^^^^^^^^^^^^^^^^^^

Here is an example of a typical "ctrl" section syntax:

.. code-block:: php

	$GLOBALS['TCA']['tx_externalimporttut_departments'] = array(
		'ctrl' => array(
			'title' => 'LLL:EXT:externalimport_tut/locallang_db.xml:tx_externalimporttut_departments',
			...
			'external' => array(
				0 => array(
					'connector' => 'csv',
					'parameters' => array(
						'filename' => $extensionPath . 'res/departments.txt',
						'delimiter' => "\t",
						'text_qualifier' => '"',
						'skip_rows' => 1,
						'encoding' => 'latin1'
					),
					'data' => 'array',
					'reference_uid' => 'code',
					'priority' => 10,
					'description' => 'Import of all company departments'
				)
			)
		),
	);


The :code:`external` property is an indexed array. The available properties
are described below.


.. _administration-general-tca-properties:

Properties
^^^^^^^^^^

.. container:: ts-properties

	===================================== =============
	Property                              Data type
	===================================== =============
	`additional\_fields`_                 string
	clearCache_                           integer
	connector_                            string
	data_                                 string
	dataHandler_                          string
	deleteNotSynchedRecords_              boolean
	description_                          string
	disabledOperations_                   string
	enforcePid_                           boolean
	minimumRecords_                       integer
	namespaces_                           array
	nodetype_                             string
	parameters_                           array
	pid_                                  integer
	priority_                             integer
	`reference\_uid`_                     string
	`where\_clause`_                      string
	===================================== =============


.. _administration-general-tca-properties-connector:

connector
~~~~~~~~~

Type
  string

Description
  Connector service subtype.

  Must be defined only for pulling data. Leave blank for pushing data.

Scope
  Fetch data


.. _administration-general-tca-properties-parameters:

parameters
~~~~~~~~~~

Type
  array

Description
  Array of parameters that must be passed to the connector service.

  Not used when pushing data.

Scope
  Fetch data


.. _administration-general-tca-properties-data:

data
~~~~

Type
  string

Description
  The format in which the data is returned by the connector service. Can
  be either :code:`xml` or :code:`array`.

Scope
  Fetch data


.. _administration-general-tca-properties-datahandler:

dataHandler
~~~~~~~~~~~

Type
  string

Description
  A class name for replacing the standard data handlers. See the
  :ref:`Developer's Guide <developer>` for more details.

Scope
  Handle data


.. _administration-general-tca-properties-nodetype:

nodetype
~~~~~~~~

Type
  string

Description
  Name of the reference nodes inside the XML structure, i.e. the
  children of these nodes correspond to the data that goes into the
  database fields (see also the description of the
  :ref:`field <administration-columns-properties-field>`
  attribute).

Scope
  Handle data (XML)


.. _administration-general-tca-properties-reference-uid:

reference\_uid
~~~~~~~~~~~~~~

Type
  string

Description
  Name of the column where the equivalent of a primary key for the
  external data is stored.

Scope
  Store data


.. _administration-general-tca-properties-priority:

priority
~~~~~~~~

Type
  integer

Description
  A level of priority for execution of the synchronization. Some tables
  may need to be synchronized before others if foreign relations are to
  be established. This gives a clue to the user and a strict order for
  scheduled synchronizations.

  Not used when pushing data.

Scope
  Display/Automated import process


.. _administration-general-tca-properties-pid:

pid
~~~

Type
  string

Description
  ID of the page where the imported records should be stored. Can be
  ignored and the general storage pid is used instead
  (:ref:`see Configuration <configuration>`).

Scope
  Store data


.. _administration-general-tca-properties-enforcepid:

enforcePid
~~~~~~~~~~

Type
  boolean

Description
  If this is set to true, all operations regarding existing records will
  be limited to records stored in the defined pid (i.e. either the above
  property or the general extension configuration). This has two
  consequences:

  #. when checking for existing records, those records will be selected
     only from the defined pid.

  #. when checking for records to delete, only records from the defined pid
     will be affected

  This is a convenient way of protecting records from operations started
  from within the external import process, so that it won't affect e.g.
  records created manually.

Scope
  Store data


.. _administration-general-tca-properties-where-clause:

where\_clause
~~~~~~~~~~~~~

Type
  string

Description
  SQL condition that will restrict the records considered during the
  import process. Only records matching the condition will be updated or
  deleted. This condition comes on top of the "enforcePid" condition, if
  defined.

  .. warning::

     This may cause many records to be inserted over time.
     Indeed if some external data is imported the first time, but then
     doesn't match the :code:`where_clause` condition, it will never be found
     for update. It will thus be inserted again and again. Whenever you
     make use of the :code:`where_clause` property you should therefore watch
     for an unexpectedly high number of inserts.

Scope
  Store data


.. _administration-general-tca-properties-additional-fields:

additional\_fields
~~~~~~~~~~~~~~~~~~

Type
  string

Description
  Comma-separated list of fields from the external source that should be
  made available during the import process, but that will not be stored
  in the internal table.

  This is usually the case for fields which you want to use in the
  transformation step, but that will not be stored eventually.

Scope
  Fetch data


.. _administration-general-tca-properties-namespaces:

namespaces
~~~~~~~~~~

Type
  array

Description
  Associative array of namespaces that can be used in XPath queries (see
  "Columns Configuration" below). The keys correspond to prefixes and
  the values to URIs. The prefixes can then be used in XPath queries.

  **Example**

  Given the following declaration:

  .. code-block:: php

     'namespaces' => array(
        'atom' => 'http://www.w3.org/2005/Atom'
     )

  a Xpath query like:

  .. code-block:: text

     atom:link

  could be used. The prefixes used for XPath queries don't need to match
  the prefixes used in the actual XML source. The defaut namespace has
  to be registered too in order for XPath queries to succeed.

Scope
  Handle data (XML)


.. _administration-general-tca-properties-description:

description
~~~~~~~~~~~

Type
  string

Description
  A purely descriptive piece of text, which should help you remember
  what this particular synchronization is all about. Particularly useful
  when a table is synchronized with multiple sources.

Scope
  Display


.. _administration-general-tca-properties-disabledoperations:

disabledOperations
~~~~~~~~~~~~~~~~~~

Type
  string

Description
  Comma-separated list of operations that should **not** be performed.
  Possible operations are insert, update and delete. This way you can
  block any of these operations.

  insert
    The operation performed when new records are found in
    the external source.

  update
    Performed when a record already exists and only its data
    needs to be updated.

  delete
    Performed when a record is in the database, but is not
    found in the external source anymore.

  See also the column-specific property
  :ref:`disabledOperations <administration-columns-properties-disabledoperations>`.

Scope
  Store data


.. _administration-general-tca-properties-minimumrecords:

minimumRecords
~~~~~~~~~~~~~~

Type
  integer

Description
  Minimum number of items expected in the external data. If fewer items
  are present, the import is aborted. This can be used – for example –
  to protect the existing data against deletion when the fetching of the
  external data failed (in which case there are no items to import).

Scope
  Validate data


.. _administration-general-tca-properties-disablelog:

disableLog
~~~~~~~~~~

Type
  integer

Description
  Set to :code:`TRUE` to disable logging by TCEmain. This setting will override
  the general "Disable logging" setting
  (see :ref:`Configuration for more details <configuration>`).

Scope
  Store data


.. _administration-general-tca-properties-clearcache:

clearCache
~~~~~~~~~~

Type
  integer

Description
  Comma-separated list of pages whose cache should be cleared at the end
  of the import process. See :ref:`Clearing the cache <user-clear-cache>`.

Scope
  Store data


.. _administration-general-tca-properties-deletenotsynchedrecords:

deleteNotSynchedRecords
~~~~~~~~~~~~~~~~~~~~~~~

Type
  boolean

Description
  **Deprecated**. Use :ref:`disabledOperations <administration-general-tca-properties-disabledoperations>` instead.

Scope
  Store data

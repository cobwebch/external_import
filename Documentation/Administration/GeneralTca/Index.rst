.. include:: ../../Includes.txt


.. _administration-general-tca:

General TCA configuration
^^^^^^^^^^^^^^^^^^^^^^^^^

Here is an example of a typical "ctrl" section syntax:

.. code-block:: php

        $extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('externalimport_tut');

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
					'referenceUid' => 'code',
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
""""""""""

.. container:: ts-properties

	===================================== ================= ========================
	Property                              Data type         Scope/Step
	===================================== ================= ========================
	additionalFields_                     string            Read data
	clearCache_                           string            Clear cache
	connector_                            string            Read data
	customSteps_                          array             Any step
	data_                                 string            Read data
	dataHandler_                          string            Handle data
	description_                          string            Display
	disabledOperations_                   string            Store data
	disableLog_                           boolean           Store data
	enforcePid_                           boolean           Store data
	group_                                string            Sync process
	minimumRecords_                       integer           Validate data
	namespaces_                           array             Handle data (XML)
	nodetype_                             string            Handle data (XML)
	nodepath_                             string            Handle data (XML)
	parameters_                           array             Read data
	pid_                                  integer           Store data
	priority_                             integer           Display/automated import
	referenceUid_                         string            Store data
    updateSlugs_                          boolean           Store data
	useColumnIndex_                       string or integer Configuration
	whereClause_                          string            Store data
	===================================== ================= ========================


.. _administration-general-tca-properties-connector:

connector
~~~~~~~~~

Type
  string

Description
  Connector service subtype.

  Must be defined only for pulling data. Leave blank for pushing data.
  You will need to install the relevant connector extension. Here is a list
  of available extensions and their corresponding types:

  ====  =================
  Type  Extension
  ====  =================
  csv   svconnector_csv
  json  svconnector_json
  sql   svconnector_sql
  feed  svconnector_feed
  ====  =================

Scope
  Read data


.. _administration-general-tca-properties-parameters:

parameters
~~~~~~~~~~

Type
  array

Description
  Array of parameters that must be passed to the connector service.

  Not used when pushing data.

Scope
  Read data


.. _administration-general-tca-properties-data:

data
~~~~

Type
  string

Description
  The format in which the data is returned by the connector service. Can
  be either :code:`xml` or :code:`array`.

Scope
  Read data


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


.. _administration-general-tca-properties-group:

group
~~~~~

Type
  string

Description
  This can be any arbitrary string of characters. All External Import
  configurations having the same value for the "group" property will
  form a group of configurations. It is then possible to execute the
  synchronization of all configurations in the group in one go, in
  order of priority. Group synchronization is available on the command
  line and in the Scheduler task.

Scope
  Sync process


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


.. _administration-general-tca-properties-nodepath:

nodepath
~~~~~~~~

Type
  string

Description
  XPath expression for selecting the reference nodes inside the XML structure.
  This is an alternative to the :ref:`nodetype <administration-general-tca-properties-nodetype>`
  property and will take precedence if both are defined.

Scope
  Handle data (XML)


.. _administration-general-tca-properties-reference-uid:

referenceUid
~~~~~~~~~~~~

Type
  string

Description
  Name of the column where the equivalent of a primary key for the
  external data is stored.

  .. important::

     This is the name of a field in the TYPO3 CMS database, not in
     the external data! It is the field where the reference
     (or primary) key of the external data is stored.

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


.. _administration-general-tca-properties-usecolumnindex:

useColumnIndex
~~~~~~~~~~~~~~

Type
  string or integer

Description
  In a basic configuration the same index must be used for the general
  TCA configuration and for each column configuration. With this property
  it is possible to use a different index for the column configurations.
  The "ctrl" part has to exist with its own index, but the columns may refer
  to another index and thus their configuration does not need to be defined.
  Obviously the index referred to must exist for columns.

  The type may be a string or an integer, because a configuration key
  may also be either a string or an integer.

Scope
  Configuration


.. _administration-general-tca-properties-customsteps:

customSteps
~~~~~~~~~~~

Type
  array

Description
  As explained in the :ref:`process overview <user-overview>`, the import
  process goes through several steps, depending on its type. This property
  makes it possible to register additional steps. Each step can be placed
  before or after any existing step (including previously registered custom
  steps).

  The configuration is a simple array, each entry being itself an array with
  two properties: "class" referring to the PHP class containing the custom step
  code and "position" stating when the new step should happen. The syntax for
  position is made of the keyword :code:`before` or :code:`after`, followed by
  a colon (:code:`:`) and the name of an existing step class.

  Example:

  .. code-block:: php

       'customSteps' => array(
               array(
                       'class' => \Cobweb\ExternalimportTest\Step\EnhanceDataStep::class,
                       'position' => 'after:' . \Cobweb\ExternalImport\Step\ValidateDataStep::class
               )
       ),

  If any element of the custom step declaration is invalid, the step will be
  ignored. More information is given in the :ref:`Developer's Guide <developer-steps>`.

Scope
  Any step


.. _administration-general-tca-properties-where-clause:

whereClause
~~~~~~~~~~~

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
     doesn't match the :code:`whereClause` condition, it will never be found
     for update. It will thus be inserted again and again. Whenever you
     make use of the :code:`whereClause` property you should therefore watch
     for an unexpectedly high number of inserts.

Scope
  Store data


.. _administration-general-tca-properties-additional-fields:

additionalFields
~~~~~~~~~~~~~~~~

Type
  string

Description
  Comma-separated list of fields from the external source that should be
  made available during the import process, but that will not be stored
  in the internal table.

  This is usually the case for fields which you want to use in the
  transformation step, but that will not be stored eventually.

Scope
  Read data


.. _administration-general-tca-properties-update-slugs:

updateSlugs
~~~~~~~~~~~

Type
  boolean

Description
  Slugs are populated automatically for new records thanks to External Import relying on the
  :php:`\TYPO3\CMS\Core\DataHandling\DataHandler` class. The same is not true for updated records.
  If you want record slugs to be updated when modified external data is imported, set this
  flag to :php:`true`.

Scope
  Store data


.. _administration-general-tca-properties-namespaces:

namespaces
~~~~~~~~~~

Type
  array

Description
  Associative array of namespaces that can be used in
  :ref:`XPath queries <administration-columns-properties-xpath>`.
  The keys correspond to prefixes and the values to URIs.
  The prefixes can then be used in XPath queries.

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
  Set to :code:`true` to disable logging by the TYP3 Core Engine. This setting will override
  the general "Disable logging" setting
  (see :ref:`Configuration for more details <configuration>`).

Scope
  Store data


.. _administration-general-tca-properties-clearcache:

clearCache
~~~~~~~~~~

Type
  string

Description
  Comma-separated list of caches identifiers for caches which should be cleared
  at the end of the import process. See :ref:`Clearing the cache <user-clear-cache>`.

Scope
  Clear cache

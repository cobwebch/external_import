.. include:: ../../Includes.txt


.. _administration-general-tca:

General TCA configuration
^^^^^^^^^^^^^^^^^^^^^^^^^

Here is an example of a typical general section syntax, containing two import configurations.

Each configuration must be identified with a key (in the example below, :code:`0` and  :code:`'api'`).
The same keys need to be used again in the :ref:`column configuration <administration-columns>`.

.. code-block:: php

	$GLOBALS['TCA']['tx_externalimporttest_tag'] = array_merge( $GLOBALS['TCA']['tx_externalimporttest_tag'], [
        'external' => [
             'general' => [
                  0 => [
                       'connector' => 'csv',
                       'parameters' => [
                            'filename' => 'EXT:externalimport_test/Resources/Private/ImportData/Test/Tags.txt',
                            'delimiter' => ';',
                            'text_qualifier' => '"',
                            'encoding' => 'utf8',
                            'skip_rows' => 1
                       ],
                       'data' => 'array',
                       'referenceUid' => 'code',
                       'priority' => 5000,
                       'description' => 'List of tags'
                  ],
                  'api' => [
                       'data' => 'array',
                       'referenceUid' => 'code',
                       'description' => 'Tags defined via the import API'
                  ]
             ]
        ],
	]);


All available properties are described below.


.. _administration-general-tca-properties:

Properties
""""""""""

.. container:: ts-properties

   ===================================== ================= ========================
   Property                              Data type         Scope/Step
   ===================================== ================= ========================
   additionalFields_                     string            Read data
   arrayPath_                            string            Handle data (array)
   arrayPathFlatten_                     bool              Handle data (array)
   arrayPathSeparator_                   string            Handle data (array)
   clearCache_                           string            Clear cache
   columnsOrder_                         string            Transform data
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
  order of priority (lowest goes first). Group synchronization is available on the command
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


.. _administration-general-tca-properties-arraypath:

arrayPath
~~~~~~~~~

Type
  string

Description
  Pointer to a sub-array inside the incoming external data, as a list of keys
  separated by some marker. The sub-array pointed to will be used
  as the source of data in the subsenquent steps, rather than the whole structure
  that was read during the :code:`ReadDataStep`.

  For more details on usage and available options, :ref:`see the dedicated page <administration-array-path>`.

Scope
  Handle data (array)


.. _administration-general-tca-properties-arraypathflatten:

arrayPathFlatten
~~~~~~~~~~~~~~~~

Type
  bool

Description
  When the special :code:`*` segment is used in an :ref:`arrayPath <administration-general-tca-properties-array-path>`,
  the resulting structure is always an array. If the :code:`arrayPath` target is
  actually a single value, this may not be desirable. When :code:`arrayPathFlatten`
  is set to :code:`true`, the result is preserved as a simple type.

  .. note::

     If the :code:`arrayPath` property uses the special :code:`*` segment several times,
     :code:`arrayPathFlatten` will apply only to the last occurrence. The reason is that
     the method which traverses the array structure is called recursively on each :code:`*` segment.
     When the result of the final call is flattened, a simple type is returned back up the
     call chain, which means that :code:`arrayPathFlatten` has no further effect.

Scope
  Handle data (array)


.. _administration-general-tca-properties-arraypathseparator:

arrayPathSeparator
~~~~~~~~~~~~~~~~~~

Type
  string

Description
  Separator to use in the :ref:`arrayPath <administration-general-tca-properties-arraypath>` property.
  Defaults to :code:`/` if this property is not defined.

Scope
  Handle data (array)


.. _administration-general-tca-properties-reference-uid:

referenceUid
~~~~~~~~~~~~

Type
  string

Description
  Name of the column where the equivalent of a primary key for the
  external data is stored.

  Records for which this data does not exist are skipped (since version 6.1).
  This is tested with PHP's :code:`isset()` function. If you think your data
  may contain empty values and you wish to skip them too, use the
  :ref:`isEmpty <administration-transformations-properties-isempty>` transformation
  property with the :code:`invalidate` option set to :code:`true`.

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
  A level of priority for the execution of the synchronization. Some tables
  may need to be synchronized before others if foreign relations are to
  be established. This gives a clue to the user and a strict order for
  scheduled synchronizations (either when synchronizing all configurations
  or when synchronizing a :ref:`group <administration-general-tca-properties-group>`).

  The lowest priority value goes first.

  If priority is not defined, a default value of 1000 is applied
  (defined by class constant :code:`\Cobweb\ExternalImport\Importer::DEFAULT_PRIORITY`).

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
  (:ref:`see Configuration <installation-configuration>`).

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
  The general configuration part has to exist with its own index (say "index A"), but the columns may refer
  to another index (say "index B") and thus their configuration does not need to be defined.
  Obviously the index referred to ("index B") must exist for columns.

  The type may be a string or an integer, because a configuration key
  may also be either a string or an integer.

  Since version 6.1, it is possible to define specific configurations for selected
  columns using the index from the general configuration ("index A"). It will not
  be overridden by the configuration corresponding to the index referred to with
  :code:`useColumnIndex` property ("index B").

  Example:

  .. code-block:: php

      'stable' => [
          'connector' => 'feed',
          'parameters' => [
              'uri' => 'EXT:externalimport_test/Resources/Private/ImportData/Test/StableProducts.xml',
              'encoding' => 'utf8'
          ],
          'group' => 'Products',
          'data' => 'xml',
          'nodetype' => 'products',
          'referenceUid' => 'sku',
          'priority' => 5120,
          'useColumnIndex' => 'base',
          ...
      ],

  This general configuration makes reference to the "base" configuration. This means
  that all columns will use the "base" configuration, unless they have a configuration
  using specifically the "stable" index. So the "sku" column will use the configuration
  from the "base" index:

  .. code-block:: php

     'sku' => [
         'exclude' => false,
         'label' => 'SKU',
         'config' => [
             'type' => 'input',
             'size' => 10
         ],
         'external' => [
             'base' => [
                 'xpath' => './self::*[@type="current"]/item',
                 'attribute' => 'sku'
             ],
             'products_for_stores' => [
                 'field' => 'product'
             ],
             'updated_products' => [
                 'field' => 'product_sku'
             ]
         ]
     ],

  However, the "name" column has a specific configuration corresponding to the "stable"
  index, so it will be used, and not the configuration from the "base" index:

  .. code-block:: php

     'name' => [
         'exclude' => false,
         'label' => 'Name',
         'config' => [
             'type' => 'input',
             'size' => 30,
             'eval' => 'required,trim',
         ],
         'external' => [
             'base' => [
                 'xpath' => './self::*[@type="current"]/item',
             ],
             'stable' => [
                 'xpath' => './self::*[@type="current"]/item',
                 'transformations' => [
                     10 => [
                         'userFunction' => [
                             'class' => \Cobweb\ExternalimportTest\UserFunction\Transformation::class,
                             'method' => 'caseTransformation',
                             'parameters' => [
                                 'transformation' => 'upper'
                             ]
                         ]
                     ]
                 ]
             ],
             'updated_products' => [
                 'field' => 'name'
             ]
         ]
     ],

Scope
  Configuration


.. _administration-general-tca-properties-columnsorder:

columnsOrder
~~~~~~~~~~~~

Type
  string

Description
  By default, columns (regular columns or additional fields) are handled in alphabetical
  order whenever a loop is performed on all columns (typically in the :php:`\Cobweb\ExternalImport\Step\TransformDataStep`
  class). This can be an issue when you need a specific column to be handled before
  another one.

  With this property, you can define a comma-separated list of columns, that will
  be handled in that specific order. It is not necessary to define an order for all columns.
  If only some columns are explicitly ordered, the rest will be handled after the ordered
  ones, in alphabetical order. The order is visually reflected in the backend module,
  when viewing the :ref:`configuration details <user-backend-module-synchronizable-details>`.

Scope
  Transform data (essentially)


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
  three properties:

  - **class (required)**: name of the PHP class containing the custom step.
  - **position (required)**: states when the new step should happen. The syntax for
    position is made of the keyword :code:`before` or :code:`after`, followed by
    a colon (:code:`:`) and the name of an existing step class.
  - **parameters (optional)**: array which is passed as is to the custom step class
    when it is called during the import process. Inside the step, it can be accessed
    using :code:`$this->parameters`.

  Example:

  .. code-block:: php

       'customSteps' => [
               [
                       'class' => \Cobweb\ExternalimportTest\Step\EnhanceDataStep::class,
                       'position' => 'after:' . \Cobweb\ExternalImport\Step\ValidateDataStep::class
               ]
       ],

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
  This property is not part of the general configuration anymore. Please refer to
  :ref:`the dedicated chapter <administration-additionalfields>`.

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
  (see :ref:`Configuration for more details <installation-configuration>`).

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

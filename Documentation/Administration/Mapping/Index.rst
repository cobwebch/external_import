.. include:: ../../Includes.txt


.. _administration-mapping:

Mapping configuration
^^^^^^^^^^^^^^^^^^^^^

The external values can be matched to values from an existing
TYPO3 CMS table, using the "mapping" property, which has its own
set of properties. They are described below.


.. _administration-mapping-properties:

Properties
""""""""""

.. container:: ts-properties

   ========================== =============
   Property                   Data type
   ========================== =============
   default_                   mixed
   matchMethod_               string
   matchSymmetric_            boolean
   multipleValuesSeparator_   string
   referenceField_            string
   table_                     string
   valueField_                string
   valueMap_                  array
   whereClause_               string
   ========================== =============


.. _administration-mapping-properties-table:

table
~~~~~

Type
  string

Description
  Name of the table to read the mapping data from.

Scope
  Transform data


.. _administration-mapping-properties-reference-field:

referenceField
~~~~~~~~~~~~~~

Type
  string

Description
  Name of the field against which external values must be matched.

  .. note::

     SQL functions may be used here. Example: :php:`'CONCAT(first_name, \' \', last_name)'`.

Scope
  Transform data


.. _administration-mapping-properties-value-field:

valueField
~~~~~~~~~~

Type
  string

Description
  Name of the field to take the mapped value from. If not defined, this
  will default to "uid".

  .. note::

     SQL functions may be used here. Example: :php:`'CONCAT(first_name, \' \', last_name)'`.

Scope
  Transform data


.. _administration-mapping-properties-where-clause:

whereClause
~~~~~~~~~~~

Type
  string

Description
  SQL condition (without the "WHERE" keyword) to apply to the referenced
  table. This is typically meant to be a mirror of the
  :ref:`foreign_table_where <t3tca:columns-select-properties-foreign-table-where>`
  property of select-type fields.

  However only one marker is supported in this case: :code:`###PID_IN_USE###`
  which will be replaced by the current storage pid. So if you have
  something like:

  .. code-block:: php

     'foreign_table_where' => 'AND pid = ###PAGE_TSCONFIG_ID###'

  in the TCA for your column, you should replace the marker by a hard-
  coded value instead for external import, e.g.

  .. code-block:: php

     'whereClause' => 'pid = 42'

  .. important::

     The clause must start with neither the "WHERE", nor the "AND" keyword.

Scope
  Transform data


.. _administration-mapping-properties-default:

default
~~~~~~~

Type
  mixed

Description
  Default value that will be used when a value cannot be mapped. Otherwise the field is unset for the record.

  .. note::

     This is quite important when mapping MM relations. If an existing item has currently relations in the
     TYPO3 database, but not any longer in the data to be imported, the existing MM relations will not be
     removed if the field is unset. In such a case, make sure to use an empty string for the default value,
     as this will tell the DataHandler that it has to remove the existing MM relations.

     **Example**

     .. code-block:: php

          $GLOBALS['TCA']['tx_externalimporttest_product']['columns']['categories']['external']['base'] = [
               'xpath' => './self::*[@type="current"]/category',
               'transformations' => [
                    10 => [
                         'mapping' => [
                              'table' => 'sys_category',
                              'referenceField' => 'external_key',
                              'default' => ''
                         ]
                    ]
               ]
          ];

Scope
  Transform data


.. _administration-mapping-properties-valuemap:

valueMap
~~~~~~~~

Type
  array

Description
  Fixed hash table for mapping. Instead of using a database table to
  match external values to internal values, this property makes it
  possible to use a simple list of key-value pairs. The keys correspond
  to the external values.

Scope
  Transform data


.. _administration-mapping-properties-multiplevaluesseparator:

multipleValuesSeparator
~~~~~~~~~~~~~~~~~~~~~~~

Type
  string

Description
  Set this property if the field to map contains several values,
  separated by some symbol (for example, a comma). The values will
  be split using the symbol defined in this property and each resulting
  value will go through the mapping process.

  This makes it possible to handle 1:n or m:n relations, where the
  incoming values are all stored in the same field.

  .. note::

     This property does nothing when used in combination with the
     :ref:`MM property <administration-mm>`, because we expect normalized
     data with one and denormalized data with the other. The chapter about
     :ref:`mapping data <user-mapping-data-mm>` hopefully helps understand this.

Scope
  Transform data


.. _administration-mapping-properties-match-method:

matchMethod
~~~~~~~~~~~

Type
  array

Description
  Value can be "strpos" or "stripos".

  Normally mapping values are matched based on a strict equality. This
  property can be used to match in a "softer" way. It will match if the
  external value is found inside the values pointed to by the
  :ref:`referenceField <administration-mapping-properties-reference-field>`
  property. "strpos" will perform a case-sensitive
  matching, while "stripos" is case-unsensitive.

  Caution should be exercised when this property is used. Since the
  matching is less strict it may lead to false positives. You should
  review the data after such an import.

  .. note::

     It is important to understand how the :code:`matchMethod` property
     influences the matching process. Consider trying to map freely input
     country names to the :code:`static_countries` table inside TYPO3 CMS.
     This may not be so easy depending on how names were input in the
     external data. For example, "Australia" will not strictly match the
     official name, which is "Commonwealth of Australia". However setting
     :code:`matchMethod` to "strpos" will generate a match, since "Australia"
     can be found inside "Commonwealth of Australia"


Scope
  Transform data


.. _administration-mapping-properties-match-symmetric:

matchSymmetric
~~~~~~~~~~~~~~

Type
  boolean

Description
  This property complements :ref:`matchMethod <administration-mapping-properties-match-method>`.
  If set to :code:`true`, the import process will not only
  try to match the external value inside the mapping values,
  but also the reverse, i.e. the mapping values
  inside the external value.

Scope
  Transform data


.. _administration-mapping-example:

Examples
""""""""

.. _administration-mapping-example-simple:

Simple mapping
~~~~~~~~~~~~~~

Here's an example TCA configuration.

.. code-block:: php

	$GLOBALS['TCA']['fe_users']['columns']['tx_externalimporttut_department']['external'] = [
		0 => [
			'field' => 'department',
			'mapping' => [
				'table' => 'tx_externalimporttut_departments',
				'referenceField' => 'code'
			]
		]
	];

The value found in the "department" field of the external data
will be matched to the "code" field of the "tx_externalimporttut_departments" table,
and thus create a relation between the "fe_users" and the
"tx_externalimporttut_departments" table.


.. _administration-mapping-example-multiple:

Mapping multiple values
~~~~~~~~~~~~~~~~~~~~~~~

This second example demonstrates usage of the
:ref:`multipleValuesSeparator <administration-mapping-properties-multiplevaluesseparator>`
property.

The incoming data looks like:

.. code-block:: xml

	<catalogue>
		<products type="current">
			<item sku="000001">Long sword</item>
			<tags>attack,metal</tags>
		</products>
		<products type="obsolete">
			<item index="000002">Solar cream</item>
		</products>
		<products type="current">
			<item sku="000005">Chain mail</item>
			<tags>defense,metal</tags>
		</products>
		<item sku="000014" type="current">Out of structure</item>
	</catalogue>

and the external import configuration like:

.. code-block:: php

	$GLOBALS['TCA']['tx_externalimporttest_product']['columns']['tags']['external'] = [
      'base' => [
          'xpath' => './self::*[@type="current"]/tags',
          'transformations' => [
               10 => [
                    'mapping' => [
                         'table' => 'tx_externalimporttest_tag',
                         'referenceField' => 'code',
                         'multipleValuesSeparator' => ','
                    ]
               ]
          ]
      ]
	];

The values in the :code:`<tags>` nodes will be split on the
comma and each will be matched to a tag from "tx_externalimporttest_tag"
table, using the "code" field for matching.

This example is taken from the "externalimport_test" extension.

.. include:: /Includes.rst.txt


.. _administration-columns:

Columns configuration
^^^^^^^^^^^^^^^^^^^^^

You also need an "external" syntax for each column to define
which external data goes into that column and any handling that might
apply. This is also an indexed array. Obviously indices used for each
column must relate to the indices used in the :ref:`general configuration <administration-general-tca>`.
In its simplest form this is just a reference to the external data's name:

.. code-block:: php

    'code' => [
        'exclude' => 0,
        'label' => 'LLL:EXT:externalimport_tut/locallang_db.xml:tx_externalimporttut_departments.code',
        'config' => [
            'type' => 'input',
            'size' => 10,
            'max' => 4,
            'eval' => 'required,trim',
        ],
        'external' => [
            0 => [
                'field' => 'code'
            ]
        ]
    ],

The properties for the columns configuration are described below.

.. warning::

   Columns "crdate", "tstamp" and "cruser_id" cannot be mapped as they are overwritten by the
   :php:`DataHandler`. If you need to manipulate these columns you should use the
   :ref:`Datamap Postprocess event <developer-events-datamap-postprocess>` or the
   :ref:`Cmdmap Postprocess event <developer-events-cmdmap-postprocess>`
   which are triggered after :php:`DataHandler` operations.

.. hint::

   You can set static values by using the :ref:`transformation > value <administration-columns-properties-value>`.


.. _administration-columns-properties:

Properties
""""""""""

.. container:: ts-properties

   ========================= ====================================================================== ===================
   Property                  Data type                                                              Step/Scope
   ========================= ====================================================================== ===================
   arrayPath_                string                                                                 Handle data (array)
   arrayPathSeparator_       string                                                                 Handle data (array)
   arrayPathFlatten_         bool                                                                   Handle data (array)
   attribute_                string                                                                 Handle data (XML)
   attributeNS_              string                                                                 Handle data (XML)
   children_                 :ref:`Children records configuration <administration-children>`        Store data
   disabledOperations_       string                                                                 Store data
   field_                    string                                                                 Handle data
   fieldNS_                  string                                                                 Handle data (XML)
   multipleRows_             boolean                                                                Store data
   multipleSorting_          string                                                                 Store data
   substructureFields_       array                                                                  Handle data
   transformations_          :ref:`Transformations configuration <administration-transformations>`  Transform data
   value_                    Simple type (string, integer, float, boolean)                          Handle data
   xmlValue_                 boolean                                                                Handle data (XML)
   xpath_                    string                                                                 Handle data (XML)
   ========================= ====================================================================== ===================


.. _administration-columns-properties-value:

value
~~~~~

Type
  Simple type (string, integer, float, boolean)

Description
  Sets a fixed value, independent of the data being imported.
  For example, this might be used to set a flag for all imported
  records. Or you might want to use different types for different import sources.

  This can be used for both array-type and XML-type data.

Scope
  Handle data


.. _administration-columns-properties-field:

field
~~~~~

Type
  string

Description
  Name or index of the field (or node, in the case of XML data) that
  contains the data in the external source.

  For array-type data, this information is mandatory. For XML-type data,
  it can be left out. In such a case, the value of the current node
  itself will be used, or an attribute of said node, if the
  :ref:`attribute <administration-columns-properties-attribute>`
  property is also defined.

Scope
  Handle data


.. _administration-columns-properties-array-path:

arrayPath
~~~~~~~~~

Type
  string

Description
  Replaces the :ref:`field <administration-columns-properties-field>` property for pointing
  to a field in a "deeper" position inside a multidimensional array. The value is a string
  comprised of the keys for pointing into the array, separated by some character.

  For more details on usage and available options, :ref:`see the dedicated page <administration-array-path>`.

  Works only for array-type data.

  If both "field" and "arrayPath" are defined, the latter takes precedence.

Scope
  Handle data (array)


.. _administration-columns-properties-arraypathflatten:

arrayPathFlatten
~~~~~~~~~~~~~~~~

Type
  bool

Description
  When the special :code:`*` segment is used in an :ref:`arrayPath <administration-columns-properties-array-path>`,
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


.. _administration-columns-properties-array-path-separator:

arrayPathSeparator
~~~~~~~~~~~~~~~~~~

Type
  string

Description
  Separator to use in the :ref:`arrayPath <administration-columns-properties-array-path>` property.
  Defaults to :code:`/` if this property is not defined.

Scope
  Handle data (array)


.. _administration-columns-properties-attribute:

attribute
~~~~~~~~~

Type
  string

Description
   If the data is of type XML, use this property to retrieve the value
   from an attribute of the node rather than the value of the node itself.

   This applies to the node selected with the :ref:`field <administration-columns-properties-field>`
   property or to the current node if :ref:`field <administration-columns-properties-field>`
   is not defined.

Scope
  Handle data (XML)


.. _administration-columns-properties-xpath:

xpath
~~~~~

Type
  string

Description
  This property can be used to execute a XPath query relative to the
  node selected with the :ref:`field <administration-columns-properties-field>`
  property or (since version 2.3.0) directly on the current node
  if :ref:`field <administration-columns-properties-field>`
  is not defined.

  The value will be taken from the first node returned by the query.
  If the :ref:`attribute <administration-columns-properties-attribute>` property is
  also defined, it will be applied to the node returned by the XPath query.

  Please see the :ref:`namespaces <administration-general-tca-properties-namespaces>`
  property for declaring namespaces to use in a XPath query.

Scope
  Handle data (XML)


.. _administration-columns-properties-fieldns:

fieldNS
~~~~~~~

Type
  string

Description
   Namespace for the given field. Use the full URI for the namespace, not
   a prefix.

   **Example**

   Given the following data to import:

   .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <Invoice xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">
            <InvoiceLine>
                <cbc:ID>A1</cbc:ID>
                <cbc:LineExtensionAmount currencyID="USD">100.00</cbc:LineExtensionAmount>
                <cac:OrderReference>
                    <cbc:ID>000001</cbc:ID>
                </cac:OrderReference>
            </InvoiceLine>
            ...
        </Invoice>

   getting the value in the :code:`<cbc:LineExtensionAmount>` tag would require
   the following configuration:

   .. code-block:: php

        'external' => [
            0 => [
                'fieldNS' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
                'field' => 'LineExtensionAmount'
            ]
        ]

Scope
  Handle data (XML)


.. _administration-columns-properties-attributens:

attributeNS
~~~~~~~~~~~

Type
  string

Description
   Namespace for the given attribute. Use the full URI for the namespace,
   not a prefix. See :ref:`fieldNS <administration-columns-properties-fieldns>`
   for example usage.

Scope
  Handle data (XML)


.. _administration-columns-properties-substructure-fields:

substructureFields
~~~~~~~~~~~~~~~~~~

Type
  array

Description
   Makes it possible to read several values that are located inside nested data structures.
   Consider the following data source:

   .. code:: json

        [
          {
            "order": "000001",
            "date": "2014-08-07",
            "customer": "Conan the Barbarian",
            "products": [
              {
                "product": "000001",
                "qty": 3
              },
              {
                "product": "000005",
                "qty": 1
              },
              {
                "product": "000101",
                "qty": 10
              },
              {
                "product": "000102",
                "qty": 2
              }
            ]
          },
          {
            "order": "000002",
            "date": "2014-08-08",
            "customer": "Sonja the Red",
            "products": [
              {
                "product": "000001",
                "qty": 1
              },
              {
                "product": "000005",
                "qty": 2
              },
              {
                "product": "000202",
                "qty": 1
              }
            ]
          }
        ]

   The "products" field is actually a nested structure, from which we want to fetch the values
   from both `product` and `qty`. This can be achieved with the following configuration:

   .. code:: php

        'products' => [
         'exclude' => 0,
         'label' => 'Products',
         'config' => [
            ...
         ],
         'external' => [
            0 => [
               'field' => 'products',
               'substructureFields' => [
                  'products' => [
                     'field' => 'product'
                  ],
                  'quantity' => [
                     'field' => 'qty'
                  ]
               ],
               ...
            ]
         ]
        ]

   The keys to the configuration array correspond to the names of the columns where the values will be
   stored. The configuration for each element can use all the existing properties for retrieving data:

   - :ref:`field <administration-columns-properties-field>`
   - :ref:`fieldNS <administration-columns-properties-fieldns>`
   - :ref:`arrayPath <administration-columns-properties-array-path>`
   - :ref:`arrayPathSeparator <administration-columns-properties-array-path-separator>`
   - :ref:`attribute <administration-columns-properties-attribute>`
   - :ref:`attributeNS <administration-columns-properties-attributens>`
   - :ref:`xpath <administration-columns-properties-xpath>`
   - :ref:`xmlValue <administration-columns-properties-xmlvalue>`

   The substructure fields are searched for inside the structure selected with the "main" data pointer.
   In the example above, the whole "products" structure is first fetched, then the `product` and `qty`
   are searched for inside that structure.

   The above example will read the values in the `product` nested field and put it into "products" column. Same for
   `qty` and "quantity". The fact that there are several entries will multiply imported records, actually
   denormalising the data on the fly. The result would be something like:

   +--------+------------+---------------------+----------+----------+
   | order  | date       | customer            | products | quantity |
   +========+============+=====================+==========+==========+
   | 000001 | 2014-08-07 | Conan the Barbarian | 000001   | 3        |
   +--------+------------+---------------------+----------+----------+
   | 000001 | 2014-08-07 | Conan the Barbarian | 000005   | 1        |
   +--------+------------+---------------------+----------+----------+
   | 000001 | 2014-08-07 | Conan the Barbarian | 000101   | 10       |
   +--------+------------+---------------------+----------+----------+
   | 000001 | 2014-08-07 | Conan the Barbarian | 000102   | 2        |
   +--------+------------+---------------------+----------+----------+
   | 000002 | 2014-08-08 | Sonja the Red       | 000001   | 1        |
   +--------+------------+---------------------+----------+----------+
   | 000002 | 2014-08-08 | Sonja the Red       | 000005   | 2        |
   +--------+------------+---------------------+----------+----------+
   | 000002 | 2014-08-08 | Sonja the Red       | 000202   | 1        |
   +--------+------------+---------------------+----------+----------+

   Obviously if you have a single element in the nested structure, no denormalisation happens.
   Due to this denormalisation you probably want to use this property in conjunction with the
   :ref:`multipleRows <administration-columns-properties-multiple-rows>` or
   :ref:`children <administration-columns-properties-children>` properties.

   .. note::

      In such scenarios you will generally want to have one of the nested fields "take the main role",
      i.e. have its value fill a column bearing the name of TYPO3 column which contains the substructure
      configuration. In the above example, the `product` field is matched to the "products" column name.
      In such a case, this nested field will go through any :ref:`transformations <administration-transformations>`
      defined for the column.

      If you need to apply :ref:`transformations <administration-transformations>` to other substructure fields,
      map them to :ref:`additional fields <administration-additionalfields>`. In order for this to work, you need
      to write some value into each additional field, otherwise it will result in a configuration error. So you
      need to set some dummy value, that is overridden by the values pointed to by the `substructureFields`
      configuration, but take care that if such a value is missing, the dummy value will remain and may produce
      unwanted results, depending on the rest of your configuration.

Scope
  Handle data


.. _administration-columns-properties-multiple-rows:

multipleRows
~~~~~~~~~~~~

Type
  boolean

Description
   Set to :code:`true` if you have denormalized data. This will tell the import
   process that there may be more than one row per record to import and that all
   values for the given column must be gathered and collapsed into a comma-separated
   list of values. See the :ref:`Mapping data <user-mapping-data>` chapter for
   explanations about the impact of this flag.

   If these values need to be sorted, use the :ref:`multipleSorting <administration-columns-properties-multiple-sorting>`
   property.

Scope
  Store data


.. _administration-columns-properties-multiple-sorting:

multipleSorting
~~~~~~~~~~~~~~~

Type
  string

Description
   If the :ref:`multipleRows <administration-columns-properties-multiple-rows>` need to be sorted,
   use this property to name the field which should be used for sorting. This can be any of the
   mapped fields, additional fields or substructure fields.

   .. note::

      The sorting is done using the PHP function :code:`strnatcasecmp()`, so make sure
      that your data plays well with it.

Scope
  Store data


.. _administration-columns-properties-children:

children
~~~~~~~~

Type
  array (see :ref:`Children records configuration <administration-children>`)

Description
   This property makes it possible to create nested structures and import them
   in one go. This may typically be "sys_file_reference" records for a field
   containing images. This should be used anytime you are using a MM table into
   which you need to write specific properties (like "sys_file_reference").
   For simple MM tables (like "sys_category_record_mm"), you don't need to create
   this children sub-structure for the MM table. It is enough to gather a comma-separated
   list of "sys_category" primary keys.

Scope
  Store data


.. _administration-columns-properties-transformations:

transformations
~~~~~~~~~~~~~~~

Type
  array (see :ref:`Transformations configuration <administration-transformations>`)

Description
  Array of transformation properties. The transformations will be executed as ordered
  by their array keys.

  **Example:**

  .. code-block:: php

        $GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = [
         0 => [
            'field' => 'start_date',
            'transformations' => [
               20 => [
                  'trim' => true
               ],
               10 => [
                  'userFunction' => [
                     'class' => \Cobweb\ExternalImport\Transformation\DateTimeTransformation::class,
                     'method' => 'parseDate'
                  ]
               ]
            ]
         ]
        ];

  The "userFunction" will be executed first (:code:`10`) and the "trim" next (:code:`20`).

Scope
  Transform data


.. _administration-columns-properties-xmlvalue:

xmlValue
~~~~~~~~

Type
  boolean

Description
  When taking the value of a node inside a XML structure, the default behaviour
  is to retrieve this value as a string. If the node contained a XML sub-structure,
  its tags will be stripped. When setting this value to :code:`true`, the XML
  structure of the child nodes is preserved.

Scope
  Handle data (XML)


.. _administration-columns-properties-disabledoperations:

disabledOperations
~~~~~~~~~~~~~~~~~~

Type
  array

Description
  Comma-separated list of database operations from which the column
  should be excluded. Possible values are "insert" and "update".

  See also the general property
  :ref:`disabledOperations <administration-general-tca-properties-disabledoperations>`.

Scope
  Store data

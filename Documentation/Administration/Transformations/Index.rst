﻿.. include:: /Includes.rst.txt


.. _administration-transformations:

Transformations configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A number of properties relate to transforming the data during the import process.
All of these properties are used during the "Transform data" step. They are
sub-properties of the :ref:`transformations property <administration-columns-properties-transformations>`.


.. _administration-transformations-properties:

Properties
""""""""""

.. container:: ts-properties

   ========================= ===================================================== =================
   Property                  Data type                                             Step/Scope
   ========================= ===================================================== =================
   isEmpty_                  array                                                 Transform data
   mapping_                  :ref:`Mapping configuration <administration-mapping>` Transform data
   rteEnabled_               boolean                                               Transform data
   trim_                     boolean                                               Transform data
   userFunction_             array                                                 Transform data
   value_                    simple type (string, integer, float, boolean)         Transform data
   ========================= ===================================================== =================


.. _administration-transformations-properties-mapping:

mapping
~~~~~~~

Type
  :ref:`Mapping configuration <administration-mapping>`

Description
  This property can be used to map values from the external data to
  values coming from some internal table. A typical example might be to
  match 2-letter country ISO codes to the uid of the "static\_countries"
  table.

Scope
  Transform data


.. _administration-transformations-properties-value:

value
~~~~~

Type
  Simple type (string, integer, float, boolean)

Description
  With this property, it is possible to set a fixed value for a given
  field. For example, this might be used to set a flag for all imported
  records. Or you might want to use different types for different import sources.

  .. note::

     Since External Import 7.1, the column property :ref:`value <administration-columns-properties-value>`
     should be used instead, as it makes more sense. Since there could be scenarios where this
     transformation property also makes sense, it is not deprecated, but its usage
     should be avoided.

  **Example**:

  .. code-block:: php
     :caption: EXT:my_extension/Configuration/Overrides/tx_sometable.php

     $GLOBALS['TCA']['tx_sometable'] = array_replace_recursive($GLOBALS['TCA']['tx_sometable'],
     [
       // ...
         'columns' => [
             'type' => [
                 'external' => [
                     0 => [
                         'transformations' => [
                             10 => [
                                 // Default type
                                 'value' => 0
                             ]
                         ],
                     ],
                     'another_import' => [
                         'transformations' => [
                             10 => [
                                 // Another type
                                 'value' => 1
                             ]
                         ],
                     ]
                 ]
             ],
          // ...
         ],
     ]);


Scope
  Transform data


.. _administration-transformations-properties-trim:

trim
~~~~

Type
  boolean

Description
  If set to :code:`true`, every value for this column will be trimmed during the
  transformation step.

  .. note::

     With newer versions of PHP, trying to trim a non-string causes an error.
     To account for that, since External Import 6.0.1, non-string data is left
     unchanged by this transformation. This may cause changes in your import, as
     previously the data used to be cast on the fly and trimmed.

     If you are affected by this change, you should create a custom transformation
     with a :ref:`userFunction <administration-transformations-properties-userfunction>`
     to cast your data explicitly before calling :code:`trim`.

Scope
  Transform data


.. _administration-transformations-properties-rteenabled:

rteEnabled
~~~~~~~~~~

Type
  boolean

Description
  If set to :code:`true` when importing HTML data into a RTE-enable field, the
  imported data will go through the usual RTE transformation process on
  the way to the database.

  .. note::

     Since the data goes through the RTE transformation process, you should mind
     the settings of the RTE for the given field if the results are unexpected. This
     is particularly true for tags which are not inside other tags and need to be
     explicitly allowed using the :code:`allowTagsOutside` option for example
     (see the :ref:`RTE configuration reference <t3tsref:pageTsRte>`).

Scope
  Transform data


.. _administration-transformations-properties-userfunc:
.. _administration-transformations-properties-userfunction:

userFunction
~~~~~~~~~~~~

Type
  array

Description
  This property can be used to define a function that will be called on
  each record to transform the data from the given field. See example
  below.

  **Example**

  Here is a sample setup referencing a user function:

  .. code-block:: php

        $GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = [
         0 => [
            'field' => 'start_date',
            'transformations' => [
               10 => [
                  'userFunction' => [
                     'class' => \Cobweb\ExternalImport\Transformation\DateTimeTransformation::class,
                     'method' => 'parseDate'
                  ]
               ]
            ]
         ]
        ];

  The definition of a user function takes three parameters:

  class
    *(string)* Required. Name of the class to be instantiated.

  method
    *(string)* Required. Name of the method that should be called.

  parameters (formerly "params")
    *(array)* Optional. Can contain any number of data, which will be passed
    to the method. This used to be called "params". Backwards-compatibility is
    ensured for now, but please update your configuration as soon as possible.

  In the example above we are using a sample class provided by
  External Import that can be used to parse a date and either return it
  as a timestamp or format it using either of the PHP functions
  :code:`date()` or :code:`strftime()` .

  .. note::

     Since External Import 5.1.0, if the user function throws an exception while
     handling a value, that value will be unset and thus removed from the imported
     dataset. The rationale is that such a value is considered invalid and should not
     be further processed nor saved to the database.

     The user function can also specifically throw the
     :php:`\Cobweb\ExternalImport\Exception\InvalidRecordException`. The effect is to
     remove the entire record from the imported dataset.

  For more details about creating a user function, please refer to the
  :ref:`Developer's Guide <developer-user-functions>`.

Scope
  Transform data


.. _administration-transformations-properties-isempty:

isEmpty
~~~~~~~

Type
  array

Description
  This property is used to assess if a value in the given column can be considered
  empty or not and, if yes, act on it. The action can be either to set a default
  value or to remove the entire record from the imported dataset.

  Deciding whether a given value is "empty" is a bit tricky, since :code:`null`,
  :code:`false`, :code:`0` or an empty string - to name a few - could all be considered
  empty depending on the circumstances. By default, this property will rely on the PHP
  function :code:`empty()`. However it is also possible to evaluate an expression based
  on the values in the record using the Symfony Expression Language.

  expression
    *(string)* A condition using the Symfony Expression Language syntax. If it evaluates
    to :code:`true`, the action (see below) will be triggered. The values in the record
    can be used, by simply referencing them with the column name.

    If no expression is defined, the PHP function :code:`empty()` is used.

    See the `Symfony documentation for reference <https://symfony.com/doc/current/components/expression_language/syntax.html>`_.

  invalidate
    *(bool)* Set this property to :code:`true` to discard the entire record from the
    imported dataset if the **expression** (or :code:`empty()`) evaluated to :code:`true`.
    **invalidate** takes precedence over **default**.

  default
    *(mixed)* If the **expression** (or :code:`empty()`) evaluates to :code:`true`, this
    value will be set in the record instead of the empty value.

  **Example**

  .. code-block:: php

        'store_code' => [
            'exclude' => 0,
            'label' => 'Code',
            'config' => [
                'type' => 'input',
                'size' => 10
            ],
            'external' => [
                0 => [
                    'field' => 'code',
                    'transformations' => [
                        10 => [
                            'trim' => true
                        ],
                        20 => [
                            'isEmpty' => [
                                'expression' => 'store_code === ""',
                                'invalidate' => true
                            ]
                        ],
                    ]
                ]
            ]
        ],

  In this example, the :code:`store_code` field is compared with an empty string. Any record with
  an empty string in that column will be removed from the dataset.

  .. note::

     Since you can write any expression as long as it evaluates to a boolean value, this property
     actually makes it possible to test another condition than just emptiness, although it may be
     confusing to use it in this way.

  .. warning::

     There's a weird behavior in the Symfony Expression Language: if the value being evaluated
     is missing from the record, the parser throws an error as if the syntax were invalid. The
     workaround implemented in External Import is that an evaluation throwing an exception is
     equivalent to the evaluation returning :code:`true`. This makes it possible to handle
     missing values, but has the drawback that a real syntax error will not be detected and
     all values will be considered empty.

     Such events are logged (at notice-level).

     This does not happen anymore with :code:`symfony/expression-language` 7.2 or above. Also,
     with :code:`symfony/expression-language` 6.x or above, it is possible to use the
     `coalesce operator <https://symfony.com/doc/current/reference/formats/expression_language.html#null-coalescing-operator>`_,
     which will prevent the above-mentioned error.

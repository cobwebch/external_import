.. include:: /Includes.rst.txt


.. _import-configuration-examples-substructure-children:

Substructures and child records
===============================

The :ref:`substructureFields <administration-columns-properties-substructure-fields>`
property makes it possible to read nested structures in the imported data and
the :ref:`children <administration-children>` property allows for the creation
of child (IRRE) records. Each one is a bit hairy to use properly and the combination
of both may melt your brain. Hopefully this example can help avoid such a crash.

This example is based on the import of orders from the
`External Import test extension <https://github.com/fsuter/externalimport_test>`_.


.. _import-configuration-examples-substructure-children-orders:

The orders
~~~~~~~~~~

The orders table contains only a few fields. The most interesting one is :code:`products`,
which is of type "inline" and describes the items in the order, each with a reference to
a product and a quantity.

Here is the TCA for the column:

.. code:: php

    'products' => [
        'exclude' => 0,
        'label' => 'Products',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_externalimporttest_order_items',
            'foreign_selector' => 'uid_foreign',
            'foreign_label' => 'uid_foreign',
            'foreign_field' => 'uid_local',
            'foreign_sortby' => 'sorting_foreign',
            'minitems' => 1,
            'maxitems' => 9999,
            'appearance' => [
                'useSortable' => true,
            ],
        ],
    ],

and the relevant columns in the :code:`tx_externalimporttest_order_items` table:

.. code:: php

    'uid_foreign' => [
        'exclude' => 0,
        'label' => 'Product',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tx_externalimporttest_product',
            'foreign_table_where' => 'ORDER BY tx_externalimporttest_product.name',
            'maxitems' => 1,
        ],
    ],
    'quantity' => [
        'exclude' => 0,
        'label' => 'Quantity',
        'config' => [
            'type' => 'number',
        ],
    ],


.. _import-configuration-examples-substructure-children-data:

The data
~~~~~~~~

The data to import presents itself as a JSON structure. An order contains several items,
each item refering to a product by its code and a quantity. For the sake of the
example, the quantity is a string rather than a number. Below is an extract, the
full file can be found in :code:`EXT:externalimport_test/Resources/Private/ImportData/Test/Orders.json`.

.. code:: json

    [
      {
        "order": "000001",
        "date": "2014-08-07",
        "customer": "Conan the Barbarian",
        "items": [
          {
            "product_code": "000001",
            "qty": "03"
          },
          {
            "product_code": "000005",
            "qty": "01"
          },
          {
            "product_code": "000101",
            "qty": "10"
          },
          {
            "product_code": "000102",
            "qty": "02"
          }
        ]
      },
      {
        "order": "000002",
        "date": "2014-08-08",
        "customer": "Sonja the Red",
        "items": [
          {
            "product_code": "000001",
            "qty": "01"
          },
          {
            "product_code": "000005",
            "qty": "02"
          },
          {
            "product_code": "000202",
            "qty": "01"
          }
        ]
      }
    ]


.. _import-configuration-examples-substructure-children-reading:

Reading the structure
~~~~~~~~~~~~~~~~~~~~~

The orders being a nested structure, they can't be read as a single value. Instead,
we need to use the :ref:`substructureFields <administration-columns-properties-substructure-fields>`
property, which makes it possible to loop on such a nested structure and extract
one or more values from each. Let's look just at the "reading" part of the configuration

.. code:: php

        'products' => [
            ...
            'external' => [
                0 => [
                    'field' => 'items',
                    'substructureFields' => [
                        'products' => [
                            'field' => 'product_code',
                        ],
                        'quantity' => [
                            'field' => 'qty',
                        ],
                    ],
                    ...
                ],
            ],
        ],

What this code does, is that the :code:`items` field is read for each order. The
value being an array, the :code:`substructureFields` property loops on each entry
and extracts the :code:`product_code` (putting it into the :code:`products` field)
and :code:`qty` values (putting it into the :code:`quantity` field).

In effect, this creates one row per item in the order within the array containing the data to import.
In database terms, this corresponds to a denormalization. Considering the above JSON structure, the
result can be represented like this:

+--------+------------+---------------------+----------+----------+
| order  | date       | customer            | products | quantity |
+========+============+=====================+==========+==========+
| 000001 | 2014-08-07 | Conan the Barbarian | 000001   | 03       |
+--------+------------+---------------------+----------+----------+
| 000001 | 2014-08-07 | Conan the Barbarian | 000005   | 01       |
+--------+------------+---------------------+----------+----------+
| 000001 | 2014-08-07 | Conan the Barbarian | 000101   | 10       |
+--------+------------+---------------------+----------+----------+
| 000001 | 2014-08-07 | Conan the Barbarian | 000102   | 02       |
+--------+------------+---------------------+----------+----------+
| 000002 | 2014-08-08 | Sonja the Red       | 000001   | 01       |
+--------+------------+---------------------+----------+----------+
| 000002 | 2014-08-08 | Sonja the Red       | 000005   | 02       |
+--------+------------+---------------------+----------+----------+
| 000002 | 2014-08-08 | Sonja the Red       | 000202   | 01       |
+--------+------------+---------------------+----------+----------+

.. warning::

   When using :code:`substructureFields` in conjunction with a :code:`children` configuration,
   it is very important that the IRRE column (:code:`products` in this case) contains
   the value that the child record relates to (the product code in this case). This is what
   enables the TYPO3 Core Engine to create the relation properly between parent and children.


.. _import-configuration-examples-substructure-children-transformation:

Applying transformations
~~~~~~~~~~~~~~~~~~~~~~~~

For the :code:`products` field, we want to map it to the products table. This is a
straightforward :ref:`mapping transformation <administration-mapping>`:

.. code:: php

        'products' => [
            ...
            'external' => [
                0 => [
                    'field' => 'items',
                    'substructureFields' => [
                        'products' => [
                            'field' => 'product_code',
                        ],
                        'quantity' => [
                            'field' => 'qty',
                        ],
                    ],
                    'transformations' => [
                        10 => [
                            'mapping' => [
                                'table' => 'tx_externalimporttest_product',
                                'referenceField' => 'sku',
                            ],
                        ],
                    ],
                    ...
                ],
            ],
        ],

But what about the :code:`quality` field which was automatically created when reading
the substructure? It is also possible to put it through transformations, but transformations
cannot be directly attached to the substructure definition. Instead, we have to use
:ref:`additional fields <administration-additionalfields>`. We declate :code:`quality`
as an additional field and read some dummy value into it. That value will be overridden
during the substructure processing. During the transformation step, the value coming
from the substructure will go through whatever transformation was declared. Here's the
corresponding code:

.. code:: php

    'external' => [
        'general' => [
            ...
        ],
        'additionalFields' => [
            0 => [
                'quantity' => [
                    // Fill with any dummy (but existing) value
                    'field' => 'order',
                    'transformations' => [
                        10 => [
                            'userFunction' => [
                                'class' => Transformation::class,
                                'method' => 'castToInteger',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

The transformation is simply - as its name makes clear - a casting of string to integer.


.. _import-configuration-examples-substructure-children-children:

Creating child records
~~~~~~~~~~~~~~~~~~~~~~

So what do we do now with our denormalized structure?

In this scenario, we want to create the IRRE (inline) structure of order items within
each order. For this, we need the :ref:`children <administration-children>` property.
The :code:`children` configuration defines the table for which to create entries and
the values to attribute to each column of that table. The values come from the fields
available in the data to import, hence :code:`products` and `quantity` in our case.

Because the :code:`children` property expects the creation of multiple children, it
knows how to handle the denormalization caused by reading the substructure. It will
look to the :ref:`referenceUid <administration-general-tca-properties-reference-uid>`
column to tell which entries are repeating and are thus associated with the same parent.

Putting it all together, the configuration for the :code:`products` column looks like this
(lines related to the :code:`children` property are highlighted):

.. code:: php
   :emphasize-lines: 36-51

        'products' => [
            'exclude' => 0,
            'label' => 'Products',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_externalimporttest_order_items',
                'foreign_selector' => 'uid_foreign',
                'foreign_label' => 'uid_foreign',
                'foreign_field' => 'uid_local',
                'foreign_sortby' => 'sorting_foreign',
                'minitems' => 1,
                'maxitems' => 9999,
                'appearance' => [
                    'useSortable' => true,
                ],
            ],
            'external' => [
                0 => [
                    'field' => 'items',
                    'substructureFields' => [
                        'products' => [
                            'field' => 'product_code',
                        ],
                        'quantity' => [
                            'field' => 'qty',
                        ],
                    ],
                    'transformations' => [
                        10 => [
                            'mapping' => [
                                'table' => 'tx_externalimporttest_product',
                                'referenceField' => 'sku',
                            ],
                        ],
                    ],
                    'children' => [
                        'table' => 'tx_externalimporttest_order_items',
                        'columns' => [
                            'uid_local' => [
                                'field' => '__parent.id__',
                            ],
                            'uid_foreign' => [
                                'field' => 'products',
                            ],
                            'quantity' => [
                                'field' => 'quantity',
                            ],
                        ],
                        'controlColumnsForUpdate' => 'uid_local, uid_foreign',
                        'controlColumnsForDelete' => 'uid_local',
                    ],
                ],
            ],
        ],


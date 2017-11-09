.. include:: ../../Includes.txt


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
	mapping_                  :ref:`Mapping configuration <administration-mapping>` Transform data
	rteEnabled_               boolean                                               Transform data
	trim_                     boolean                                               Transform data
	userFunc_                 array                                                 Transform data
	value_                    simple type (string, integer, boolean)                Transform data
	========================= ===================================================== =================


.. _administration-columns-properties-mapping:
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


.. _administration-columns-properties-value:
.. _administration-transformations-properties-value:

value
~~~~~

Type
  Simple type (string, integer, boolean)

Description
  With this property, it is possible to set a fixed value for a given
  field. For example, this might be used to set a flag for all imported
  records.

Scope
  Transform data


.. _administration-columns-properties-trim:
.. _administration-transformations-properties-trim:

trim
~~~~

Type
  boolean

Description
  If set to :code:`true`, every value for this column will be trimmed during the
  transformation step.

Scope
  Transform data


.. _administration-columns-properties-rteenabled:
.. _administration-transformations-properties-rteenabled:

rteEnabled
~~~~~~~~~~

Type
  boolean

Description
  If set to :code:`true` when importing HTML data into a RTE-enable field, the
  imported data will go through the usual RTE transformation process on
  the way to the database.

Scope
  Transform data


.. _administration-columns-properties-userfunc:
.. _administration-transformations-properties-userfunc:

userFunc
~~~~~~~~

Type
  array

Description
  This property can be used to define a function that will be called on
  each record to transform the data from the given field. See example
  below.

  **Example**

  Here is a sample setup referencing a user function:

  .. code-block:: php

		$GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = array(
				0 => array(
						'field' => 'start_date',
						'transformations' => array(
								10 => (
										'userFunc' => array(
												'class' => \Cobweb\ExternalImport\Task\DateTimeTransformation::class,
												'method' => 'parseDate'
										)
								)
						)
				)
		);

  A user function requires three parameters:

  class
    *(string)* Name of the class to be instantiated.

  method
    *(string)* Defines which method of the class should be called.

  params
    *(array)* Optional. Can contain any number of data, which will be passed
    to the method.

  In the example above we are using a sample class provided by
  External Import that can be used to parse a date and either return it
  as a timestamp or format it using either of the PHP functions
  :code:`date()` or :code:`strftime()` .

  For more details about creating a user function, please refer to the
  :ref:`Developer's Guide <developer>`.

Scope
  Transform data

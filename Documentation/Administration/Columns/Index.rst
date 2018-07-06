.. include:: ../../Includes.txt


.. _administration-columns:

Columns configuration
^^^^^^^^^^^^^^^^^^^^^

You also need an "external" syntax for each column to define
which external data goes into that column and any handling that might
apply. This is also an indexed array. Obviously indices used for each
column must relate to the indices used in the "ctrl" section. In its
simplest form this is just a reference to the external data's name:

.. code-block:: php

	'code' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:externalimport_tut/locallang_db.xml:tx_externalimporttut_departments.code',
		'config' => array(
			'type' => 'input',
			'size' => '10',
			'max' => '4',
			'eval' => 'required,trim',
		),
		'external' => array(
			0 => array(
				'field' => 'code'
			)
		)
	),

The properties for the columns configuration are described below.


.. _administration-columns-properties:

Properties
""""""""""

.. container:: ts-properties

	========================= ====================================================================== ===================
	Property                  Data type                                                              Step/Scope
	========================= ====================================================================== ===================
	arrayPath_                string                                                                 Handle data (array)
	arrayPathSeparator_       string                                                                 Handle data (array)
	attribute_                string                                                                 Handle data (XML)
	attributeNS_              string                                                                 Handle data (XML)
	disabledOperations_       string                                                                 Store data
	field_                    string                                                                 Handle data
	fieldNS_                  string                                                                 Handle data (XML)
	MM_                       :ref:`MM configuration <administration-mm>`                            Store data
	transformations_          :ref:`Transformations configuration <administration-transformations>`  Transform data
	xmlValue_                 boolean                                                                Handle data (XML)
	xpath_                    string                                                                 Handle data (XML)
	========================= ====================================================================== ===================


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
  comprised of the keys for pointing into the array, separated by some character (:code:`/`
  by default; can be changed using the :ref:`arrayPathSeparator <administration-columns-properties-array-path-separator>`
  property). Consider the following structure to import:

  .. code:: php

		[
				'name' => 'Zaphod Beeblebrox',
				'book' => [
						'title' => 'Hitchiker\'s Guide to the Galaxy'
				]
		]

  To import the title of the book, use a configuration like:

  .. code:: php

		[
				'arrayPath' => 'book/title'
		]

  Works only for array-type data.

  .. note::

     Using :code:`'arrayPath' => 'book'` is equivalent to using :code:`'field' => 'book'`,
     but the "field" property should be preferred in such a case.

     If both "field" and "arrayPath" are defined, the latter takes precedence.

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

		'external' => array(
			0 => array(
				'fieldNS' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
				'field' => 'LineExtensionAmount'
			)
		)

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


.. _administration-columns-properties-mm:

MM
~~

Type
  :ref:`MM configuration <administration-mm>`

Description
   Definition of MM-relations, see :ref:`specific reference <administration-mm>`
   for more details.

Scope
  Transform data


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

		$GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = array(
				0 => array(
						'field' => 'start_date',
						'transformations => array(
								20 => array(
										'trim' => true
								),
								10 => array(
										'userFunc' => array(
												'class' => \Cobweb\ExternalImport\Task\DateTimeTransformation::class,
												'method' => 'parseDate'
										)
								)
						)
				)
		);

  The "userFunc" will be executed first (:code:`10`) and the "trim" next (:code:`20`).

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

.. include:: ../../Includes.txt


.. _administration-mm:

MM-relations configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^

Things get a bit more complicated with MM-relations, where additional
configuration is required. The related properties are described below.

.. warning::

   This property is deprecated. Use :ref:`multipleRows <administration-columns-properties-multiple-rows`
   or :ref:`children <administration-columns-properties-children>` according to your needs.

   Depending on how you used this property, there's no fallback and you will need to change your code.
   If your need was to enrich a MM table with additional information, change your TCA to use an IRRE
   structure instead.


.. _administration-mm-properties:

Properties
""""""""""

.. container:: ts-properties

   ========================= =====================================================
   Property                  Data type
   ========================= =====================================================
   additionalFields_         array
   mapping_                  :ref:`Mapping configuration <administration-mapping>`
   multiple_                 boolean
   sorting_                  string
   ========================= =====================================================


.. _administration-mm-properties-mapping:

mapping
~~~~~~~

Type
  :ref:`Mapping configuration <administration-mapping>`

Description
  This is similar to the :ref:`mapping property <administration-columns-properties-mapping>`
  for columns. It is used to define which table to link to and
  which column in that table contains the external primary key.

Scope
  Store data


.. _administration-mm-properties-additional-fields:

additionalFields
~~~~~~~~~~~~~~~~

Type
  array

Description
  List of fields that must be stored along the local and foreign keys in
  the MM table. For each such field, define which TYPO3 CMS MM-table field
  corresponds to which external data field.

Scope
  Store data


.. _administration-mm-properties-multiple:

multiple
~~~~~~~~

Type
  boolean

Description
  If some MM-relations exist several times in your external data
  (because they have various additional fields), you must set this
  property to 1, so that they are preserved (otherwise the TYPO3 Core Engine will take
  only unique "uid\_local"-"uid\_foreign" pairs into account).

Scope
  Store data


.. _administration-mm-properties-sorting:

sorting
~~~~~~~

Type
  string

Description
  Indicates that the data is to be sorted according to that particular
  field from the external data.

  Note that since the external import relies on the TYPO3 Core Engine to store the
  data, which sets its own numbering for sorting, thus the value in
  sorting is never used as is, but just for ordering the records. So if
  the records in the external source are already sorted, there's no need
  to define the "sorting" property.

Scope
  Store data


.. _administration-mm-example:

Example
"""""""

This example shows how the "employee" field of the external data
is mapped to the "fe_users" table to consitute the list of members
in a team.

.. code-block:: php

	'members' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:externalimport_tut/locallang_db.xml:tx_externalimporttut_teams.members',
		'config' => array(
			'type' => 'group',
			'size' => 5,
			'internal_type' => 'db',
			'allowed' => 'fe_users',
			'MM' => 'tx_externalimporttut_teams_feusers_mm',
			'maxitems' => 100
		),
		'external' => array(
			0 => array(
				'field' => 'employee',
				'MM' => array(
					'mapping' => array(
						'table' => 'fe_users',
						'referenceField' => 'tx_externalimporttut_code',
						'default' => ''
					),
					'sorting' => 'rank'
				)
			)
		)
	),

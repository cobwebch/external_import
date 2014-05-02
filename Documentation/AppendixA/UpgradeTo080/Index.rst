.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _appendixa-to08:

Upgrade to 0.8.0
^^^^^^^^^^^^^^^^

With version 0.8.0 it became possible to define multiple external
sources for a given table. This implied changing the extended TCA
syntax. When upgrading to version 0.8.0 you must also change all your
"external" TCA properties. All such properties have become indexed
arrays. So if you had the following:

.. code-block:: php

	$TCA['tx_myext_mytable'] = array (
		'ctrl' => array (
			'title' => ...,
			...
			'external' => array(
				'connector' => ...,
				'parameters' => array(
					...
				),
				'data' => 'xml',
				'nodetype' => 'record',
				'reference_uid' => ...,
				'priority' => 10,
				'deleteNonSynchedRecords' => 1
			)
		),
	);

You must change it to:

.. code-block:: php

	$TCA['tx_myext_mytable'] = array (
		'ctrl' => array (
			'title' => ...,
			...
			'external' => array(
				0 => array (
					'connector' => ...,
					'parameters' => array(
						...
					),
					'data' => 'xml',
					'nodetype' => 'record',
					'reference_uid' => ...,
					'priority' => 10,
					'deleteNonSynchedRecords' => 1
				)
			)
		),
	);

The same goes for the columns definitions which should be changed
from:

.. code-block:: php

	'field_name' => array (
		'exclude' => 0,
		'label' => '...',
		'config' => array (
			...
		),
		'external' => array (
			'field' => '...',
		)
	),

to:

.. code-block:: php

	'field_name' => array (
		'exclude' => 0,
		'label' => '...',
		'config' => array (
			...
		),
		'external' => array (
			0 => array (
				'field' => '...',
			)
		)
	),

Furthermore the MM-mappings syntax has been simplified. So the
following configuration:

.. code-block:: php

	'external' => array(
		0 => array(
			'MM' => array(
				'mappings' => array(
					'uid_foreign' => array(
						'table' => name of foreign table,
						'reference_field' => foreign MM key,
						'value_field' => 'uid'
					)
				),
				'additional_fields' => array(
					TYPO3 field name => external data field name
				),
				'sorting' => 'field',
			)
		)
	)

can be rewritten to:

.. code-block:: php

	'external' => array(
		0 => array(
			'MM' => array(
				'mapping' => array(
					'table' => name of foreign table,
					'reference_field' => foreign MM key,
					'value_field' => 'uid'
				),
				'additional_fields' => array(
					TYPO3 field name => external data field name
				),
				'sorting' => 'field',
			)
		)
	)

although the old syntax is still supported.

Also note that the "deleteNonSynchedRecords" property was deprecated
in favour of the more flexible "disabledOperations" property.
It is still supported though.


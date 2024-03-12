.. include:: /Includes.rst.txt


.. _administration-additionalfields:

Additional fields configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Additional fields are fields that are read from the external source but not saved
to the database. They do not match TCA columns. They are most likely used in
user functions and custom steps to prepare some other data, but are not
persisted in the TYPO3 database.

Since External Import 5.0, additional fields are defined in their own
"configuration space":


.. code-block:: php

   $GLOBALS['TCA']['tx_externalimporttest_tag'] = [
      'external' => [
         'additionalFields' => [
            0 => [
               'quantity' => [
                  'field' => 'qty'
               ]
            ]
         ]
      ],
   ];

As usual the index (here :code:`0`) must match between the general configuration,
the columns configuration and the additional fields configuration.

In the above example the "qty" field from the external data will be read and stored in the "quantity"
column, which will be available for any processing, but not saved to the database.

All properties from the :ref:`columns configuration <administration-columns>`
can be used with additional fields too (although some may not make sense).

.. tip::

   Technically speaking the additional fields and columns configuration are merged.
   Additional fields are marked with a special flag that tells External Import not
   to save them. Any column can be marked (or unmarked) as such programmatically
   by invoking :code:`\Cobweb\ExternalImport\Domain\Model\Configuration::setExcludedFromSavingFlagForColumn()`.

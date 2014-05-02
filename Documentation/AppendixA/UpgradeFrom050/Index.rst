.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _appendixa-from05:

Upgrade from 0.5.0
^^^^^^^^^^^^^^^^^^

If you were using version 0.5.0, you may have some surprises as the
extended TCA syntax has been modified for MM-relations:

- in MM mappings, the "uid\_local" mapping no longer needs to be
  defined. Indeed the local uid is considered to be always "uid", since
  the whole point of this extension is to store the data into database
  tables that respect the TYPO3 standards.

- The "reference\_field" for the "uid\_foreign" mapping now uses the
  name of the field in the local database table. This is matched to the
  field name in the external data by reading to what external field that
  column is matched.

- The "update" property has been removed, since TCEmain deletes existing
  MM-relations anyway.

- The "sorting\_data" field has been removed. The "sorting" property now
  stores what was in "sorting\_data" and there are no other options for
  sorting.

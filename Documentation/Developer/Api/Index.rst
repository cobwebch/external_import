.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _developer-api:

External Import API
^^^^^^^^^^^^^^^^^^^

It is very simple to use the external import features. You just need
to assemble data in a format it can understand (XML structure or
recordset) and call the appropriate method. All you need is an
instance of class :code:`\Cobweb\ExternalImport\Importer` and a single call.

.. code-block:: php

	$importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Cobweb\ExternalImport\Importer::class);
	$importer->importData($table, $index, $rawData);


The call parameters are as follows:

+----------+---------+------------------------------------------------+
| Name     | Type    | Description                                    |
+==========+=========+================================================+
| $table   | string  | Name of the table to store the data into.      |
+----------+---------+------------------------------------------------+
| $index   | integer | Index of the relevant external configuration.  |
+----------+---------+------------------------------------------------+
| $rawData | mixed   | The data to store, either as XML or PHP array. |
+----------+---------+------------------------------------------------+

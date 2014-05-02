.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _user-mapping-data:

Mapping data
^^^^^^^^^^^^

In the Administration chapter below, you will find explanations about
how to map the data from the external source to existing or newly
created tables in the TYPO3 database. There are two mandatory
conditions for this operation to succeed:

- the external data **must** have the equivalent of a primary key

- this primary key **must** be stored into some column of the TYPO3 CMS
  database, but **not** the uid column which is internal to TYPO3 CMS.

The primary key in the external data is the key that will used to
decide whether a given entry in the external data corresponds to a
record already stored in the TYPO3 CMS database or if a new record should
be created for that entry. Records in the TYPO3 CMS database that do not
match primary keys in the external data can be deleted if desired.


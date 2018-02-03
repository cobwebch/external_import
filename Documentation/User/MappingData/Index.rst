.. include:: ../../Includes.txt


.. _user-mapping-data:

Mapping data
^^^^^^^^^^^^

In the :ref:`Administration chapter <administration>`, you will find explanations about
how to map the data from the external source to existing or newly
created tables in the TYPO3 CMS database. There are two mandatory
conditions for this operation to succeed:

- the external data **must** have the equivalent of a primary key

- this primary key **must** be stored into some column of the TYPO3 CMS
  database, but **not** the uid column which is internal to TYPO3 CMS.

The primary key in the external data is the key that will used to
decide whether a given entry in the external data corresponds to a
record already stored in the TYPO3 CMS database or if a new record should
be created for that entry. Records in the TYPO3 CMS database that do not
match primary keys in the external data can be deleted if desired.


.. _user-mapping-data-mm:

Mapping MM relations
""""""""""""""""""""

Mapping relations is more tricky that simple values and MM relations
even more so. The explanation of the properties in the
:ref:`Administration chapter <administration>` will help, as will the
:ref:`External Import Tutorial <tut:start>`.

However there are some fundamental concepts to understand
to be able to use those features in an optimal way.

External Import saves the imported data using the
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler` class
This implies that the data is
arranged in a particular structure that is later transformed
into SQL statement by the TYPO3 CMS Core, but not by
External Import itself. This is important to understand, because
it has an impact on how MM relations are handled.

Whenever a record from a given table has relations to one or
more records from another table, that list of records is
described in the DataHandler structure as a simple comma-separated
list of identifiers. This means that External Import does not
need to know about all the details of the MM relation. It
can just send a comma-separated list of identifiers and DataHandler
will sort it out using the TCA configuration.

This means that it is not absolutely necessary to use the
:ref:`MM configuration properties <administration-mm>`, just
because the TCA configuration refers to a MM table.

What the :ref:`MM configuration properties <administration-mm>`
imply first and foremost is that the external data should be
considered as being denormalized. Indeed in a "non-MM" import
process, several external records with the same external key
will overwrite each other, the last one being the one that is
actually imported. However when a MM configuration is active,
the foreign key in each such record will be preserved and used
to define the many relations of that record.

Consider the following external data:

**Table A**

+----------------------+-----------+
| External primary key | Title     |
+======================+===========+
| x3                   | X-Three   |
+----------------------+-----------+
| y4                   | Y-Four    |
+----------------------+-----------+

**Table B**

+----------------------+-----------+----------------------+
| External primary key | Name      | External foreign key |
+======================+===========+======================+
| 01                   | Foo       | x3                   |
+----------------------+-----------+----------------------+
| 01                   | Foo       | y4                   |
+----------------------+-----------+----------------------+
| 02                   | Bar       | x3                   |
+----------------------+-----------+----------------------+

Table A is imported first and contains two records.
Table B is imported next. Two of the external records have
the same external primary key. Thus - after import - the
table will contain only two records. However, because we declared
a MM relation between Table A and Table B, the relations
to both :code:`x3` and :code:`y4` will be preserved for
record :code:`01`. Otherwise, the second entry would have
overwritten the first one and record :code:`01` would have had
a single relation (to :code:`y4`).

I hope this is not too confusing, I'm finding it hard to explain...

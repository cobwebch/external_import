.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _administration:

Administration
--------------

To start inserting data from an external source into your TYPO3 CMS
tables, you must first extend their TCA with a specific syntax, with
general information in the "ctrl" section and specific information for
each column. Obviously you can also create new tables and put your
data in there.

This chapter describes all possible configuration options. As in the
main TCA reference, a scope is associated with each property to help
understand which part of the process it impacts. The names of the
scopes correspond to the :ref:`process steps <user-overview>`.

There are some code examples throughout this chapter. They are all
taken from the :ref:`External Import Tutorial <tut:start>`. You are
encouraged to refer to it for more examples and more details about
each example.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   UserRights/Index
   GeneralTca/Index
   Columns/Index
   Mapping/Index
   MmRelations/Index

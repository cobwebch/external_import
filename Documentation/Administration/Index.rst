.. include:: ../Includes.txt


.. _administration:

Administration
--------------

To start inserting data from an external source into your TYPO3 CMS
tables, you must first extend their TCA with a specific syntax, with
general information in the "ctrl" section and specific information for
each column. Obviously you can also create new tables and put your
data in there.

This chapter describes all possible configuration options. For each
property, a step or a more general scope is mentioned to help
understand which part of the process it impacts. The names of the
steps correspond to the :ref:`process steps <user-overview>`.

There are some code examples throughout this chapter. They are
taken either from the :ref:`External Import Tutorial <tut:start>`
or from the test extension: https://github.com/fsuter/externalimport_test.
You are encouraged to refer to them for more examples and more details about
each example (in the Tutorial).


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   UserRights/Index
   GeneralTca/Index
   Columns/Index
   Transformations/Index
   Mapping/Index
   MmRelations/Index

.. include:: /Includes.rst.txt


.. _import-configuration:
.. _administration:

Import configuration
--------------------

To start inserting data from an external source into your TYPO3 CMS
tables, you must first extend their TCA with a specific syntax. This
syntax is comprised of 3 parts:

- general information ("General TCA configuration")
- specific information for each column where data will be stored ("Columns configuration")
- so-called "additional fields" which are read from the external source, but not saved

The first two parts are **required**, the third is *optional*.

This chapter describes all possible configuration options. For each
property, a step or a more general scope is mentioned to help
understand which part of the process it impacts. The names of the
steps correspond to the :ref:`process steps <user-overview>`.

There are some code examples throughout this chapter. They are
taken either from the :ref:`External Import Tutorial <cobweb/externalimport_tut:start>`
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
   AdditionalFields/Index
   Transformations/Index
   Mapping/Index
   Children/Index
   ArrayPath/Index
   LogCleanup/Index

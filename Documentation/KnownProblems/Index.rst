.. include:: ../Includes.txt


.. _problems:

Known problems
--------------

Nested imports (with the :ref:`children <administration-columns-properties-children>`
property) are a new feature from 5.0 and certainly not all scenarios have been
explored. This could be a source of bugs. In particular, nothing is provided
for sorting records if the default ordering (i.e. the order in which the data
was read from the external source) is not wanted. Maybe mapping the "sorting" field
is enough. I will gladly have feedback from your experience.

In general please report bugs and improvements at:
https://github.com/cobwebch/external_import/issues.

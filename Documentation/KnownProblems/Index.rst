.. include:: /Includes.rst.txt


.. _problems:

Known problems
--------------

In order to support Reactions in TYPO3 12, a new database field was added to the
:code:`sys_reaction` table. Since this table does not exist in TYPO3 11, the Maintenance Tool
will create an incomplete :code:`sys_reaction` table with just that single field.
Since there's no associated TCA in TYPO3 11, this doesn't cause any problem. It's just
not very elegant.

In general please report bugs and improvements at:
https://github.com/cobwebch/external_import/issues.

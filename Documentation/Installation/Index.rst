.. include:: ../Includes.txt


.. _installation:

Installation
------------

Installing this extension does nothing in and of itself. You still
need to extend the TCA definition of some tables with the appropriate
syntax and create specific connectors for the application you want to
connect to.

TYPO3 CMS 9 or 10 is required, as well as the "scheduler" system extension.


.. _installation-compatibility:

Compatibility issues
^^^^^^^^^^^^^^^^^^^^


.. _installation-upgrade-500:

Upgrade to 5.0.0
""""""""""""""""

The general configuration must now be placed in :code:`$GLOBALS['TCA'][table-name]['external']['general']`
instead of :code:`$GLOBALS['TCA'][table-name]['ctrl']['external']`. Backwards-compatibility is ensured for now
but code should be migrated as soon as possible, as support will be dropped in the future.

The "additionalFields" property from the general configuration (and not from the "MM" property)
has been moved to its own configuration space. Rather than
:code:`$GLOBALS['TCA'][table-name]['ctrl']['external'][some-index]['additionalFields]`
it is now :code:`$GLOBALS['TCA'][table-name]['external']['additionalFields'][some-index]`.
Furthermore, it is no longer a simple comma-separated list of fields, but an array structure
with all the same options as standard column mappings. Backwards-compatibility is ensured for now.
For more details, :ref:`see the relevant chapter <administration-additionalfields>`.

The "MM" property is deprecated. It should not be used anymore. Instead the new
:ref:`multipleRows <administration-columns-properties-multiple-rows>` or
:ref:`children <administration-columns-properties-children>` properties
should be used according to your import scenario.

The "userFunc" property of the transformations configuration has been renamed to
:ref:`userFunction <administration-transformations-properties-userfunction>` and
its sub-property "params" has been renamed "parameters". Backwards-compatibility is ensured for now.

If both "insert" and "update" operations are disabled in the general configuration
(using the :ref:`disabledOperations property <administration-general-tca-properties-disabledoperations>`),
External Import will now delete records that were not marked for update (even if the
actual update does not take place). Previously, no records would have been deleted,
because the entire matching of existing records was skipped.

Check out the revamped :ref:`Mapping data <user-mapping-data>` chapter which should
hopefully help you get a better picture of what is possible with External Import
and how different properties can be combined.

:ref:`Custom steps <administration-general-tca-properties-customsteps>` can now
receive an array of arbitrary parameters.


.. _installation-upgrade-500-breaking-changes:

Breaking changes
~~~~~~~~~~~~~~~~

The :php:`\Cobweb\ExternalImport\Step\StoreDataStep` class puts the list of stored
records into the "records" member variable of the :php:`\Cobweb\ExternalImport\Domain\Model\Data`
object. This used to be a simple list of records for the imported table. Since child
tables are now supported, the structure has changed so that there's now a list of
records for each table that was imported. The table name is the key in the first
dimension of the array. If you were relying on this data in a custom step, you will
need to update your code as no backward-compatibility was provided for this change.


.. _installation-upgrade-old:

Upgrade to older version
""""""""""""""""""""""""

In case you are upgrading from a very old version and proceeding step by step,
you find all the old upgrade instructions in the :ref:`Appendix <appendix-old-upgrades>`.


Other requirements
^^^^^^^^^^^^^^^^^^

As was mentioned in the introduction, this extension makes heavy use
of an extended syntax for the TCA. If you are not familiar with the
TCA, you are strongly advised to read up on it in the
:ref:`TCA Reference manual <t3tca:start>`.

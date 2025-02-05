.. include:: /Includes.rst.txt


.. _appendix:

Appendix
--------


.. _appendix-old-upgrades:

Upgrading instructions for older versions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


.. _appendix-old-upgrades-630:
.. _installation-upgrade-630:

Upgrade to 6.3.0
""""""""""""""""

External Import now supports Connector services registered with new system introduced with
extension "svconnector" version 5.0.0, while staying compatible with the older versions.

Another small new feature is the possibility to define a storage pid for the imported data
on the :ref:`command line <user-command>` or when creating a :ref:`Scheduler task <user-scheduler>`,
which overrides storage information that might be found in the TCA or in the extension configuration.


.. _appendix-old-upgrades-620:
.. _installation-upgrade-620:

Upgrade to 6.2.0
""""""""""""""""

The :ref:`Substructure Preprocess event <developer-events-substructure-preprocess>`
is now fired for both array-type and XML-type data (previously, only for array-type data).
To know which type of data is being handled, a new :code:`getDataType()` method is available.
The type of structure that must be returned after modfication (by calling :code:`setStructure()`
must be either an array or a :code:`\DomNodeList`, as opposed to just an array in older versions.
Existing event listeners may need to be adapted.


.. _appendix-old-upgrades-610:
.. _installation-upgrade-610:

Upgrade to 6.1.0
""""""""""""""""

Records which have no external key set (the value referenced by the
:ref:`referenceUid <administration-general-tca-properties-reference-uid>` property)
are now skipped in the import. Indeed it makes no sense to import records without
such keys, as they can never be updated and - if several are created in a single
import run - they will override each other. Still it is a change of behaviour and
should be noted.


.. _appendix-old-upgrades-600:
.. _installation-upgrade-600:

Upgrade to 6.0.0
""""""""""""""""

All properties that were deprecated in version 5.0.0 were removed and the
backwards-compatibility layer was dropped. Please refer to the
:ref:`5.0.0 upgrade instructions <installation-upgrade-500>` and check if you have applied
all changes.

All hooks were marked as deprecated. They will be removed in version 7.0.0.
You should migrate your code to use either :ref:`custom process steps <developer-steps>`
or the newly introduced :ref:`PSR-14 events <developer-events>`.
See the :ref:`hooks chapter <developer-hooks>` for information about how to migrate
each hook.

External Import is now configured for using the standard (Symfony)
dependency injection mechanism. This means it is not necessary to instantiate the
:php:`\Cobweb\ExternalImport\Importer` class using Extbase's
:php:`\TYPO3\CMS\Extbase\Object\ObjectManager` anymore when using the Importer
as an API.

The PHP code was cleaned up as much as possible and strict typing was declared
in every class file. This may break your custom code if you were calling public methods
without properly casting arguments.


.. _appendix-old-upgrades-500:
.. _installation-upgrade-500:

Upgrade to 5.0.0
""""""""""""""""

There are many changes in version 5.0.0, but backwards-compatibility has been
provided for all them (except the minor breaking change mentioned below). Please
make sure to update your configuration as soon as possible, backwards-compatibility
will be dropped in version 5.1.0. Messages for deprecated configuration appear in
the backend module when viewing the details of a configuration.


.. _installation-upgrade-500-changes:

Changes
~~~~~~~

The general configuration must now be placed in :code:`$GLOBALS['TCA'][table-name]['external']['general']`
instead of :code:`$GLOBALS['TCA'][table-name]['ctrl']['external']`.

The "additionalFields" property from the general configuration (and not from the "MM" property)
has been moved to its own configuration space. Rather than
:code:`$GLOBALS['TCA'][table-name]['ctrl']['external'][some-index]['additionalFields]`
it is now :code:`$GLOBALS['TCA'][table-name]['external']['additionalFields'][some-index]`.
Furthermore, it is no longer a simple comma-separated list of fields, but an array structure
with all the same options as standard column configurations.
For more details, :ref:`see the relevant chapter <administration-additionalfields>`.

The "MM" property is deprecated. It should not be used anymore. Instead the new
:ref:`multipleRows <administration-columns-properties-multiple-rows>` or
:ref:`children <administration-columns-properties-children>` properties
should be used according to your import scenario.

The "userFunc" property of the transformations configuration has been renamed to
:ref:`userFunction <administration-transformations-properties-userfunction>` and
its sub-property "params" has been renamed "parameters".

If both "insert" and "update" operations are disabled in the general configuration
(using the :ref:`disabledOperations property <administration-general-tca-properties-disabledoperations>`),
External Import will now delete records that were not marked for update (even if the
actual update does not take place). Previously, no records would have been deleted,
because the entire matching of existing records was skipped.

Accessing the external configuration inside a custom step with
:code:`$this->configuration` or :code:`$this->getConfiguration()` is deprecated.
:code:`$this->getImporter()->getExternalConfiguration()` instead.

The "scheduler" system extension is required instead of just being suggested.


.. _installation-upgrade-500-new:

New stuff
~~~~~~~~~

It is possible to import nested structures using the
:ref:`children <administration-columns-properties-children>` property. For example,
you can now import data into some table and its images all in one go by creating
a nested structure for the "sys\_file\_reference" table.

The :ref:`multipleRows <administration-columns-properties-multiple-rows>` and
:ref:`multipleSorting <administration-columns-properties-multiple-sorting>` properties
allow for a much clearer handling of denormalized external sources.

Check out the revamped :ref:`Mapping data <user-mapping-data>` chapter which should
hopefully help you get a better picture of what is possible with External Import
and how different properties (especially the new ones) can be combined.

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


.. _installation-upgrade-410:
.. _appendix-old-upgrades-410:

Upgrade to 4.1.0
""""""""""""""""

Version 4.1.0 introduces one **breaking change**. There now exists custom permissions
for backend users regarding usage of the backend module. On top of table-related
permissions, users must have been given explicit rights (via the user groups they
belong to) to perform synchronizations or define Scheduler tasks. See the
:ref:`User rights chapter <administration-user-rights>` for more information.


.. _installation-upgrade-400:
.. _appendix-old-upgrades-400:

Upgrade to 4.0.0
""""""""""""""""

.. _installation-upgrade-400-importer-api:
.. _appendix-old-upgrades-400-importer-api:

Importer API changes
~~~~~~~~~~~~~~~~~~~~

The External Import configuration is now fully centralized in a :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`
object. Every time you need some aspect of the configuration, you should get it via the instance
of this class rather than through any other mean. The most current use case was getting the
name of the current table and index from the :php:`\Cobweb\ExternalImport\Importer` class,
using :code:`Importer::getTableName()` and :code:`Importer::getIndex()`. Such methods
were deprecated and should not be used anymore. Use instead:

.. code-block:: php

   $table = $importer->getExternalConfiguration()->getTable();
   $index = $importer->getExternalConfiguration()->getIndex();


The :code:`Importer::synchronizeData()` method was renamed to :code:`Importer::synchronize()` and
the :code:`Importer::importData()` method was renamed to :code:`Importer::import()`. The old methods
were kept, but are deprecated.

The :code:`Importer::synchronizeAllTables()` method should not be used anymore as it does not allow
for a satisfying reporting. Instead a loop should be done on all configurations and
:code:`Importer::synchronize()` called inside the loop. See for example
:code:`\Cobweb\ExternalImport\Command\ImportCommand::execute()`.

Other deprecated methods are :code:`Importer::getColumnIndex()` and :code:`Importer::getExternalConfig()`.

The :code:`Importer::getExistingUids()` method was moved to a new class called
:php:`\Cobweb\ExternalImport\Domain\Repository\UidRepository` (which is a Singleton).


.. _installation-upgrade-400-transformation-properties:
.. _appendix-old-upgrades-400-transformation-properties:

Transformation properties
~~~~~~~~~~~~~~~~~~~~~~~~~

All column properties that are related to the "Transform data" scope have been grouped into a new
property called :ref:`transformations <administration-columns-properties-transformations>`.
This is an ordered array, which makes it possible to use transformation properties several times
on the same field (e.g. calling several user functions) and to do that in a precise order.
As an example, usage of such properties should be changed from:

.. code-block:: php

   $GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = [
         0 => [
               'field' => 'start_date',
               'trim' => true
               'userFunction' => [
                     'class' => \Cobweb\ExternalImport\Task\DateTimeTransformation::class,
                     'method' => 'parseDate'
               ]
         ]
   ];


to:

.. code-block:: php

   $GLOBALS['TCA']['fe_users']['columns']['starttime']['external'] = [
         0 => [
               'field' => 'start_date',
               'transformations => [
                     10 => [
                           'trim' => true
                     ],
                     20 => [
                           'userFunc' => [
                                 'class' => \Cobweb\ExternalImport\Task\DateTimeTransformation::class,
                                 'method' => 'parseDate'
                           ]
                     ]
               ]
         ]
   ];


If you want to preserve "old-style" order, the transformation properties were called in the
following order up to version 3.0.x: "trim", "mapping", "value", "rteEnabled" and "userFunc".
Also note that "value" was ignored if "mapping" was also defined. Now both will be taken into
account if both exist (although that sounds rather like a configuration mistake).

A compatibility layer ensures that old-style transformation properties are preserved, but
this is a temporary convenience, which will be removed in the next version. So please upgrade
your configurations.

.. note::

   The upgrade wizard from version 3.0.0 has been removed. If you are upgrading from TYPO3
   6.2 to TYPO3 8.7, you must go through TYPO3 7.6 first and use the upgrade wizard from
   External Import 3.0.x before moving on to TYPO3 8.7.


.. _installation-upgrade-400-renamed-properties:
.. _appendix-old-upgrades-400-renamed-properties:

Renamed properties
~~~~~~~~~~~~~~~~~~

To continue the move to unified naming conventions for properties started in version 3.0,
the mapping and MM properties which had underscores in their names were moved to
lowerCamelCase name.

The old properties are interpreted for backwards-compatibility, but this will be dropped
in the next major version. The backend module will show you the deprecated properties.


.. _installation-upgrade-400-breaking-changes:
.. _appendix-old-upgrades-400-breaking-changes:

Breaking changes
~~~~~~~~~~~~~~~~

While all hooks were preserved as is, in the sense that they still receive a back-reference
to the :php:`\Cobweb\ExternalImport\Importer` object, the :code:`processParameters`
hook was modified due to its particular usage (it is called in the backend module,
so that processed parameters can be viewed when checking the configuration).
It now receives a reference to the :php:`\Cobweb\ExternalImport\Domain\Model\Configuration`
object and not to the :php:`\Cobweb\ExternalImport\Importer` object anymore.
Please update your hooks accordingly.


.. _installation-upgrade-300:
.. _appendix-old-upgrades-300:

Upgrade to 3.0.0
""""""""""""""""

The "excludedOperations" column configuration, which was deprecated since
version 2.0.0, was entirely removed. The same goes for the "mappings.uid_foreign"
configuration.

More importantly the Scheduler task was renamed from :php:`tx_externalimport_autosync_scheduler_Task`
to :php:`\Cobweb\ExternalImport\Task\AutomatedSyncTask`. As such, existing
Scheduler tasks need to be updated. An upgrade wizard is provided in the
Install Tool. It will automatically migrate existing old tasks.

.. figure:: ../Images/UpdateWizard.png
    :alt: The update wizard shows that there are tasks to update

If there are no tasks to migrate, the External Import wizard will simply not show up.
Otherwise just click on the "Execute" button and follow the instructions.

Several general TCA configuration properties were renamed, to respect a global
lowerCamelCase naming convention. This is the list of properties and how they
were renamed:

- additional\_fields => additionalFields
- reference\_uid => referenceUid
- where\_clause => whereClause


.. _installation-upgrade-200:
.. _appendix-old-upgrades-200:

Upgrade to 2.0.0
""""""""""""""""

The column configuration "excludedOperations" has been renamed to
"disabledOperations", for consistency with the table configuration
option. The "excludedOperations" is preserved for now and will log an
entry into the deprecation log. You are advised to change the naming
of this configuration if you use it, support will be dropped at some
point in the future.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Hooks/Index

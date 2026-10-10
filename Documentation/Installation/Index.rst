.. include:: /Includes.rst.txt


.. _installation:

Installation
------------

Installing this extension does nothing in and of itself. You still
need to extend the TCA definition of some tables with the appropriate
syntax and create specific connectors for the application you want to
connect to.

TYPO3 CMS 13 or 14 is required, as well as the "scheduler" and "reactions" system extensions.


.. _installation-compatibility:
.. _installation-upgrading:

Upgrading and what's new
^^^^^^^^^^^^^^^^^^^^^^^^


.. _installation-upgrade-900:

Upgrade to 9.0.0
""""""""""""""""

Version 9.0.0 adds support for TYPO3 14, while dropping support for TYPO3 12.

Since TYPO3 14, Scheduler tasks are entirely defined using TCA and are thus strictly restricted
to admin users. Rather than trying to work around this even more than what was done up to now,
adding, editing and deleting Scheduler tasks from the External Import backend module has been
restricted to admin users.

Another consequence of this restructuring is that a new Scheduler task :php:`Cobweb\ExternalImport\Task\SynchronizationTask`
replacing :php:`Cobweb\ExternalImport\Task\AutomatedSyncTask` with a cleaner structure.
See details below about the upgrade/migration process.

The deprecated :code:`group` property was definitely removed. If you still used it, you need to switch
to the :ref:`groups <administration-general-tca-properties-groups>` property instead.

A new :ref:`examples chapter <import-configuration-examples>` (with just one example
for now) provides detailed explanations for the most complex configurations.


.. _installation-upgrade-900-scheduler:

Scheduler tasks upgrade/migration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Please follow these steps for upgrading properly:

..  rst-class:: bignums-important

1. If you are running TYPO3 13, ignore this whole process. The new Scheduler task
   exists only for TYPO3 14 and above.

2. Run the upgrade wizard "Migrate the contents of the tx_scheduler_task database table into a more structured form."
   provided by the TYPO3 Core.

3. Run the upgrade wizard "Migrate old AutomatedSyncTask to newer SynchronizationTask"
   provided by External Import.


.. _installation-upgrade-820:

Upgrade to 8.2.0
""""""""""""""""

XPath functions can be used in the :ref:`columns configuration <administration-columns>`
to directly return a string value. Previously XPath expressions could only be used
to select a node or list of nodes in the XML structure.

A new event :ref:`ModifyReactionResponseEvent <developer-events-modify-response>` is available
to modify the response of a reaction before it is sent back. Both the response body
and the HTTP return code may be changed.

Loading of the TCA has been encapsulated into a repository class, making it easier to
follow the evolutions of the TYPO3 Core and allowing developers who might need it
to :ref:`dynamically manipulate the full TCA <developer-tca>` before the External Import
configurations are extracted from it.


.. _installation-upgrade-810:

Upgrade to 8.1.0
""""""""""""""""

:code:`\Cobweb\ExternalImport\Importer::getContext()` and :code:`\Cobweb\ExternalImport\Importer::setContext()`
have been deprecated in favor of :code:`\Cobweb\ExternalImport\Importer::getCallType()` and
:code:`\Cobweb\ExternalImport\Importer::setCallType()`. These methods rely on the :php:`\Cobweb\ExternalImport\Enum\CallType`
enumeration which is used more consistenty throughout External Import.

A new event :ref:`ChangeConfigurationBeforeRunEvent <developer-events-dhange-configuration-before-run>` makes
it possible to modify the External Import configuration at run-time. This happens before any of the import
steps is executed.


.. _installation-upgrade-800:

Upgrade to 8.0.0
""""""""""""""""

Configurations can now be part of several groups. As such, the "group" property is deprecated
and is replaced with the :ref:`groups <administration-general-tca-properties-groups>` property
(with an array value rather than string).

.. note::

   A Rector rule is provided for migration. Use it in your :file:`rector.php` file:

   .. code-block:: php

        return RectorConfig::configure()
            ...
            ->withRules([
                ...
                \Cobweb\ExternalImport\Rector\ChangeGroupPropertyRector::class,
            ])
            ...
        ;


System extension "reactions" is now a requirement. The "Import external data" reaction
can now target a :ref:`group of configurations <administration-general-tca-properties-groups>`.

The logging mechanism has been changed to store the backend user's name rather than its id.
This makes it much easier for the Log module and keeps working even if a user is removed.
An update wizard is available for updating existing log records.

.. warning::

   Don't drop the "cruser_id" field before running the update wizard, or it won't be able
   to do its job.

In version 7.2.0, a change was introduced to preserve :code:`null` values from the imported data.
It affected only fields with :code:`'eval' => 'null'` in their TCA. Since version 8.0.0,
:code:`null` are preserved also for relation-type fields ("group", "select", "inline"
and "file") which have no :code:`minitems` property or :code:`'minitems' => 0`. This makes
it effectively possible to remove existing relations. This is an important change of behavior,
which - although more correct - may have unexpected effects on your date.

A new :ref:`disabled flag <administration-general-tca-properties-disabled>` makes it possible
to completely hide a configuration.


.. _installation-upgrade-600-new:

New stuff
~~~~~~~~~

The :code:`arrayPath` is now available as both a :ref:`general configuration option <administration-general-tca-properties-arraypath>`
and a :ref:`column configuration option <administration-columns-properties-array-path>`.
It was also enriched with more capabilities.

A new exception :php:`\Cobweb\ExternalImport\Exception\InvalidRecordException` was
introduced which can be used inside :ref:`user function <developer-user-functions>`
to remove an entire record from the data to import if needed.

A new transformation property :ref:`isEmpty <administration-transformations-properties-isempty>`
is available for checking if a given data can be considered empty or not.
For maximum flexibility, it relies on the Symfony Expression language.

It is also possible to set multiple mail recipients for the import report
instead of a single one (see the :ref:`extension configuration <installation-configuration>`).


.. _installation-upgrade-old:

Upgrade to older version
""""""""""""""""""""""""

In case you are upgrading from a very old version and proceeding step by step,
you find all the old upgrade instructions in the :ref:`Appendix <appendix-old-upgrades>`.


Other requirements
^^^^^^^^^^^^^^^^^^

As is mentioned in the introduction, this extension makes heavy use
of an extended syntax for the TCA. If you are not familiar with the
TCA, you are strongly advised to read up on it in the
:ref:`TCA Reference manual <t3tca:start>`.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Configuration/Index

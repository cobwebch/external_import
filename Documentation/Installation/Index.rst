.. include:: /Includes.rst.txt


.. _installation:

Installation
------------

Installing this extension does nothing in and of itself. You still
need to extend the TCA definition of some tables with the appropriate
syntax and create specific connectors for the application you want to
connect to.

TYPO3 CMS 12 or 13 is required, as well as the "scheduler" and "reactions" system extensions.


.. _installation-compatibility:
.. _installation-upgrading:

Upgrading and what's new
^^^^^^^^^^^^^^^^^^^^^^^^


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
can now target a :ref:`group of configurations <administration-general-tca-properties-group>`.

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


.. _installation-upgrade-730:

Upgrade to 7.3.0
""""""""""""""""

This version introduces a new reaction dedicated to deleting already import data.


.. _installation-upgrade-720:

Upgrade to 7.2.0
""""""""""""""""

The :php:`HandleDataStep` process now keeps :code:`null` values found in the imported data.
This is an important change, but is has a concrete effect only if the target field is nullable
(i.e. it has an :code:`eval` property including :code:`null` or has property :code:`nullable`
set to :code:`true` in its TCA configuration). In such cases, existing values will be set to
:code:`null` where they would have been left untouched before. It may also affect user functions
in transformations where a :code:`null` value was not expected to be found until now.


.. _installation-upgrade-710:

Upgrade to 7.1.0
""""""""""""""""

External Import now supports PHP 8.2.

When running the preview mode from the backend module, some steps now provide
a download button, to retrieve the data being handled in its current state.

When setting a fixed value, the new :ref:`column configuration property <administration-columns-properties-value>`
should be preferred over the historical :ref:`transformation property <administration-transformations-properties-value>`.

It is now possible to define explicitly :ref:`the order in which columns are processed <administration-general-tca-properties-columnsorder>`.


.. _installation-upgrade-700:

Upgrade to 7.0.0
""""""""""""""""

Support for old-style Connector services was droppped (i.e. connectors registered
as TYPO3 Core Services). If you use custom connector services, make sure to update
them (see the :ref:`update instructions <cobweb/svconnector:installation-updating-500>`
provided by extension "svconnector").

When editing Scheduler tasks in the External Import backend module, it is no longer
possible to define a start date (this tiny feature was a lot of hassle to maintain
across TYPO3 versions).

All hooks were removed. If you were still using hooks, please refer to the
:ref:`archived page about hooks <appendix-hooks>`
to find replacement instructions.

A new :php:`ReportStep` has been introduced, which triggers a webhook reporting about
the just finished import run. In order for this step to run (and do the reporting) even
when the process is aborted, a new possibility has been added for steps to run despite
the interruption. This actually fixes a bug with the :php:`ConnectorCallbackStep` which
was never called when the process was aborted. If you use such a post-processing,
you can now report about failed imports if needed.


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

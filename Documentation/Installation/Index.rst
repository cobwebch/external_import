.. include:: ../Includes.txt


.. _installation:

Installation
------------

Installing this extension does nothing in and of itself. You still
need to extend the TCA definition of some tables with the appropriate
syntax and create specific connectors for the application you want to
connect to.

TYPO3 CMS 11 or 12 is required, as well as the "scheduler" system extension.


.. _installation-compatibility:
.. _installation-upgrading:

Upgrading and what's new
^^^^^^^^^^^^^^^^^^^^^^^^


.. _installation-upgrade-710:

Upgrade to 7.1.0
""""""""""""""""

When running the preview mode from the backend module, some steps now provide
a download button, to retrieve the data being handled in its current state.


.. _installation-upgrade-700:

Upgrade to 7.0.0
""""""""""""""""

Support for old-style Connector services was droppped (i.e. connectors registered
as TYPO3 Core Services). If you use custom connector services, make sure to update
them (see the :ref:`update instructions <svconnector:installation-updating-500>`
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


.. _installation-upgrade-630:

Upgrade to 6.3.0
""""""""""""""""

External Import now supports Connector services registered with new system introduced with
extension "svconnector" version 5.0.0, while staying compatible with the older versions.

Another small new feature is the possibility to define a storage pid for the imported data
on the :ref:`command line <user-command>` or when creating a :ref:`Scheduler task <user-scheduler>`,
which overrides storage information that might be found in the TCA or in the extension configuration.


.. _installation-upgrade-620:

Upgrade to 6.2.0
""""""""""""""""

The :ref:`Substructure Preprocess event <developer-events-substructure-preprocess>`
is now fired for both array-type and XML-type data (previously, only for array-type data).
To know which type of data is being handled, a new :code:`getDataType()` method is available.
The type of structure that must be returned after modfication (by calling :code:`setStructure()`
must be either an array or a :code:`\DomNodeList`, as opposed to just an array in older versions.
Existing event listeners may need to be adapted.


.. _installation-upgrade-610:

Upgrade to 6.1.0
""""""""""""""""

Records which have no external key set (the value referenced by the
:ref:`referenceUid <administration-general-tca-properties-reference-uid>` property)
are now skipped in the import. Indeed it makes no sense to import records without
such keys, as they can never be updated and - if several are created in a single
import run - they will override each other. Still it is a change of behaviour and
should be noted.


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


.. _installation-upgrade-510:

Upgrade to 5.1.0
""""""""""""""""

There is a single change in version 5.1.0 that may affect existing imports:
when a user function fails to handle the value it was supposed to transform
(by throwing an exception), that value is now removed from the imported dataset.
Before that it was left unchanged.


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

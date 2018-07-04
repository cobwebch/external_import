.. include:: ../../Includes.txt


.. _developer-api:

Available APIs
^^^^^^^^^^^^^^

This chapter describes the various APIs and data models existing in this extension
and which might be of use to developers.


.. _developer-api-import:

Import API
""""""""""

As mentioned earlier, External Import can be used from within another piece
of code, just passing it data and benefiting from its mapping, transformation
and storing features.

It is very simple to use this feature. You just need
to assemble data in a format that External Import can understand (XML structure or
PHP array) and call the appropriate method. All you need is an
instance of class :php:`\Cobweb\ExternalImport\Importer` and a single call.

.. warning::

   Since version 4.0.0, the :php:`\Cobweb\ExternalImport\Importer` class must
   be instantiated using Extbase's :php:`\TYPO3\CMS\Extbase\Object\ObjectManager`
   due to its usage of dependency injection.


.. code-block:: php

	$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
	$importer = $objectManager->get(\Cobweb\ExternalImport\Importer::class);
	$messages = $importer->import($table, $index, $rawData);


The call parameters are as follows:

+----------+---------+---------------------------------------------------------+
| Name     | Type    | Description                                             |
+==========+=========+=========================================================+
| $table   | string  | Name of the table to store the data into.               |
+----------+---------+---------------------------------------------------------+
| $index   | integer | Index of the relevant external configuration.           |
+----------+---------+---------------------------------------------------------+
| $rawData | mixed   | The data to store, either as XML (string) or PHP array. |
+----------+---------+---------------------------------------------------------+

The result is a multidimensional array of messages. The first dimension is a status and corresponds to
the :code:`\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR`, :code:`\TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING`
and :code:`\TYPO3\CMS\Core\Messaging\AbstractMessage::OK` constants. The second dimension is a list
of messages. Your code should handle these messages as needed.

.. _developer-api-data-model:

Data Model
""""""""""

The data that goes through the import process is encapsulated in the
:php:`\Cobweb\ExternalImport\Domain\Model\Data` class. This class contains
two member variables:

rawData
  The data as it is read from the external source or as it is passed to
  the import API. Given the current capacities of External Import, this
  may be either a string representing a XML structure or a PHP array.

records
  The data as structured by External Import, step after step.

There are getters and setters for each of these.


.. _developer-api-configuration-model:

Configuration Model
"""""""""""""""""""

Whenever an import is run, the corresponding TCA configuration is loaded
into an instance of the :php:`\Cobweb\ExternalImport\Domain\Model\Configuration` class.
The main member variables are:

table
  The name of the table for which data is being imported.

index
  The index of the configuration being used.

ctrlConfiguration
  The "ctrl" part of the External Import TCA configuration.

columnConfiguration
  The columns configuration part of the External Import TCA configuration.

additionalFields
  Array containing the list of :ref:`additional fields <administration-general-tca-properties-additional-fields>`.
  This should be considered a runtime cache for an often requested property.

countAdditionalFields
  Number of additional fields. This is also a runtime cache.

steps
  List of steps the process will go through. When the External Import configuration is loaded,
  the list of steps is established, based on the type of import (synchronized or via the API)
  and any :ref:`custom steps <developer-steps>`. This ensures that custom steps are handled
  in a single place.

connector
  The Configuration object also contains a reference to the Connector service used to read
  the external data, if any.

There are getters and setters for each of these.


.. _developer-api-importer-class:

The Importer class
""""""""""""""""""

Beyond the :code:`import()` method mentioned above the :php:`\Cobweb\ExternalImport\Importer` class
also makes a number of internal elements available via getters:

getExtensionConfiguration
  Get an array with the unserialized extension configuration.

getExternalConfiguration
  Get the current instance of the :ref:`Configuration model <developer-api-configuration-model>`.

setContext/getContext
  Define or retrieve the execution context. This is mostly informative and is used to set a
  context for the log entries. Expected values are "manual", "cli", "scheduler" and "api".
  Any other value can be set, but will not be interpreted by the External Import extension.
  In the Log module, such values will be displayed as "Other".

setDebug/getDebug
  Define or retrieve the debug flag. This makes it possible to programatically turn
  debugging on or off.

setVerbose/getVerbose
  Define or retrieve the verbosity flag. This is currently used only by the command-line
  utility for debugging output.

and a few more which are not as significant and can be explored by
anyone interested straight in the source code.

For reporting, the :php:`\Cobweb\ExternalImport\Importer` class also provides
the :code:`addMessage()` method which takes as arguments a message and a severity
(using the constants of the :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage`
class).


.. _developer-api-call-context:

The call context
""""""""""""""""

External Import may be called in various contexts (command line, Scheduler task,
manual call in the backend or API call). While the code tries to be as generic as possible,
it is possible to hit some limits in some circumstances. The "call context" classes
have been designed for such situations.

A call context class must inherit from :php:`\Cobweb\ExternalImport\Context\AbstractCallContext`
and implement the necessary methods. There is currently a single method called
:code:`outputDebug()` which is supposed to display some debug output. Currently a specific
call context exists only for the command line and makes it possible to display
debugging information in the Symfony console.


.. _developer-api-reporting:

The reporting utility
"""""""""""""""""""""

The :php:`\Cobweb\ExternalImport\Utility\ReportingUtility` class is in charge
of giving feedback in various contexts, lik sending an email once a synchronization
is finished.

It provides a generic API for storing values from :php:`Step` classes that could
make sense in terms of reporting. Currently this is used only by the
:php:`\Cobweb\ExternalImport\Step\StoreDataStep` class which reports on the number
of operations performed (inserts, updates, deletes and moves).

.. note::

   These values are not used for any reporting for now. The number of updates is used
   in functional tests. Improved reporting could ensue in the future.

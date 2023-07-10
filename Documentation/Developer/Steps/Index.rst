.. include:: ../../Includes.txt


.. _developer-steps:

Custom process steps
^^^^^^^^^^^^^^^^^^^^

Besides all the :ref:`events <developer-events>`, it is also possible to
register custom process steps. How to register a custom step is
covered in the :ref:`Administration chapter <administration-general-tca-properties-customsteps>`.
This section describes what a custom step can or should do and
what resources are available from within a custom step class.


.. _developer-steps-parent-class:

Parent class
""""""""""""

A custom step class **must** inherit from abstract class
:php:`\Cobweb\ExternalImport\Step\AbstractStep`. If it does not,
the step will be ignored during import. The parent class makes
a lot of features available some of which are described below.

If you want to use Dependency Injection in your custom step class,
just remember to declare it as being public in your service configuration file.


.. _developer-steps-resources:

Available resources
"""""""""""""""""""

A custom step class has access to the following member variables:

data
  Instance of the object model encapsulating the data being processed
  (:php:`\Cobweb\ExternalImport\Domain\Model\Data`).

importer
  Back-reference to the current instance of the :php:`\Cobweb\ExternalImport\Importer` class.

parameters
  Array of parameters declared in the configuration of the custom step.

See the :ref:`API chapter <developer-api>` for more information about these classes.

Furthermore, the custom step class can access a member variable called :code:`abortFlag`.
Setting this variable to :code:`true` will cause the import process to be aborted
**after** the custom step. Any such interruption is logged by the
:php:`\Cobweb\ExternalImport\Importer` class, albeit without any detail. If you feel
the need to report about the reason for interruption, do so from
within the custom step class:

.. code-block:: php

      $this->getImporter()->addMessage(
           'Your message here...',
           FlashMessage::WARNING // or whatever error level
      );

It is also possible to mark a custom step so that it is executed even if the process
was aborted by a previous step. This is done by setting the :code:`executeDespiteAbort`
member variable to :code:`true` in the constructor.

.. code-block:: php

    public function __construct() {
        $this->setExecuteDespiteAbort(true);
    }

In general, use the getters and setters to access the member variables.


.. _developer-steps-basics:

Custom step basics
""""""""""""""""""

A custom step class must implement the :code:`run()` method. This method
receives no arguments and returns nothing. All interactions with the process
happens via the member variables described above and their API.

The main reason to introduce a custom step is to manipulate the data being
processed. To read the data, use:

.. code-block:: php

	// Read the raw data or...
	$rawData = $this->getData()->getRawData();
	// Read the processed data
	$records = $this->getData()->getRecords();

.. note::

   Depending on when you custom step happens, there may not yet be any raw
   nor processed data available.

If you manipulate the data, you need to store it explicitely:

.. code-block:: php

	// Store the raw data or...
	$this->getData()->setRawData();
	// Store the processed data
	$this->getData()->setRecords();

Another typical usage would be to interrupt the process entirely
by setting the :code:`abortFlag` variable to :code:`true`, as mentioned
above.

The rich API that is available makes it possible to do many things beyond
these. For example, one could imagine changing the External Import configuration
on the fly.

In general the many existing :code:`Step` classes provide many examples
of API usage and should help when creating a custom process step.


.. _developer-steps-preview:

Preview mode
""""""""""""

It is very important that your custom step respects the
:ref:`preview mode <user-backend-module-synchronizable-preview>`.
This has two implications:

#. If relevant, you should return some preview data. For example,
   the :code:`TransformDataStep` class returns the import data once
   transformations have been applied to it, the :code:`StoreDataStep`
   class returns the TCE structure, and so on. There's an API for returning
   preview data:

   .. code-block:: php

		$this->getImporter()->setPreviewData(...);

   The preview data can be of any type.

#. **Most importantly**, you must respect the preview mode and not make
   any persistent changes, like saving stuff to the database. Use the API
   to know whether preview mode is on or not:

   .. code-block:: php

		$this->getImporter()->isPreview();

#. Indicate that the :code:`records` of the :code:`Data` object are downloadable
   if it makes sense (see the :ref:`Data model API <developer-api-data-model>`).
   This is done by overriding the :code:`hasDownloadableData()` method
   of the :php:`\Cobweb\ExternalImport\Step\AbstractStep` class to return :code:`true`.


.. _developer-steps-example:

Example
"""""""

Finally here is a short example of a custom step class. Note how the API is used
to retrieve the list of records (processed data), which is looped over and then
saved again to the :code:`Data` object.

In this example, the "name" field of every record is used to filter acceptable entries.

.. warning::

   Note the call to :code:`array_values()` to compact the array again once records
   have been removed. This is very important to avoid having empty entries in your
   import.

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Cobweb\ExternalimportTest\Step;

   use Cobweb\ExternalImport\Step\AbstractStep;

   /**
    * Class demonstrating how to use custom steps for external import.
    *
    * @package Cobweb\ExternalimportTest\Step
    */
   class TagsPreprocessorStep extends AbstractStep
   {

       /**
        * Filters out some records from the raw data for the tags table.
        *
        * Any name containing an asterisk is considered censored and thus removed.
        */
       public function run(): void
       {
           $records = $this->getData()->getRecords();
           foreach ($records as $index => $record) {
               if (strpos($record['name'], '*') !== false) {
                   unset($records[$index]);
               }
           }
           $records = array_values($records);
           $this->getData()->setRecords($records);
           $this->getData()->isDownloadable(true);
           // Set the filtered records as preview data
           $this->importer->setPreviewData($records);
       }

       /**
        * Define the data as being downloadable
        *
        * @return bool
        */
       public function hasDownloadableData(): bool
       {
           return true;
       }
   }

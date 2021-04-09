.. include:: ../../Includes.txt


.. _developer-steps:

Custom process steps
^^^^^^^^^^^^^^^^^^^^

Besides all the :ref:`hooks <developer-hooks>`, it is also possible to
register custom process steps. How to register a custom step is
covered in the :ref:`Administration chapter <administration-general-tca-properties-customsteps>`.
This section describes what a custom step can or should do and
what resources are available from within a custom step class.

Custom steps are much more powerful than hooks and should be preferred
whenever that makes sense. Custom steps have access to much more information
than hooks, although most hooks are more specific.
Also custom steps are registered **per** configuration, which removes the need
to test inside them which import configuration is being handled.


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

configuration
  Instance of the current External Import configuration
  (:php:`\Cobweb\ExternalImport\Domain\Model\Configuration`).

  .. warning::

     This has been deprecated in External Import 5.0.0. Please use
     :code:`$this->getImporter()->getExternalConfiguration()` instead.

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

.. note::

   Custom steps get to manipulate the whole data set, contrary to many
   of the hooks, which are called while looping on each entry in the
   data set.

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


.. _developer-steps-example:

Example
"""""""

Finally here is a short example of a custom step class. Note how the API is used
to retrieve the list of records (processed data), which is looped over and then
saved again to the :code:`Data` object.

In this example, the "name" field of every record is postfixed with a
simple string.

.. code-block:: php

      <?php
      namespace Cobweb\ExternalimportTest\Step;

      use Cobweb\ExternalImport\Step\AbstractStep;

      /**
       * Class demonstrating how to use custom steps for external import.
       *
       * @package Cobweb\ExternalimportTest\Step
       */
      class EnhanceDataStep extends AbstractStep
      {
          /**
           * Performs some dummy operation to demonstrate custom steps.
           *
           * @return void
           */
          public function run()
          {
              $records = $this->getData()->getRecords();
              foreach ($records as $index => $record) {
                  $records[$index]['name'] = $record['name'] . ' (base)';
              }
              $this->getData()->setRecords($records);
          }
      }

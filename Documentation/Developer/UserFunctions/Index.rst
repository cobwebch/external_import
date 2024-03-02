.. include:: /Includes.rst.txt


.. _developer-user-functions:

User functions
^^^^^^^^^^^^^^

The external import extension can call user functions for any field
where external data is imported. Some sample functions are provided in
:file:`Classes/Transformation/DateTimeTransformation.php`
and :file:`Classes/Transformation/ImageTransformation.php`.

Basically, the function receives three parameters:

+--------------+---------+-----------------------------------------------------------------------+
| Name         | Type    | Description                                                           |
+==============+=========+=======================================================================+
| $record      | array   | The complete record being handled. This makes it possible to refer to |
|              |         | other fields of the same record during the transformation, if needed. |
+--------------+---------+-----------------------------------------------------------------------+
| $index       | string  | The key of the field to transform. Modifying other fields in the      |
|              |         | record is not possible since the record is passed by value and not by |
|              |         | reference. Only the field corresponding to this key should be         |
|              |         | transformed and returned.                                             |
+--------------+---------+-----------------------------------------------------------------------+
| $parameters  | array   | Additional parameters passed to the function. This will be very       |
|              |         | specific to each function and can even be completely omitted.         |
|              |         | External import will pass an empty array to the user function if the  |
|              |         | "parameters" property is not defined.                                 |
+--------------+---------+-----------------------------------------------------------------------+

The function is expected to return only the value of the transformed field.

.. warning::

   The record received as input into the user function has
   already gone through the renaming the fields. That means the names of the
   fields are not those of the external data, but those of the TYPO3 CMS
   fields.

   If unsure, use the Preview mode to look at the results of the Handle Data step.

The class containing the user function may implement the :php:`\Cobweb\ExternalImport\ImporterAwareInterface`
(using the :php:`\Cobweb\ExternalImport\ImporterAwareTrait` or not). In such a case, it will have access to
the :php:`Importer` instance simply by using :php:`$this->getImporter()`. In particular, this makes it possible
for user functions to check if the current run is operating in preview mode or in debug mode.

The function may throw the special exception :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
This will cause the "Transform Data" step to abort. More details in the chapter about
:ref:`critical exceptions <developer-critical-exceptions>`.

The function may also throw the special exception :php:`\Cobweb\ExternalImport\Exception\InvalidRecordException`.
The related record will be removed from the imported dataset.

The function may throw any other kind of exception if the transformation it is supposed to apply
to the value it receives fails. This will trigger the removal of this value from the imported
dataset, thus avoiding that it be further processed and eventually saved to the database.

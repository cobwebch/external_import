.. include:: ../../Includes.txt


.. _developer-user-functions:

User functions
^^^^^^^^^^^^^^

The external import extension can call user functions for any field
where external data is imported. A sample function is provided in
:file:`Classes/Transformation/DateTimeTransformation.php`.
Basically, the function receives three parameters:

+----------+---------+-----------------------------------------------------------------------+
| Name     | Type    | Description                                                           |
+==========+=========+=======================================================================+
| $record  | array   | The complete record being handled. This makes it possible to refer to |
|          |         | other fields of the same record during the transformation, if needed. |
+----------+---------+-----------------------------------------------------------------------+
| $index   | string  | The key of the field to transform. Modifying other fields in the      |
|          |         | record is not possible since the record is passed by value and not by |
|          |         | reference. Only the field corresponding to this key should be         |
|          |         | transformed and returned.                                             |
+----------+---------+-----------------------------------------------------------------------+
| $params  | array   | Additional parameters passed to the function. This will be very       |
|          |         | specific to each function and can even be completely omitted.         |
|          |         | External import will pass an empty array to the user function if the  |
|          |         | "params" property is not defined.                                     |
+----------+---------+-----------------------------------------------------------------------+

The function is expected to return only the value of the transformed field.

.. warning::

   The record received as input into the user function has
   already gone through renaming the fields. That means the names of the
   fields are not those of the external data, but those of the TYPO3 CMS
   fields.

The class containing the user function may implement the :php:`\Cobweb\ExternalImport\ImporterAwareInterface`
(using the :php:`\Cobweb\ExternalImport\ImporterAwareTrait` or not). In such a case, it will have access to
the :php:`Importer` instance simply by accessing :php:`$this->importer`. In particular, this makes it possible
for user functions to check if the current run is operating in preview mode or in debug mode.

The function may throw the special exception :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
This will cause the "Transform Data" step to abort. More details in the chapter about
:ref:`critical exceptions <developer-critical-exceptions>`.

.. note::

   When adding a new user function, you may suddenly be faced with an autoloading
   error, which is not solved by a flushing of the cache. Indeed TYPO3 CMS builds
   autoloading information which is not considered to use a cache. So flushing
   all the cache will not help TYPO3 CMS to detect your new class. You will need to
   either uninstall and reinstall the extension to which your class belongs
   or recreate the autoloading information cache using the Install Tool.

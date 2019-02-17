.. include:: ../../Includes.txt


.. _developer-critical-exceptions:

Interrupting the process: critical exceptions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

One exception class plays a particular role: :php:`\Cobweb\ExternalImport\Exception\CriticalFailureException`.
It can be thrown from within a :ref:`user function <developer-user-functions>` or a
:ref:`hook <developer-hooks>` and will cause the import process to abort.

The reason for this exception is to react to some critical issue that may happen during
the call to a user function or a hook and which affects the whole import process.
For example, if you are transforming a date and a single record has an invalid date, you
probably don't want to interrupt the whole process for this. You want to record the issue
is some way, but not pull the hand brake. On the other hand, say that you are saving some files
and the target file storage is not available: you will probably want to stop the process
before every record is saved with its related files.

Such exception thrown from within any user function will cause the "Transform Data"
step to abort. When thrown from within a hook it may abort the "Transform Data",
the "Handle Data", the "Validate Data" or the "Store Data" steps. For the latter, however,
note that data may have already been saved depending on which hook it is thrown from.
Refer to the :ref:`chapter about hooks <developer-hooks>` for more details.

Make sure to include a helpful error message when throwing this exception.

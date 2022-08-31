.. include:: ../../Includes.txt


.. _user-debugging:

Debugging
^^^^^^^^^

There are many potential sources of error during synchronization, from
wrong mapping configurations to missing user rights to PHP errors in
user functions. When a synchronization is launched from the BE module
a status is displayed when the operation is finished.

The extension tries to report at best on the success or failure of the operation.
Turning on the "debug" mode (see the :ref:`Configuration chapter <installation-configuration>`)
will provide additional information.

As described in the :ref:`Configuration chapter <installation-configuration>`,
it is also possible to receive a detailed report by email.
It will contain a general summary of what happened during synchronization,
but also all error messages logged by the TYPO3 Core Engine, if any.


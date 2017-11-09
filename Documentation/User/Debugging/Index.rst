.. include:: ../../Includes.txt


.. _user-debugging:

Debugging
^^^^^^^^^

There are many potential sources of error during synchronization, from
wrong mapping configurations to missing user rights to PHP errors in
user functions. When a synchronization is launched from the BE module
a status is displayed when the operation is finished.

However if some other errors happen, like a PHP error, or any type of
output produced by calls to debug methods will not be visible because
they are flushed before the ExtDirect response is sent. On way around
this is to use an extension that writes to the Developer's Log
(e.g. `devlog <http://typo3.org/extensions/repository/view/devlog/>`_)
and activate :code:`$TYPO3_CONF_VARS[SYS][enable_DLOG]` .

As described in the :ref:`Configuration chapter <configuration>`,
it is also possible to receive a detailed report by email.
It will contain a general summary of what happened during synchronization,
but also all error messages logged by TCEmain, if any.


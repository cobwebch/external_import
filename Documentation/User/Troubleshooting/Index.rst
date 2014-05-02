.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _user-troubleshooting:

Troubleshooting
^^^^^^^^^^^^^^^


.. _user-backend-troubleshooting-not-executed:

The automatic synchronization is not being executed
"""""""""""""""""""""""""""""""""""""""""""""""""""

You may observe that the scheduled synchronization is not taking place
at all. Even if the debug mode is activated and you look at the
devLog, you will see no call to external\_import. This may happen when
you set a too high frequency for synchronizations (like 1 minute for
example). If the previous synchronization has not finished, the
Scheduler will prevent the new one from taking place. The symptom is a
message like "[scheduler]: Event is already running and multiple
executions are not allowed, skipping! CRID: xyz, UID: nn" in the
system log (System > Log). In this case you should delete the
existing schedule and set up a new one.


.. _user-backend-troubleshooting-neverending:

The manual synchronization never ends
"""""""""""""""""""""""""""""""""""""

It may be that no results are reported during a manual synchronization
and that the looping arrows continue spinning endlessly. This happens
when something failed completely during the synchronization and the BE
module received no response. See the advice in :ref:`Debugging <user-debugging>`.


.. _user-backend-troubleshooting-all-deleted:

All the existing data was deleted
"""""""""""""""""""""""""""""""""

The most likely cause is that the external data could not be fetched,
resulting in zero items to import. If the delete operation is not
disabled, External import will take that as a sign that all existing
data should be deleted, since the external source didn't provide
anything.

There are various ways to protect yourself against that. Obviously you
can disable the delete operation, so that no record ever gets deleted.
If this is not desirable, you can use the "minimumRecords" option (see
"General TCA configuration") below. For example, if you always expect
at least 100 items to be imported, set this option to 100. If fewer
items than this are present in the external data, the import process
will be aborted and nothing will get deleted.


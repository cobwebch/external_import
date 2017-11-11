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
system log (**SYSTEM > Log**). In this case you should delete the
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
:ref:`General TCA configuration <administration-general-tca>`) below.
For example, if you always expect at least 100 items to be imported,
set this option to 100. If fewer items than this are present in the
external data, the import process will be aborted and nothing will get deleted.


.. _user-backend-troubleshooting-empty-fields:

Can I leave out records with "empty" fields?
""""""""""""""""""""""""""""""""""""""""""""

A likely scenario is wanting to leave out records where one field is empty.
There's no configuration property for that as it is a difficult topic.
First of all what constitutes an "empty field" will vary depending on
the incoming data and what handling is applied to it. What is more
one may want to filter the data at different points in the process
(e.g. after the data is read or after the data is transformed).

This is why there is no configuration property for "requiring" a field.
Such a need is better addressed by creating a :ref:`custom step <developer-steps>`,
that can applied specific criteria and at a precise point in the
import process.

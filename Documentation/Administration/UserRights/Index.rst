.. include:: ../../Includes.txt


.. _administration-user-rights:

User rights
^^^^^^^^^^^

Before digging into the TCA specifics let's have a look at the topic
of user rights. Since External Import relies on :class:`\\TYPO3\\CMS\\Core\\DataHandling\\DataHandler`
for storing data, the user rights on the synchronized tables will always be
enforced. However additional checks are performed in both the BE
module and the automated tasks to avoid displaying sensitive data or
throwing needless error messages.

When accessing the BE module, user rights are taken into account in
that:

- a user must have at least listing rights on a table to see it in the
  BE module.

- a user must have modify rights on a table to be allowed to synchronize
  it manually or define an automated synchronization for it.

DB mount points are not checked for at this point, so the user may be
able to start a synchronization and still get error messages if not
allowed to write to the page where the imported data should be stored.

When a synchronization runs automatically a check on user rights is
also performed at the beginning, so that the synchronization can be
skipped entirely if the CLI user does not have modify rights on the
given table. This is reported in the mail report.

An automated synchronization will be run by the Scheduler. This
means that the active user will be :code:`_cli_scheduler`, so this user
needs to have enough rights to perform all expected operations, in
particular:

- authorize this user to list and modify the tables that are going to be
  synchronized

- give this user access to the page(s) where the records are stored,
  i.e. pages must be in the DB Mounts of the user and user must have enough
  rights on these pages, i.e. "Show page", "Edit content", "Edit page"
  and "Delete page" (Web > Access). Of course this can also be achieved
  via a BE group the user belongs to.

A good way to verify that the :code:`_cli_scheduler` use has enough rights
is to use the **SYSTEM > Backend users** module to switch to that user and perform
manual synchronizations from there (this means giving access to the
"External Import" BE module to the :code:`_cli_scheduler` user).


.. _administration-user-rights-typo3-8:

User rights since TYPO3 CMS 8
"""""""""""""""""""""""""""""

The setup of user rights for the Scheduler has become much easier
since TYPO3 CMS 8. Indeed all command-line calls are made with the
generic :code:`_cli_` user, which has admin rights.

The same is true for :ref:`command-line calls <user-command>`.

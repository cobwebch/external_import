.. include:: ../../Includes.txt


.. _user-command:

The command-line interface
^^^^^^^^^^^^^^^^^^^^^^^^^^

Since version 4.0 and using TYPO3 CMS 8 and above, a command-line
interface to External Import is available. It can be used to
run a single synchronization or all of them (in order of
increasing priority).

Calling :code:`path/to/php path/to/bin/typo3 externalimport:sync --list` will
output a list of all configurations available for synchronization.

Synchronizing everything is achieved by calling: :code:`path/to/php path/to/bin/typo3 externalimport:sync --all`.

Finally a single configuration can be synchronized by calling:
:code:`path/to/php path/to/bin/typo3 externalimport:sync --table=foo --index=bar`.

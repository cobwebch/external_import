.. include:: ../../Includes.txt


.. _user-command:

The command-line interface
^^^^^^^^^^^^^^^^^^^^^^^^^^

The External Import process can be called from the command line.
It can be used to run a single synchronization, all of them or a group of them.
When several synchronizations are run, they happen in order of
increasing priority. The following operations are possible:

List all configurations available for synchronization
  :code:`path/to/php path/to/bin/typo3 externalimport:sync --list`

Synchronize everything
  :code:`path/to/php path/to/bin/typo3 externalimport:sync --all`.

Synchronize a :ref:`group of configurations <administration-general-tca-properties-group>`
  :code:`path/to/php path/to/bin/typo3 externalimport:sync --group=(group name)`.

Synchronize a single configuration
  :code:`path/to/php path/to/bin/typo3 externalimport:sync --table=foo --index=bar`.


.. _user-command-storage:

Forcing the storage page
""""""""""""""""""""""""

The :code:`storage` flag can be used to pass the id of a page in the TYPO3 system
where the imported data will be stored. This overrides both the TCA and the extension
settings.


.. _user-command-preview:

Running in preview mode
"""""""""""""""""""""""

Preview mode can be activated by using the :code:`preview` flag and a :code:`Step`
class name as argument. The import process will stop after the given step and return
some preview data (or not; that depends on the step). No permanent changes are made
(e.g. nothing is saved to the database).

A typical command will look like:

.. code-block:: text

	path/to/php path/to/bin/typo3 externalimport:sync --table=foo --index=bar --preview='Cobweb\\ExternalImport\\Step\\TransformDataStep'

This will stop the process after the :code:`TransformDataStep` and dump the transformed
data in the standard output. Mind the correct syntax for defining the :code:`Step` class
(quote with no opening backslash).

.. note::

   If running a full or group synchronization, the preview mode will apply to each
   configuration.


.. _user-command-debug:

Debugging on the command-line
"""""""""""""""""""""""""""""

Debugging on the command-line is achieved by using the verbose flag, which is
available for all commands. If global debugging is turned on
(see the :ref:`Extension configuration <installation-configuration>`), debugged variables
will be dumped along with the usual output from the External Import command.
If global debugging is disabled, it can be enabled for a single run, by
using the "debug" flag:

.. code-block:: text

	path/to/php path/to/bin/typo3 externalimport:sync --table=foo --index=bar --debug -v

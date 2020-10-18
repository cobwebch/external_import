.. include:: ../../Includes.txt


.. _administration-log-cleanup:

Log cleanup
^^^^^^^^^^^

The log table can be cleaned up automatically using the *Table garbage collection* Scheduler task.

A new entry for that task can be created with the following options:

* *Table to clean up*: :code:`tx_externalimport_domain_model_log`
* *Delete entries older than given number of days*: 30 (default)

A pre-configuration exists in the :file:`ext_localconf.php` file with a configuration
of 180 days.

If you run a lot of imports, make sure that this table is cleaned up regularly.

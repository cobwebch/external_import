.. include:: ../Includes.txt


.. _configuration:

Configuration
-------------

The extension has the following configuration options:

Storage PID
  Defines a general page where all the imported records
  are stored. This can be overridden specifically for each table (see
  Administration below).

Log storage PID
  Defines a page where log entries will be stored. The default
  is :code:`0` (root page).

Force time limit
  Sets a maximum execution time (in seconds) for
  the manual import processes (i.e. imports launched from the BE
  module). This time limit affects both PHP (where the default value is
  defined by :code:`max_execution_time`) and the AJAX calls triggered by the
  BE module (where the default limit is 30 seconds). This is necessary
  if you want to run large imports. Setting this value to -1 preserves
  the default time limit.

Email for reporting
  If an email address is entered here, a detailed
  report will be sent to this address after every automated
  synchronization. Mails are not sent after synchronizations started
  manually from the BE module.Note that the mail reporting feature needs
  a valid e-mail address to be available for sending from. This will
  either be the mail of the :code:`_cli_scheduler` (or :code:`_cli_`) user
  or the default mail address of the TYPO3 installation
  (:code:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`). If
  neither of these mails are available, the report will not be sent and
  an error will appear in the **SYSTEM > Log**.

Subject of email report
  A label that will be prepended to the subject of the reporting mail.
  It may be convenient – for example – to use the server's name, in case
  you have several servers running the same imports.

Debug
  Check to enable the extension to log some data during import runs.
  This may have an effect depending on the call context (e.g. in verbose mode
  on the command line, debug output will be sent to standard output).
  Debug output is routed using the Core Logger API.
  Hence if you wish to see more details, you may want to add specific
  configuration for the :php:`\Cobweb\ExternalImport\Importer` class which centralizes logging.
  Example:

  .. code-block:: php

		$GLOBALS['TYPO3_CONF_VARS']['LOG']['Cobweb']['ExternalImport']['Importer']['writerConfiguration'] = [
			// configuration for ERROR level log entries
			\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
				// add a FileWriter
				\TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
					// configuration for the writer
					'logFile' => 'typo3temp/logs/typo3_import.log'
				]
			]
		];

Disable logging
  Disables logging by the TYPO3 Core Engine. By default
  an entry will be written in the System > Log for each record
  touched by the import process. This may create quite a lot of log
  entries on large imports. Checking this box disables logging for
  **all** tables. It can be overridden at table-level by the
  :ref:`disableLog <administration-general-tca-properties-disablelog>`.

  .. warning::

     There is one big drawback to this method however.
     If core logging is disabled, errors are not tracked at all.
     This means that the import will run happily all the time and
     never report errors. You will unfortunately have to choose
     between errors not being reported and your log being flooded.



.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

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
  either be the mail of the :code:`_cli_scheduler` user or the default mail
  address of the TYPO3 installation (
  :code:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']` ). If
  neither of these mails are available, the report will not be sent and
  an error will appear in the System > Log.

Subject of email report
  A label that will be prepended to the
  subject of the reporting mail. It may be convenient – for example – to
  use the server's name, in case you have several servers running the
  same imports.

Preview/Debug limit
  This is the maximum number of rows that will
  be dumped to the devlog when debugging is turned on. It will also be
  used as the number of rows displayed during a preview, when that
  feature is implemented.

Debug
  Check to enable the extension to store some log data
  (requires an extension such as `devlog <http://typo3.org/extensions/repository/view/devlog/>`_).

Disable logging
  Disables logging by TCEmain. By default
  an entry will be written in the System > Log for each record
  touched by the import process. This may create quite a lot of log
  entries on large imports. Checking this box disables logging for
  **all** tables. It can be overridden at table-level by the
  "disableLog" flag (see "General TCA configuration").

  .. warning::

     There is one big drawback to this method however.
     If TCEmain logging is disabled, errors are not tracked at all.
     This means that the import will run happily all the time and
     never report errors. You will unfortunately have to choose
     between errors not being reported and your log being flooded.



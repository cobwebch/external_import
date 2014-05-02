.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _user-backend-module:

Using the backend module
^^^^^^^^^^^^^^^^^^^^^^^^


.. _user-backend-module-synchronizable:

Synchronizable tables
"""""""""""""""""""""

The first function of the BE module – called "Tables with
synchronization" – displays a list of all synchronizable tables. The
various features are summarized in the picture below.

.. figure:: ../../Images/SynchronizableTablesOverview.png
	:alt: BE module overview for synchronizable tables

	Overview of the synchronizable tables view with all available
	functions


.. note::

   Some icons may not appear depending on user rights.
   Users without write access to a given table will not see the
   synchronize button, nor any of the actions related to the Scheduler.

Clicking on the information icon will open a pop-up window containing
all the information about that particular configuration. The view
consists of two tabs: the first one displays the configuration from
the "ctrl" section of the TCA ("General information"), the second one
displays the configuration for each column ("Columns mapping").

.. figure:: ../../Images/InformationInspector.png
	:alt: Inspecting TCA properties

	Viewing the details of the TCA properties for external import


Clicking on the synchronize data button will immediately start
the synchronization of the corresponding table. This may take quite
some time if the data to import is large. If you move away from the BE
module during that time, the process will still complete, but you will
not get any feedback about the results. If you wait until the end of
the process, pop-up messages will appear with the results:

.. figure:: ../../Images/SynchronizationResults.png
	:alt: Results of synchronization

	Pop-up message show the results of the synchronization


.. _user-backend-module-automation:

Setting up the automatic schedule
"""""""""""""""""""""""""""""""""

The automatic scheduling facility relies on the Scheduler to run. On
top of the normal Scheduler setup, there are some points you must pay
particular attention to in the case of external import.

As can be seen in the above screenshot, the information whether the
automatic synchronization is enabled or not is displayed for each
table. It is possible to add or change that schedule, by clicking on
the respective icons. This triggers the display of a pop-up window
with an input form where you can choose a start date (date of first
execution; leave empty for immediate activation) and a frequency. The
frequency can be entered as a number of seconds or using the same
syntax as for cron jobs.

.. figure:: ../../Images/AutomationDialog.png
	:alt: Automation dialog box

	Dialog box for setting automated synchronization parameters


Clicking on the trash can icon cancels the automatic
synchronization (a confirmation window will appear first).

At the top of the screen, before the list, it is possible to define a
schedule for **all** tables. This means that all imports will be
executed one after the other, in the order of priority.

.. figure:: ../../Images/FullAutomation.png
	:alt: Automating all tables

	Setting automated synchronization for all tables


Clicking on the "Activate" or "Modify" button will trigger the
same window as for individual tables. Clicking on "Deactivate" will
remove the scheduling.

Defining a schedule is not enough. Proper user rights must also be
considered. See the "User rights" section in the "Administration"
chapter.

.. note::

   Of course, it is perfectly possible to define automation tasks
   from within the Scheduler's BE module. External Import offers this
   as a convenience and also for non-admin users.


.. _user-backend-module-non-synchronizable:

Non-synchronizable tables
"""""""""""""""""""""""""

The second function of the BE module – called "Tables without
synchronization" – displays a list of non-synchronizable tables. This
view is purely informative as no action can be taken for these tables.

.. figure:: ../../Images/NonSynchronizableTablesOverview.png
	:alt: BE module overview for non-synchronizable tables

	Overview for non-synchronizable tables, with just the information icon

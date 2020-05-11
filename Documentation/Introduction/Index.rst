.. include:: ../Includes.txt


.. _introduction:

Introduction
------------

This extension is designed to fetch data from external sources
and store them into tables of the TYPO3 CMS database. The mapping
between this external data and the TYPO3 CMS tables is done
by extending the syntax of the :ref:`TCA <t3tca:start>`.
A backend module provides a way to synchronize any table manually
or to define a scheduling for all synchronizations.
Synchronizations can also be run using the command-line interface.
Automatic scheduling can be defined using a Scheduler task.

The main idea of getting external data into the TYPO3 CMS database
is to be able to use TYPO3 CMS standard functions on that data
(such as enable fields, for example, if available).

Connection to external applications is handled by a class of services
called "connectors", the base of which is available as a separate extension
(:ref:`svconnector <svconnector:start>`).

Data from several external sources can be stored into the same table
allowing data aggregation.

The extension also provides an API for sending it data from some other source.
This data is stored into the TYPO3 CMS database using the same mapping process
as when data is fetched directly by the extension.

This extension contains a number of hooks as well as the possibility to call
user-defined functions during the import process or to create custom steps,
which makes it a quite flexible tool. However it was not designed for extensive
data manipulation. It is assumed that the data received from the external source
is in "palatable" format. If the external data requires a lot of processing,
it is probably better to put it through an ETL or ESB tool first,
and then import it into TYPO3 CMS.

Please also check extension :ref:`externalimport_tut <tut:start>`
which provides a tutorial to this extension.

More examples can be found in extension "externalimport_test", which is used
for testing purposes. The setup is not documented, but can be interesting
to look at. This extension is distributed only via Github:
https://github.com/fsuter/externalimport_test


.. _suport:

Questions and support
^^^^^^^^^^^^^^^^^^^^^

If you have any questions about this extension, please ask them in the
TYPO3 English mailing list, so that others can benefit from the
answers. Please use the bug tracker on GitHub to report
problems or suggest features
(https://github.com/cobwebch/external_import/issues).


.. _happy-developer:

Keeping the developer happy
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Every encouragement keeps the developer ticking, so don't hesitate
to send thanks or share your enthusiasm about the extension.

If you appreciate this work and want to show some support, please
check https://www.monpetitcoin.com/en/support-me/.


.. _participate:

Participating
^^^^^^^^^^^^^

This tool can be used in a variety of situations and all use cases are
certainly not covered by the current version. I will probably not have
the time to implement any use case that I don't personally need.
However you are welcome to join the development team if you want to
bring in new features. If you are interested use GitHub to submit pull
requests.


.. _sponsoring:

Sponsoring
^^^^^^^^^^

You are very welcome to support the further development of this
extension. You will get mentioned here.

- A good part of the development of version 3.0 was sponsored by the
  `State of Vaud <http://vd.ch>`_.

- The :ref:`xmlValue <administration-columns-properties-xmlvalue>`
  property was sponsored by `Bendoo e-work solutions <http://www.bendoo.nl/en/>`_.

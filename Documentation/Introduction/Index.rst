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

This extension is quite flexible, thanks to the possibility of calling user
functions to transform incoming data, listening to events to react to some part
of the process or adding custom steps at any point in the process.
It is also possible to create custom connectors for reading from a specific
external source. Still this extension was not designed for extensive data manipulation.
It is assumed that the data received from the external source
is in a "palatable" format. If the external data requires a lot of processing,
it is probably better to put it through an ETL or ESB tool first,
and then import it into TYPO3 CMS.

Please also check extension :ref:`externalimport_tut <tut:start>`
which provides a tutorial to this extension.

More examples can be found in extension "externalimport_test", which is used
for testing purposes. The setup is not documented, but can be interesting
to look at. This extension is distributed only via Github:
https://github.com/cobwebch/externalimport_test

.. note::

   Setting up External Import can be quite tricky, mostly because this extension offers
   many options, that are meant to cover as many import scenarios as possible. These
   options can often be combined for even more possibilities. This can be quite
   confusing in the beginning.

   Please take time to read the whole :ref:`User manuel chapter <user>` and the
   already mentioned tutorial. In particular, you should read the following sections:

   - :ref:`General considerations <user-general>`
   - :ref:`Process overview <user-overview>`
   - :ref:`Mapping data <user-mapping-data>`


.. _other-extensions:

Differences with other extensions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

There exists several extensions for importing data into TYPO3, including the system
extension "impexp". Extension "impexp" is specifically designed to export data from
a TYPO3 installation and import it again into TYPO3, using a specific file format ("T3D").
When the need is to move around data that is already in a TYPO3 installation, "impexp" is the logical choice.
External Import differs by being designed to import data into TYPO3 from a large variety of
sources **outside** TYPO3.

There are other extensions available, like `xlsimport <https://extensions.typo3.org/extension/xlsimport>`_
and `importr <https://extensions.typo3.org/extension/importr>`_, which were released
years after External Import and - as such - I never really looked into them since I had all the tools
I needed. So it is hard to compare their features.

"xlsimport" can import only Excel and CSV format, but mostly cannot be automated (no Scheduler task,
nor command line call). Also the import configuration cannot be saved and must be repeated
each time. On the other hand, this is very convenient for one-time imports, definitely
quicker and lighter to set up than External Import.

"importr" seems to come quite close to External Import in terms of features, although
maybe with less flexibility in the data handling and less import sources (import
resources can probably be added). It is probably easier to set up than External Import,
since it allows for simply pointing to an Extbase model, plus a simple mapping of fields
to import.


.. _suport:

Questions and support
^^^^^^^^^^^^^^^^^^^^^

If you have any questions about this extension, use the dedicated channel in the
TYPO3 Slack workspace (#ext-external_import) or the issue tracker on GitHub
(https://github.com/cobwebch/external_import/issues).

Please also check the :ref:`Troubleshooting section <user-troubleshooting>`
in case your issue is already described there.


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
  property was sponsored by `Bendoo e-work solutions <https://www.bendoo.nl/en/>`_.

- The development of version 5.0 benefited from much sponsoring:

  - `Idéative <https://www.ideative.ch/>`_
  - `Bendoo e-work solutions <https://www.bendoo.nl/en/>`_
  - `mehrwert intermediale kommunikation GmbH <https://www.mehrwert.de/>`_
  - Benni Mack
  - Tomas Norre

  Without these companies and people, it would never have been such a great update!

- The development of version 6.0 was largely funded by the `Lausanne University Hospital (CHUV) <https://www.lausanneuniversityhospital.com/home>`_


.. _credits:

Credits
^^^^^^^

The icon for the log table records is derived from an icon made by `iconixar <https://www.flaticon.com/authors/iconixar>`_
from `www.flaticon.com <https://www.flaticon.com/>`_.

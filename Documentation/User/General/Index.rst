.. include:: /Includes.rst.txt


.. _user-general:

General considerations
^^^^^^^^^^^^^^^^^^^^^^

The purpose of this extension is to take data from somewhere else
(called the "*external source*") than the local TYPO3 CMS database and store
it into that local database. Data from the external source is matched
to local tables and fields, using information stored in the TCA with
the extended syntax provided by this extension.

The extension can either fetch the data from some external source or
receive data from any kind of script using the provided API. Fetching
data from an external source goes through a standardized process.

Connecting to an external source is achieved using connector services
(:ref:`see extension svconnector <cobweb/svconnector:start>`), that return the fetched data to
the external import in either XML format or as a PHP array. Currently, the
following connectors exist:

- **svconnector_csv** for CSV and silimar flat files
- **svconnector_feed** for XML source files
- **svconnector_json** for JSON source files
- **svconnector_sql** for connecting to another database

It is quite easy to develop a :ref:`custom connector <cobweb/svconnector:developers-api>`,
should that be needed.

The external data is mapped to one or more TYPO3 CMS tables
using the extended TCA syntax. From then on the table can be
synchronized with the external source. Every time a synchronization is
started (either manually or according to a schedule), the connector
service is called upon to fetch the data. Such tables are referred to
as "**synchronizable tables**". This type of action is called
"*pulling data*".

On the other hand this extension also provides an API that can be
called up to pass data directly to the external import process. No
connector services are used in this case. The extension is called on a
need-to basis by any script that uses it. As such it is not possible
to synchronize those tables from the BE module, nor to schedule their
synchronization. Such tables are referred to as "**non-synchronizable tables**".
This type of action is called "*pushing data*".

Note that it is perfectly possible to also push data towards
synchronizable tables. The reverse is not true (non-synchronizable
tables cannot pull data).

It is perfectly possible to define several import configurations for the same
table, thus pulling or pushing data from various sources into a single destination.

Synchronizations can be run in preview mode.

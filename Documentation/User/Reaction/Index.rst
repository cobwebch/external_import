.. include:: /Includes.rst.txt


.. _user-reaction:

Reaction (External Import endpoint)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When using TYPO3 12, External Import provides a reaction, i.e. an endpoint
which can be called by any third-party software to push data to import.

.. _user-reaction-reaction:

Defining the reaction
"""""""""""""""""""""

A reaction must be defined using the "Reactions" module in the TYPO3 backend.
There can be more than one External Import reaction depending on your needs.
Having several reactions allows you to distribute secret keys to different people.

.. figure:: ../../Images/Reaction.png
    :alt: Defining a reaction

    Defining a reaction in the dedicated backend module


Choosing a configuration is optional. If one is chosen, the reaction will only
execute if the incoming configuration matches the selected configuration. This
provides better safety, but is more restrictive.

It is absolutely necessary to choose a BE user to impersonate, otherwise the data
will not be stored. The easiest option is to choose the :code:`_cli_` user but
this may seem too encompassing. You can use another BE user or define a specific
one, but make sure that it has the proper rights for writing to the table(s) targeted
by the import.

.. _user-reaction-configuration:

External Import configuration
"""""""""""""""""""""""""""""

The External Import configuration does not need anything special to be used by a
reaction. However if it is only ever used by reactions, then it does not need
connector information and can thus be a :ref:`Non-synchronizable table <user-backend-module-non-synchronizable>`.


.. _user-reaction-payload:

Request payload
"""""""""""""""

To call the endpoint and trigger the External Import reaction, you need to call
the URI given by the reaction and pass it the secret key in the headers. The payload
in the request body is comprised of the following information:

table
  The name of the table targeted by the import (not necessary when a configuration is explicitly defined).

index
  The index of the targeted External Import configuration (not necessary when a configuration is explicitly defined).

data
  The actual data to import. This can be either a JSON array (for
  :ref:`array-type data <administration-general-tca-properties-data>`) or
  a (XML) string for :ref:`XML-type data <administration-general-tca-properties-data>`).

pid (optional)
  If defined, this uid from the "pages" table will override the
  :ref:`pid property <administration-general-tca-properties-pid>` from
  the general configuration.

Here is how it could look like (example made with Postman):

.. figure:: ../../Images/ReactionRequestHeaders.png
    :alt: Request headers

    The header with the URI, the accepted content type and the secret key


.. figure:: ../../Images/ReactionRequestBody.png
    :alt: Request body

    The body of the payload with the table name, configuration index and data to import

.. include:: ../../Includes.txt


.. _user-tutorial:

Tutorial
^^^^^^^^

Extension :ref:`externalimport_tut <tut:start>` provides
an extensive tutorial about external import.
It makes use of many configuration options.
All examples are discussed in the extension's manual.


.. _user-tutorial-test:

Test extension
""""""""""""""

Extension `externalimport_test <https://github.com/cobwebch/externalimport_test/>`_
also contains many example configurations which are use for integration (functional) testing.
The extension itself does not contain a detailed documentation like the tutorial,
but it is still a useful resource. The many scenarios and features covered in
that extension are briefly mentioned below to help you find your way around it.
It is structured according to the file names containing the TCA, either in
:file:`Configuration/TCA`.

tx_externalimporttest_bundle.php
  Scenario: import of 1:n relationships (bundles to products) with denormalized data,
  preserving sorting order, using :ref:`multipleRows <administration-columns-properties-multiple-rows>`
  and :ref:`multipleSorting <administration-columns-properties-multiple-sorting>`.

  Additional usage of: additional fields, user function transformations, array path (at column level).

tx_externalimporttest_designer.php
  Scenario: import data (designers) nested inside other data (products) in a XML structure using XPath
  (:ref:`nodepath <administration-general-tca-properties-nodepath>` property).

tx_externalimporttest_invoice.php
  Scenario: import denormalized data from a XML file with namespaced tags,
  using properties :ref:`namespaces <administration-general-tca-properties-namespaces>`
  and :ref:`fieldNS <administration-columns-properties-fieldns>`.

tx_externalimporttest_order.php
  Scenario: import 1:n relationships (orders to products) from nested data into an
  IRRE structure. Usage of :ref:`arrayPath <administration-general-tca-properties-arraypath>` (at general level),
  :ref:`substructureFields <administration-columns-properties-substructure-fields>`
  and :ref:`children <administration-columns-properties-children>` properties.

tx_externalimporttest_product.php
  Products are used for testing several scenarios. They are described below
  according to the configuration key:

  - **base**: usage of an EventListener (listening to :php:`Cobweb\ExternalImport\Event\ProcessConnectorParametersEvent`),
    of a custom step, of XPath at column level (property :ref:`xpath <administration-columns-properties-xpath>`);
    creation of 1:n relations to tags from comma-separated values (property
    :ref:`multipleValuesSeparator <administration-mapping-properties-multiplevaluesseparator>`) and creation of file references
    using both :ref:`substructureFields <administration-columns-properties-substructure-fields>`
    and :ref:`children <administration-columns-properties-children>` properties.

  - **more**: simpler import scenario than "base", but from a siliar XML structure and thus
    the same mapping. Tests the usage of the :ref:`useColumnIndex <administration-general-tca-properties-usecolumnindex>`
    property.

  - **stable**: same as "more", testing the disabling of both "update" and "delete" operations,
    using property :ref:`disabledOperations <administration-general-tca-properties-disabledoperations>`.

  - **products_for_stores**: creation of m:n relations between stores and products, from the
    product side. Again usage of the :ref:`children <administration-columns-properties-children>` property
    for creating IRRE entries.

  - **general_configuration_errors**: as the name implies, this configuration contains many errors and is used
    for testing the general configuration validator.

  - **updated_products**: importing products that change name (for testing the
    :ref:`updateSlugs <administration-general-tca-properties-update-slugs>` property)
    and also that change "pid" (for testing the moving of records).

tx_externalimporttest_store.php
  Scenario: import stores and their m:n relations to products, from the store side,
  again usage of the :ref:`children <administration-columns-properties-children>` property
  for creating IRRE entries.

tx_externalimporttest_tag.php
  Like products, tags are used to test several scenarios:

  - **0**: usage of a custom step to filter out some entries.

 - **only-delete**: this one is really specific to integration testing, as it is used
   to test the deletion of existing tags (loaded from a fixture during testing) when
   importing.

 - **api**: tests the usage of External Import as an API. See class
   :php:`\Cobweb\ExternalimportTest\Command\ImportCommand`.

Overrides/pages.php
  Scenario: importing some data (in this case products) as pages to test ordering
  and nesting (some pages are children of others). The configuration itself is very simple.

Overrides/sys_category.php
  Two scenarios are tested here:

  - **product_categories**: simple import into an existing table, extending for storing the external id.

  - **column_configuration_errors**: this configuration contains many errors and is used
    for testing the column configuration validator.

Overrides/tx_externalimporttest_product.php
  This is just used to demonstrate how to make a table categorizable and import categories relationships.
  It is related to the **"base"** configuration for products above.

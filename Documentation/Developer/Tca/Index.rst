.. include:: /Includes.rst.txt


.. _developer-tca:

Dynamic TCA loading
^^^^^^^^^^^^^^^^^^^

Retrieval of the TCA global array is encapsulated in a class called
:php:`\Cobweb\ExternalImport\Domain\Repository\TcaDirectAccessRepository`
which implements the :php:`\Cobweb\ExternalImport\Domain\Repository\TcaRepositoryInterface`
interface. This system pursues three aims:

1. encapsulating the retrieval of the TCA to simplify following up the evolutions in the
   TYPO3 Core (like the introduction of the TCA Schema in TYPO3 13).

2. abstracting into a base class (:php:`\Cobweb\ExternalImport\Domain\Repository\AbstractTcaRepository`)
   all the logic for retrieving all External Import-related configuration from the TCA.

3. allowing developers to perfom dynamic manipulations on the TCA by providing
   their own TCA repository class through dependency injection. This is detailed below.


.. _developer-tca-custom-repository:

Custom TCA repository
~~~~~~~~~~~~~~~~~~~~~

Although an :ref:`event exists for manipulating a single import configuration <developer-events-dhange-configuration-before-run>`,
it is not unusual to have repetitive import configurations, sometimes implying a
dynamic modification of the TCA. For such special cases, it may be useful to provide
your own custom implementation of a TCA repository.

The recommended way is to extend the abstract class :php:`\Cobweb\ExternalImport\Domain\Repository\AbstractTcaRepository`
which implements all the methods related to extracting the External Import configurations
from the TCA. The only method to implement is :code:`getTca()`, where you can
perform any processing you need. Then simply declare your repository as a service
replacing :php:`\Cobweb\ExternalImport\Domain\Repository\TcaDirectAccessRepository`,
by placing in your extension's :file:`Services.yaml` file the following:

.. code-block:: yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      Vendor\ExtName\Import\DynamicTcaRepository:
        decorates: Cobweb\ExternalImport\Domain\Repository\TcaRepositoryInterface
        public: true

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _appendixa-typo343:

Upgrade to TYPO3 4.3 and the Scheduler
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you already have a complete setup using Gabriel on a TYPO3 4.2 or
less box, the upgrade process will not be completely smooth. Indeed
TYPO3 4.3 provides a Core integration of Gabriel called "Scheduler".
This comes as a system extension and represents a serious improvement
on Gabriel.

So if you upgrade to TYPO3 4.3, you should really drop Gabriel and use
the Scheduler instead. The drawback is that you will lose the
currently scheduled imports as it is not possible to transfer Gabriel
information to the Scheduler (too much changed between the two tools).
That should not keep you from switching though, as the Scheduler
offers far more control and reporting on scheduled jobs (and Gabriel
support was dropped from External Import as of version 2.0.0).


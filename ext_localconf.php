<?php
// Register method with generic BE ajax calls handler
// (as from TYPO3 4.2)

$TYPO3_CONF_VARS['BE']['AJAX']['externalimport::synchronizeExternalTable'] = 'typo3conf/ext/external_import/class.tx_externalimport_ajax.php:tx_externalimport_ajax->synchronizeExternalTable';

// Register handler class for Gabriel

$TYPO3_CONF_VARS['EXTCONF']['gabriel']['include']['external_import'] = 'class.tx_externalimport_autosync.php';
?>
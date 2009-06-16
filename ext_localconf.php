<?php
// Register method with generic BE ajax calls handler
// (as from TYPO3 4.2)

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['externalimport::synchronizeExternalTable'] = 'typo3conf/ext/external_import/class.tx_externalimport_ajax.php:tx_externalimport_ajax->synchronizeExternalTable';

// Register handler class for Gabriel
if (t3lib_extMgm::isLoaded('gabriel')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['gabriel']['include'][$_EXTKEY] = 'class.tx_externalimport_autosync.php';
}

// Register handler calls for Scheduler
if (t3lib_extMgm::isLoaded('scheduler')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['scheduler']['include'][$_EXTKEY] = array(
		0 => array(
				'class' => 'EXT:scheduler/class.tx_externalimport_autosync_scheduler.php:tx_externalimport_autosync_scheduler',
				'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.title',
				'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.description',
				'hasArguments' => true,
				'argumentsHelp' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.argumentsHelp'
			)
	);
}
?>
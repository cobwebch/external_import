<?php
	// Register method with generic BE ajax calls handler
	// (as from TYPO3 4.2)
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['externalimport::synchronizeExternalTable'] = 'typo3conf/ext/external_import/class.tx_externalimport_ajax.php:tx_externalimport_ajax->synchronizeExternalTable';

	// Register handler calls for Scheduler
if (t3lib_extMgm::isLoaded('scheduler')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_externalimport_autosync_scheduler_Task'] = array(
		'extension'			=> $_EXTKEY,
		'title'				=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.title',
		'description'		=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.description',
		'additionalFields'	=> 'tx_externalimport_autosync_scheduler_AdditionalFieldProvider'
	);
}
t3lib_extMgm::registerExtDirectComponent(
	'TYPO3.ExternalImport.ExtDirect',
	t3lib_extMgm::extPath($_EXTKEY, 'Classes/ExtDirect/Server.php:Tx_ExternalImport_ExtDirect_Server'),
	'user_txexternalimportM1',
	'user,group'
);
?>
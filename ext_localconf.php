<?php
// Register handler calls for Scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Cobweb\ExternalImport\Task\AutomatedSyncTask::class] = array(
	'extension'			=> $_EXTKEY,
	'title'				=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.title',
	'description'		=> 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.description',
	'additionalFields'	=> \Cobweb\ExternalImport\Task\AutomatedSyncAdditionalFieldProvider::class
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent(
	'TYPO3.ExternalImport.ExtDirect',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes/ExtDirect/Server.php:Tx_ExternalImport_ExtDirect_Server'),
	'user_ExternalImportExternalImport',
	'user,group'
);

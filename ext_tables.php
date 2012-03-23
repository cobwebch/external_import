<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
//	t3lib_extMgm::addModule('user', 'txexternalimportM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');

		// Add context sensitive help (csh) to the backend module
//	t3lib_extMgm::addLLrefForTCAdescr('_MOD_user_txexternalimportM1', 'EXT:' . $_EXTKEY . '/mod1/locallang_csh_externalimport.xml');
}
	// Avoid loading the module when in the frontend or the Install Tool
if (TYPO3_MODE == 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
		// Register the backend module
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'user', // Make module a submodule of 'user'
		'external_import', // Submodule key
		'', // Position
		array(
				// An array holding the controller-action-combinations that are accessible
			'Listing' => 'sync,noSync'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/moduleIcon.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml'
		)
	);
}
?>
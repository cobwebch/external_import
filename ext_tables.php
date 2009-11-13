<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModule('user', 'txexternalimportM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');

		// Add context sensitive help (csh) to the backend module
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_user_txexternalimportM1', 'EXT:' . $_EXTKEY . '/mod1/locallang_csh_externalimport.xml');
}
?>
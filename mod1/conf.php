<?php
/*
 * Module configuration
 *
 * $Id: conf.php 3434 2007-07-30 11:02:27Z fsuter $
 */
// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/external_import/mod1/');
$BACK_PATH = '../../../../typo3/';
$MCONF['name'] = 'user_txexternalimportM1';

$MCONF['extKey'] = 'external_import';
	
$MCONF['access'] = 'user,group';
$MCONF['script'] = 'index.php';

$MLANG['default']['tabs_images']['tab'] = 'moduleicon.gif';
$MLANG['default']['ll_ref'] = 'LLL:EXT:external_import/mod1/locallang_mod.xml';
?>
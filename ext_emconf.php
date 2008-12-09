<?php

########################################################################
# Extension Manager/Repository config file for ext: "external_import"
#
# Auto generated 04-08-2008 08:49
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'External Data Import',
	'description' => 'This backend module can be used to import data from external sources into TYPO3 tables extended with special definitions in the TCA and connector services.',
	'category' => 'module',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.7.2',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:20:{s:9:"ChangeLog";s:4:"ecaf";s:10:"README.txt";s:4:"7e81";s:32:"class.tx_externalimport_ajax.php";s:4:"d46d";s:36:"class.tx_externalimport_autosync.php";s:4:"02e6";s:36:"class.tx_externalimport_importer.php";s:4:"43ec";s:21:"ext_conf_template.txt";s:4:"6848";s:12:"ext_icon.gif";s:4:"d913";s:17:"ext_localconf.php";s:4:"ab11";s:14:"ext_tables.php";s:4:"0fb5";s:13:"locallang.xml";s:4:"a0cf";s:33:"tx_externalimport_ajaxhandler.php";s:4:"d536";s:14:"doc/manual.sxw";s:4:"7783";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"f5bd";s:14:"mod1/index.php";s:4:"bd9d";s:18:"mod1/locallang.xml";s:4:"78cf";s:22:"mod1/locallang_mod.xml";s:4:"7a33";s:19:"mod1/moduleicon.gif";s:4:"b786";s:16:"res/prototype.js";s:4:"d3a5";s:30:"res/icons/refresh_animated.gif";s:4:"e1d4";}',
	'suggests' => array(
	),
);

?>
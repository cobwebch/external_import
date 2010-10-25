<?php

########################################################################
# Extension Manager/Repository config file for ext "external_import".
#
# Auto generated 25-10-2010 21:39
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'External Data Import',
	'description' => 'This backend module can be used to import data from external sources into TYPO3 tables extended with special definitions in the TCA and connector services.',
	'category' => 'module',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => 'svconnector',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			'svconnector' => '1.1.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'external_import_tut' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"d766";s:10:"README.txt";s:4:"7e81";s:32:"class.tx_externalimport_ajax.php";s:4:"09ff";s:36:"class.tx_externalimport_importer.php";s:4:"e732";s:16:"ext_autoload.php";s:4:"be7b";s:21:"ext_conf_template.txt";s:4:"a608";s:12:"ext_icon.gif";s:4:"d913";s:17:"ext_localconf.php";s:4:"328e";s:14:"ext_tables.php";s:4:"fe73";s:13:"locallang.xml";s:4:"ed6b";s:33:"tx_externalimport_ajaxhandler.php";s:4:"2d3d";s:53:"autosync/class.tx_externalimport_autosync_factory.php";s:4:"1be3";s:59:"autosync/class.tx_externalimport_autosync_gabriel_event.php";s:4:"ac1d";s:79:"autosync/class.tx_externalimport_autosync_scheduler_additionalfieldprovider.php";s:4:"c12a";s:60:"autosync/class.tx_externalimport_autosync_scheduler_task.php";s:4:"07ce";s:53:"autosync/class.tx_externalimport_autosync_wrapper.php";s:4:"3cb0";s:61:"autosync/class.tx_externalimport_autosync_wrapper_gabriel.php";s:4:"67ca";s:63:"autosync/class.tx_externalimport_autosync_wrapper_scheduler.php";s:4:"f180";s:14:"doc/manual.pdf";s:4:"2d2b";s:14:"doc/manual.sxw";s:4:"5630";s:13:"mod1/conf.php";s:4:"f5bd";s:14:"mod1/index.php";s:4:"199c";s:18:"mod1/locallang.xml";s:4:"f6ad";s:37:"mod1/locallang_csh_externalimport.xml";s:4:"06dd";s:22:"mod1/locallang_mod.xml";s:4:"7a33";s:19:"mod1/moduleicon.gif";s:4:"b786";s:16:"res/prototype.js";s:4:"d3a5";s:24:"res/tx_externalimport.js";s:4:"cc0a";s:26:"res/icons/preview_data.gif";s:4:"526a";s:30:"res/icons/refresh_animated.gif";s:4:"e1d4";s:51:"samples/class.tx_externalimport_transformations.php";s:4:"bbc9";}',
	'suggests' => array(
	),
);

?>
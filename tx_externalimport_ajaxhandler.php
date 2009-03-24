<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id$
***************************************************************/

require_once('conf.php');
require_once($BACK_PATH.'init.php');

//$LANG->includeLLFile('EXT:external_import/mod1/locallang.xml');
//$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

// Make instance

require_once(t3lib_extMgm::extPath('external_import').'mod1/class.tx_externalimport_ajax.php');
$ajaxHandler = t3lib_div::makeInstance('tx_externalimport_ajax');

// Execute called for function

$result = $ajaxHandler->execute();

// Encode result as JSON and return it

require_once(PATH_typo3.'contrib/json.php');
$jsonObject = t3lib_div::makeInstance('Services_JSON');

echo $jsonObject->encode($result);
?>
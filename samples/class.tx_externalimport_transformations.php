<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2014 Francois Suter (Cobweb) <typo3@cobweb.ch>
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

/**
 * Example transformation functions for the 'external_import' extension
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_externalimport
 */
class tx_externalimport_transformations implements t3lib_Singleton {

	/**
	 * This an example method to be called by external import for transforming data
	 * It receives the full record and the index of the field it is expected to transform
	 * It also receives any additional parameters that might have been set in the TCA
	 * It is expected to return the transformed field only
	 *
	 * In particular, this method will parse a date field using strtotime() to extract a timestamp
	 * and will return a formatted string using either date() or strftime() and a format parameter
	 * or simply the Unix timestamp if no formatting function was defined.
	 *
	 * @param	array	$record: the full record that is being transformed
	 * @param	string	$index: the index of the field to transform
	 * @param	array	$params: additional parameters from the TCA
	 * @return	mixed	Timestamp or formatted date string
	 */
	public function parseDate($record, $index, $params) {
		$value = strtotime($record[$index]);
			// Consider time zone offset
			// This is necessary because TCEmain will subtract the time zone offset upon saving the data,
			// so the offset must be added first to compensate for this
			// NOTE: this correction was made dependent on a parameter (enforceTimeZone), but it might
			// make sense to execute it all the time, given the context in which this method is called
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone']) && !empty($params['enforceTimeZone'])) {
			$value += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
		}
			// Format value only if a function was defined
		if (isset($params['function'])) {
				// Use strftime for formatting
			if ($params['function'] == 'strftime') {
				$value = strftime($params['format'], $value);
			}
				// Otherwise use date
			else {
				$value = date($params['format'], $value);
			}
		}
		return $value;
	}
}
?>
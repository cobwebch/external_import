<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Francois Suter <typo3@cobweb.ch>
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
***************************************************************/

/**
 * Testcase for the External Import importer
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_externalimport
 *
 * $Id$
 */
class tx_externalimport_importer_Test extends tx_phpunit_testcase {
	/**
	 * Local instance for testing
	 *
	 * @var tx_externalimport_importer
	 */
	protected $importer;

	public function setUp() {
		$this->importer = t3lib_div::makeInstance('tx_externalimport_importer');
	}

	/**
	 * Provide data for soft matching with strpos
	 * (currently provides only one data set, but could provide more in the future)
	 *
	 * @return array
	 */
	public function mappingTestWithStrposProvider() {
		$data = array(
			'australia' => array(
				'inputData' => 'Australia',
				'mappingTable' => array(
					'Commonwealth of Australia' => 'AU',
					'Kingdom of Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'strpos',
					'match_symmetric' => FALSE
				),
				'result' => 'AU'
			)
		);
		return $data;
	}

	/**
	 * Provide data for soft matching with strpos
	 * (currently provides only one data set, but could provide more in the future)
	 *
	 * @return array
	 */
	public function mappingTestWithStrposSymmetricProvider() {
		$data = array(
			'australia' => array(
				'inputData' => 'Commonwealth of Australia',
				'mappingTable' => array(
					'Australia' => 'AU',
					'Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'strpos',
					'match_symmetric' => TRUE
				),
				'result' => 'AU'
			)
		);
		return $data;
	}

	/**
	 * Provide data for soft matching with strpos
	 *
	 * @return array
	 */
	public function mappingTestWithStrposWithBadMappingTableProvider() {
		$data = array(
				// Case doesn't match in this data set
			'wrong case' => array(
				'inputData' => 'australia',
				'mappingTable' => array(
					'Commonwealth of Australia' => 'AU',
					'Kingdom of Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'strpos',
					'match_symmetric' => FALSE
				)
			),
			'no matching data' => array(
				'inputData' => 'Swaziland',
				'mappingTable' => array(
					'Commonwealth of Australia' => 'AU',
					'Kingdom of Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'strpos',
					'match_symmetric' => FALSE
				)
			)
		);
		return $data;
	}

	/**
	 * Provide data that will not match for strpos
	 *
	 * @return array
	 */
	public function mappingTestWithStriposProvider() {
		$data = array(
			'australia' => array(
				'inputData' => 'australia',
				'mappingTable' => array(
					'Commonwealth of Australia' => 'AU',
					'Kingdom of Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'stripos',
					'match_symmetric' => FALSE
				),
				'result' => 'AU'
			)
		);
		return $data;
	}

	/**
	 * Provide data for soft matching with strpos
	 * (currently provides only one data set, but could provide more in the future)
	 *
	 * @return array
	 */
	public function mappingTestWithStriposWithBadMappingTableProvider() {
		$data = array(
				// Case doesn't match in this data set
			'no matching data' => array(
				'inputData' => 'Swaziland',
				'mappingTable' => array(
					'Commonwealth of Australia' => 'AU',
					'Kingdom of Spain' => 'ES'
				),
				'mappingConfiguration' => array(
					'match_method' => 'strpos',
					'match_symmetric' => FALSE
				)
			)
		);
		return $data;
	}

	/**
	 * Test soft-matching method with strpos
	 *
	 * @test
	 * @dataProvider mappingTestWithStrposProvider
	 */
	public function matchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult) {
		$actualResult = $this->importer->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * Test soft-matching method with strpos and symmetric flag
	 *
	 * @test
	 * @dataProvider mappingTestWithStrposSymmetricProvider
	 */
	public function matchWordsWithStrposSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult) {
		$actualResult = $this->importer->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @dataProvider mappingTestWithStrposWithBadMappingTableProvider
	 * @expectedException UnexpectedValueException
	 */
	public function failMatchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration) {
		$this->importer->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
	}

	/**
	 * Test soft-matching method with stripos
	 *
	 * @test
	 * @dataProvider mappingTestWithStriposProvider
	 */
	public function matchWordsWithStirposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult) {
		$actualResult = $this->importer->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @dataProvider mappingTestWithStriposWithBadMappingTableProvider
	 * @expectedException UnexpectedValueException
	 */
	public function failMatchWordsWithStriposNotSymmetric($inputData, $mappingTable, $mappingConfiguration) {
		$this->importer->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
	}
}

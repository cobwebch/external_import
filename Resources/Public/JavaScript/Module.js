/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/ExternalImport/Module
 * External Import module JS
 */
define(['jquery',
		'TYPO3/CMS/Backend/Modal',
		'datatables',
		'TYPO3/CMS/Backend/jquery.clearable'
	   ], function($, Modal) {
	'use strict';

	var ExternalImportModule = {
		table: null
	};

	/**
	 * Activates DataTable on the synchronizable tables list view.
	 *
	 * @param tableView
	 */
	ExternalImportModule.buildTableForSynchronizableList = function(tableView) {
		var columns = [
			// Icon
			{
				targets: 0,
				orderable: false
			},
			// Table
			{
				targets: 1,
				orderable: true
			},
			// Description
			{
				targets: 2,
				orderable: true
			},
			// Priority
			{
				targets: 3,
				orderable: true
			},
			// Action icons
			{
				targets: 4,
				orderable: false
			},
			// Scheduler task information
			{
				targets: 5,
				orderable: true
			},
			// Scheduler action icons
			{
				targets: 6,
				orderable: false
			}
		];
		ExternalImportModule.table = tableView.DataTable({
			dom: 't',
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		ExternalImportModule.table.order([3, 'asc']).draw();
		ExternalImportModule.initializeSearchField();
	};

	/**
	 * Activates DataTable on the non-synchronizable tables list view.
	 *
	 * @param tableView
	 */
	ExternalImportModule.buildTableForNonSynchronizableList = function(tableView) {
		var columns = [
			// Icon
			{
				targets: 0,
				orderable: false
			},
			// Table
			{
				targets: 1,
				orderable: true
			},
			// Description
			{
				targets: 2,
				orderable: true
			},
			// Action icons
			{
				targets: 3,
				orderable: false
			}
		];
		ExternalImportModule.table = tableView.DataTable({
			dom: 't',
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		ExternalImportModule.table.order([1, 'asc']).draw();
		ExternalImportModule.initializeSearchField();
	};

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	ExternalImportModule.initializeSearchField = function() {
		$('#tx_externalimport_search')
			.on('input', function() {
				ExternalImportModule.table.search($(this).val()).draw();
			})
			.clearable({
				onClear: function() {
					if (ExternalImportModule.table !== null) {
						ExternalImportModule.table.search('').draw();
					}
				}
			})
			.parents('form').on('submit', function() {
				return false;
			});
	};

	/**
	 * Initialize this module
	 */
	$(function() {
		var tableView = $('#tx_externalimport_list');
		if (tableView.length) {
			// Activate DataTable
			var listType = tableView.data('listType');
			if (listType === 'nosync') {
				ExternalImportModule.buildTableForNonSynchronizableList(tableView);
			} else {
				ExternalImportModule.buildTableForSynchronizableList(tableView);
			}
		}
	});

	return ExternalImportModule;
});


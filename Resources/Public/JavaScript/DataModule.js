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
 * Module: TYPO3/CMS/ExternalImport/DataModule
 * External Import "Data Import" module JS
 */
define(['jquery',
		'TYPO3/CMS/Backend/Modal',
		'datatables',
		'TYPO3/CMS/Backend/jquery.clearable'
	   ], function($, Modal) {
	'use strict';

	var ExternalImportDataModule = {
		table: null
	};

	/**
	 * Activates DataTable on the synchronizable tables list view.
	 *
	 * @param tableView
	 */
	ExternalImportDataModule.buildTableForSynchronizableList = function(tableView) {
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
		ExternalImportDataModule.table = tableView.DataTable({
			dom: 't',
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		ExternalImportDataModule.table.order([3, 'asc']).draw();
		ExternalImportDataModule.initializeSearchField();
	};

	/**
	 * Activates DataTable on the non-synchronizable tables list view.
	 *
	 * @param tableView
	 */
	ExternalImportDataModule.buildTableForNonSynchronizableList = function(tableView) {
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
		ExternalImportDataModule.table = tableView.DataTable({
			dom: 't',
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		ExternalImportDataModule.table.order([1, 'asc']).draw();
		ExternalImportDataModule.initializeSearchField();
	};

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	ExternalImportDataModule.initializeSearchField = function() {
		var searchField = $('#tx_externalimport_search');
		// Restore existing filter
		searchField.val(ExternalImportDataModule.table.search());

		searchField
			.on('input', function() {
				ExternalImportDataModule.table.search($(this).val()).draw();
			})
			.clearable({
				onClear: function() {
					if (ExternalImportDataModule.table !== null) {
						ExternalImportDataModule.table.search('').draw();
					}
				}
			})
			.parents('form').on('submit', function() {
				return false;
			});
	};

	/**
	 * Checks if detail view tabs contain errors. If yes, tab is highlighted.
	 *
	 * @param detailView
	 */
	ExternalImportDataModule.raiseErrorsOnTab = function(detailView) {
		// Inspect each tab
		detailView.find('.tab-pane').each(function() {
			var tabPanel = $(this);
			// Count the number of alerts (of level "danger")
			var alerts = tabPanel.find('.alert-danger');
			if (alerts.length > 0) {
				// Using the tab's id, grab the corresponding anchor and add an error class to it
				var tabId = tabPanel.attr('id');
				detailView.find('a[href="#' + tabId + '"]').parent('li').addClass('has-validation-error');
			}
		});
	};

	/**
	 * Initialize this module
	 */
	$(function() {
		var tableView = $('#tx_externalimport_list');
		var detailView = $('#tx_externalimport_details');
		if (tableView.length) {
			// Activate DataTable
			var listType = tableView.data('listType');
			if (listType === 'nosync') {
				ExternalImportDataModule.buildTableForNonSynchronizableList(tableView);
			} else {
				ExternalImportDataModule.buildTableForSynchronizableList(tableView);
			}
		}
		if (detailView.length) {
			ExternalImportDataModule.raiseErrorsOnTab(detailView);
		}
	});

	return ExternalImportDataModule;
});


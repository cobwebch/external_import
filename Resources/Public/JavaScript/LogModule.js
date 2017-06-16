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
 * Module: TYPO3/CMS/ExternalImport/LogModule
 * External Import "Log" module JS
 */
define(['jquery',
		'TYPO3/CMS/Backend/Icons',
		'datatables',
		'TYPO3/CMS/Backend/jquery.clearable'
	   ], function($, Icons) {
	'use strict';

	var ExternalImportLogModule = {
		table: null,
		icons: {}
	};

	/**
	 * Preloads all the status icons.
	 */
	ExternalImportLogModule.loadStatusIcons = function () {
		Icons.getIcon('status-dialog-information', Icons.sizes.small, '', '').done(function(markup) {
			ExternalImportLogModule.icons[-2] = markup;
		});
		Icons.getIcon('status-dialog-notification', Icons.sizes.small, '', '').done(function(markup) {
			ExternalImportLogModule.icons[-1] = markup;
		});
		Icons.getIcon('status-dialog-ok', Icons.sizes.small, '', '').done(function(markup) {
			ExternalImportLogModule.icons[0] = markup;
		});
		Icons.getIcon('status-dialog-warning', Icons.sizes.small, '', '').done(function(markup) {
			ExternalImportLogModule.icons[1] = markup;
		});
		Icons.getIcon('status-dialog-error', Icons.sizes.small, '', '').done(function(markup) {
			ExternalImportLogModule.icons[2] = markup;
		});
	};

	/**
	 * Loads log data dynamically and initializes DataTables.
	 *
	 * @param tableView
	 */
	ExternalImportLogModule.buildDynamicTable = function(tableView) {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['tx_externalimport_loglist'],
			success: function (data, status, xhr) {
				ExternalImportLogModule.table = tableView.DataTable({
					data: data,
					dom: 'tp',
					// Default ordering is "date" column
					order: [
						[1, 'desc']
					],
					columnDefs: [
						{
							targets: 'log-status',
							data: 'status',
							render:  function(data, type, row, meta) {
								if (type === 'display') {
									return ExternalImportLogModule.icons[data];
								} else {
									return data;
								}
							}
						},
						{
							targets: 'log-date',
							data: 'date',
							render:  function(data, type, row, meta) {
								if (type === 'sort') {
									return data;
								} else {
									var lastModifiedDate = new Date(data * 1000);
									var day = lastModifiedDate.getDate();
									var month = lastModifiedDate.getMonth() + 1;
									// Make sure day and month are formatted with 2 digits
									if (day < 10) {
										day = '0' + day.toString();
									}
									if (month < 10) {
										month = '0' + month.toString();
									}
									var year = lastModifiedDate.getFullYear().toString().substr(2,2);
									var hours = lastModifiedDate.getHours();
									var minutes = lastModifiedDate.getMinutes();
									var seconds = lastModifiedDate.getSeconds();
									return day + '.' + month + '.' + year + ' ' + hours + ':' + minutes + ':' + seconds;
								}
							}
						},
						{
							targets: 'log-user',
							data: 'user'
						},
						{
							targets: 'log-configuration',
							data: 'configuration'
						},
						{
							targets: 'log-message',
							data: 'message'
						}
					],
					initComplete: function() {
						ExternalImportLogModule.initializeSearchField();

						// Hide the loading mask and show the table
						$('#tx_externalimport_loglist_loader').addClass('hidden');
						$('#tx_externalimport_loglist_wrapper').removeClass('hidden');
					}
				});
			}
		});
	};

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	ExternalImportLogModule.initializeSearchField = function() {
		$('#tx_externalimport_search')
			.on('input', function() {
				ExternalImportLogModule.table.search($(this).val()).draw();
			})
			.clearable({
				onClear: function() {
					if (ExternalImportLogModule.table !== null) {
						ExternalImportLogModule.table.search('').draw();
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
		var tableView = $('#tx_externalimport_loglist');
		ExternalImportLogModule.loadStatusIcons();
		ExternalImportLogModule.buildDynamicTable(tableView);
	});

	return ExternalImportLogModule;
});


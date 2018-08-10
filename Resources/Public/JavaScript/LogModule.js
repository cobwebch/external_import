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
		'moment',
		'datatables',
		'TYPO3/CMS/Backend/jquery.clearable'
	   ], function($, Icons, moment) {
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
	 * Initializes DataTables and loads data from the server-side.
	 *
	 * @param tableView
	 */
	ExternalImportLogModule.buildDynamicTable = function(tableView) {
		ExternalImportLogModule.table = tableView.DataTable({
			serverSide: true,
			processing: true,
			ajax: TYPO3.settings.ajaxUrls['tx_externalimport_loglist'],
			dom: 'tp',
			// Default ordering is "date" column
			order: [
				[1, 'desc']
			],
			// NOTE: the "name" attribute is used to define column names that match Extbase naming conventions
			// when column data is passed in the AJAX request and used server-side
			// (see \Cobweb\ExternalImport\Domain\Repository\LogRepository)
			columnDefs: [
				{
					targets: 'log-status',
					data: 'status',
					name: 'status',
					searchable: false,
					render:  function(data, type, row, meta) {
						if (type === 'display') {
							return ExternalImportLogModule.icons[data];
						} else {
							return data;
						}
					}
				},
				{
					targets: 'log-crdate',
					data: 'crdate',
					name: 'crdate',
					searchable: false,
					render:  function(data, type, row, meta) {
						if (type === 'sort') {
							return data;
						} else {
							var lastModifiedDate = moment.unix(data);
							return lastModifiedDate.format('DD.MM.YY HH:mm:ss');
						}
					}
				},
				{
					targets: 'log-username',
					data: 'username',
					name: 'cruserId.username'
				},
				{
					targets: 'log-configuration',
					data: 'configuration',
					name: 'configuration'
				},
				{
					targets: 'log-context',
					data: 'context',
					name: 'context',
					render:  function(data, type, row, meta) {
						var label = '';
						if (data) {
							switch (data) {
								case 'manual':
									label = TYPO3.lang.contextManual;
									break;
								case 'cli':
									label = TYPO3.lang.contextCli;
									break;
								case 'scheduler':
									label = TYPO3.lang.contextScheduler;
									break;
								case 'api':
									label = TYPO3.lang.contextApi;
									break;
								default:
									label = TYPO3.lang.contextOther;
							}
						}
						return label;
					}
				},
				{
					targets: 'log-message',
					data: 'message',
					name: 'message'
				},
				{
					targets: 'log-duration',
					data: 'duration',
					name: 'duration',
					render:  function(data, type, row, meta) {
						// For display, format the duration as a number of hours, minutes and seconds
						if (type === 'display') {
							var formattedTime = '';
							var hours = Math.floor(data / 3600);
							var residue = data % 3600;
							var minutes = Math.floor(residue / 60);
							var seconds = residue % 60;
							if (hours > 0) {
								formattedTime += hours + 'h ';
							}
							if (minutes > 0) {
								formattedTime += minutes + 'm ';
							}
							formattedTime += seconds + 's';
							console.log(data, hours, minutes, seconds);
							return formattedTime;
						} else {
							return data;
						}
					}
				}
			],
			initComplete: function() {
				ExternalImportLogModule.initializeSearchField();

				// Hide the loading mask and show the table
				$('#tx_externalimport_loglist_loader').addClass('hidden');
				$('#tx_externalimport_loglist_wrapper').removeClass('hidden');
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


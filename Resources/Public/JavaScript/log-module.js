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
 * External Import "Log" module JS
 */
import DocumentService from "@typo3/core/document-service.js";
import {DateTime} from "luxon";
import "@typo3/backend/input/clearable.js";
import Icons from "@typo3/backend/icons.js";

class ExternalImportLogModule {
	constructor() {
		this.tableView = 'tx_externalimport_loglist';
		this.searchField = 'tx_externalimport_search';
		this.icons = new Array();
		this.table = null;
		this.initialize();
	}

	async initialize() {
		DocumentService.ready().then((document) => {
			// Preload all icons, then finally build the dynamic table
			Icons.getIcon('actions-info-circle-alt').then((icon) => {
				this.icons[-2] = '<span class="log-icon-information">' + icon + '</span>';
				this.icons[-1] = '<span class="log-icon-notification">' + icon + '</span>';
				Icons.getIcon('actions-check-circle-alt').then((icon) => {
					this.icons[-0] = '<span class="log-icon-success">' + icon + '</span>';
					Icons.getIcon('actions-exclamation-circle-alt').then((icon) => {
						this.icons[1] = '<span class="log-icon-warning">' + icon + '</span>';
						this.icons[2] = '<span class="log-icon-danger">' + icon + '</span>';
						this.buildDynamicTable();
					});
				});
			});
		});
	}

	/**
	 * Initializes DataTables and loads data from the server-side.
	 */
	buildDynamicTable() {
		this.table = new DataTable('#' + this.tableView, {
			serverSide: true,
			processing: true,
			ajax: TYPO3.settings.ajaxUrls['tx_externalimport_loglist'],
			layout: {
				topStart: null,
				topEnd: null,
				bottomStart: 'paging',
				bottomEnd: null
			},
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
							return this.icons[data];
						} else {
							return data;
						}
					}.bind(this)
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
							const lastModifiedDate = DateTime.fromSeconds(data);
							return lastModifiedDate.toFormat('dd.LL.yy HH:mm:ss');
						}
					}
				},
				{
					targets: 'log-username',
					data: 'username',
					name: 'username'
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
						let label = '';
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
							let formattedTime = '';
							let hours = Math.floor(data / 3600);
							let residue = data % 3600;
							let minutes = Math.floor(residue / 60);
							let seconds = residue % 60;
							if (hours > 0) {
								formattedTime += hours + 'h ';
							}
							if (minutes > 0) {
								formattedTime += minutes + 'm ';
							}
							formattedTime += seconds + 's';
							return formattedTime;
						} else {
							return data;
						}
					}
				}
			],
			initComplete: function() {
				this.initializeSearchField();

				// Hide the loading mask and show the table
				document.getElementById('tx_externalimport_loglist_loader').classList.add('hidden');
				document.getElementById('tx_externalimport_loglist_wrapper').classList.remove('hidden');
			}.bind(this)
		});
	}

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	initializeSearchField() {
		let searchField = document.getElementById(this.searchField);

		searchField.addEventListener('input', function (event) {
			this.table.search(event.currentTarget.value).draw();
		}.bind(this));
		searchField.parentNode.parentNode.addEventListener('submit', function () {
			return false;
		});

		searchField.clearable({
			onClear: function () {
				if (this.table !== null) {
					this.table.search('').draw();
				}
			}.bind(this)
		});
	}
};

export default new ExternalImportLogModule();

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
 * External Import "Data Import" module JS
 */
import DocumentService from "@typo3/core/document-service.js";
import Modal from "@typo3/backend/modal.js";
import Notification from "@typo3/backend/notification.js";
import "@typo3/backend/input/clearable.js";

class ExternalImportDataModule {
	constructor() {
		this.tableView = 'tx_externalimport_list';
		this.detailView = 'tx_externalimport_details';
		this.searchField = 'tx_externalimport_search';
		this.table = null;
		this.initialize();
	}

	async initialize(){
		DocumentService.ready().then((document) => {
			if (document.getElementById(this.tableView) !== null) {
				// Activate DataTable
				const listType = document.getElementById(this.tableView).dataset.listType;
				if (listType === 'nosync') {
					this.buildTableForNonSynchronizableList();
				} else {
					this.buildTableForSynchronizableList();
					this.initializeActions();
				}
			}
			if (document.getElementById(this.detailView) !== null) {
				this.raiseErrorsOnTab();
			}
		});
	}

	/**
	 * Activates DataTable on the synchronizable tables list view.
	 */
	buildTableForSynchronizableList() {
		let columns = [
			// Icon
			{
				targets: 'column-icon',
				orderable: false
			},
			// Table
			{
				targets: 'column-table',
				orderable: true
			},
			// Description
			{
				targets: 'column-description',
				orderable: true
			},
			// Priority
			{
				targets: 'column-priority',
				orderable: true
			},
			// Group
			{
				targets: 'column-group',
				orderable: true
			},
			// Action icons
			{
				targets: 'column-actions',
				orderable: false
			},
			// Scheduler information and actions
			{
				targets: 'column-autosync',
				orderable: true
			},
			{
				targets: 'column-autosync-actions',
				orderable: false
			}
		];
		this.table = new DataTable('#' + this.tableView, {
			layout: {
				topStart: null,
				topEnd: null,
				bottomStart: null,
				bottomEnd: null
			},
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		this.table.order([3, 'asc']).draw();
		this.initializeSearchField();
	}

	/**
	 * Activates DataTable on the non-synchronizable tables list view.
	 */
	buildTableForNonSynchronizableList() {
		let columns = [
			// Icon
			{
				targets: 'column-icon',
				orderable: false
			},
			// Table
			{
				targets: 'column-table',
				orderable: true
			},
			// Description
			{
				targets: 'column-description',
				orderable: true
			},
			// Action icons
			{
				targets: 'column-actions',
				orderable: false
			}
		];
		this.table = new DataTable('#' + this.tableView, {
			layout: {
				topStart: null,
				topEnd: null,
				bottomStart: null,
				bottomEnd: null
			},
			serverSide: false,
			stateSave: true,
			info: false,
			paging: false,
			ordering: true,
			columnDefs: columns
		});
		this.table.order([1, 'asc']).draw();
		this.initializeSearchField();
	}

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	initializeSearchField() {
		let searchField = document.getElementById(this.searchField);
		// Restore existing filter
		searchField.value = this.table.search();

		searchField.addEventListener('input', function (event) {
			this.table.search(event.currentTarget.value).draw();
		}.bind(this));
		searchField.parentNode.parentNode.addEventListener('submit', function() {
			return false;
		});

		searchField.clearable({
		  onClear: function() {
			if (this.table !== null) {
			  this.table.search('').draw();
			}
		  }.bind(this)
		});
	}

	/**
	 * Checks if detail view tabs contain errors. If yes, tab is highlighted.
	 */
	raiseErrorsOnTab() {
		// Inspect each tab
		let tabPanes = document.getElementsByClassName('tab-pane');
		for (let i = 0; i < tabPanes.length; i++) {
			// Count the number of alerts (of level "danger")
			const alerts = tabPanes[i].querySelectorAll('.alert-danger').length;
			if (alerts > 0) {
				// Using the tab's id, grab the corresponding anchor and add an error class to it
				const tabId = tabPanes[i].id;
				document.querySelector('a[href="#' + tabId + '"]').parentNode.classList.add('has-validation-error');
			}
		}
	}

	/**
	 * Initializes actions on some buttons.
	 */
	initializeActions() {
		// Clicking the sync button should display a message warning not to leave the window
		// and activate icon animation
		let syncButtons = document.getElementsByClassName('sync-button');
		for (let i = 0; i < syncButtons.length; i++) {
			syncButtons[i].addEventListener('click', function (event) {
				Notification.info(TYPO3.lang.syncRunning, TYPO3.lang.doNotLeaveWindow, 0);
				event.currentTarget.querySelector('svg').classList.add('active');
			});
		}
	};
}

export default new ExternalImportDataModule();

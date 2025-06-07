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
import Icons from "@typo3/backend/icons.js";

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
			{
				name: 'icon',
				targets: 'column-icon',
				orderable: false
			},
			{
				name: 'table',
				targets: 'column-table',
				orderable: true
			},
			{
				name: 'description',
				targets: 'column-description',
				orderable: true
			},
			{
				name: 'priority',
				targets: 'column-priority',
				orderable: true
			},
			{
				name: 'group',
				targets: 'column-group',
				orderable: true
			},
			{
				name: 'actions',
				targets: 'column-actions',
				orderable: false
			},
			{
				name: 'scheduler-information',
				targets: 'column-autosync',
				orderable: true
			},
			{
				name: 'scheduler-actions',
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
			order: {
				name: 'priority',
				dir: 'asc'
			},
			columnDefs: columns
		});
		// React to the DataTables order event to update the sorting icons
		this.table.on('order', this.changeOrderIcons);
		this.table.draw();
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
		this.table.order([1, 'asc']);
		// React to the DataTables order event to update the sorting icons
		this.table.on('order', this.changeOrderIcons);
		this.table.draw();
		this.initializeSearchField();
	}

	/**
	 * Updates the sorting icons in reaction to DataTables' order event
	 */
	changeOrderIcons(event) {
		let columnIndex = event.dt.order()[0][0];
		let columnDirection = event.dt.order()[0][1];
		let defaultIconIdentifier = 'actions-sort-amount';
		let activeIconIdentifier = '';
		if (columnDirection === 'asc') {
		    activeIconIdentifier = 'actions-sort-amount-down';
		} else if (columnDirection === 'desc') {
		    activeIconIdentifier = 'actions-sort-amount-up';
		}

		// This function gives a global context so that variables defined above can be used within promise returns
		const updateSortingIcons = (defaultIcon, activeIcon, activeColumnIndex) => {
		    const columns = document.getElementById('tx_externalimport_list').getElementsByTagName('th');
			// Reset all columns to default icon
		    Icons.getIcon(defaultIcon, Icons.sizes.small).then(iconMarkup => {
		        for (let i = 0; i < columns.length; i++) {
		            let elements = columns[i].getElementsByClassName('sorting-icon');
		            if (elements.length > 0) {
		                elements[0].innerHTML = iconMarkup;
		            }
		        }
		        // Update active column's icon if an active identifier is provided
		        if (activeIcon) {
		            const activatedColumn = columns[activeColumnIndex];
		            Icons.getIcon(activeIcon, Icons.sizes.small).then(activeIconMarkup => {
		                activatedColumn.getElementsByClassName('sorting-icon')[0].innerHTML = activeIconMarkup;
		            });
		        }
		    });
		};
		updateSortingIcons(defaultIconIdentifier, activeIconIdentifier, columnIndex);
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
				event.currentTarget.querySelector('span.t3js-icon').classList.add('icon-spin');
			});
		}
	};
}

export default new ExternalImportDataModule();

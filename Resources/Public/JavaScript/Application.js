/**
 * Full ExtJS application for external_import's BE module
 *
 * $Id$
 */
Ext.namespace('TYPO3.ExternalImport');

	// Define general loading indicator
TYPO3.ExternalImport.loadingIndicator = '<div class="loading-indicator">' + TYPO3.lang.action_loading + '</div>';

	// Define the data in the (external import) configuration store
TYPO3.ExternalImport.ConfigurationStore = new Ext.data.DirectStore({
	storeId: 'configuration',
	idProperty: 'id',
	root : 'data',
	fields: [
		{name: 'id'},
		{name: 'table'},
		{name: 'tableName'},
		{name: 'icon'},
		{name: 'index'},
		{name: 'priority'},
		{name: 'description'},
		{name: 'writeAccess'}
	],
	sortInfo: {
		field: 'priority',
		direction: 'ASC'
	}
});

	// Define the grid where all configurations are displayed
TYPO3.ExternalImport.ConfigurationGrid = new Ext.grid.GridPanel({
	store: TYPO3.ExternalImport.ConfigurationStore,
	cls: 'configurationGrid',
	columns: [
		{
			id: 'icon',
			header: '',
			dataIndex: 'icon',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true
		},
		{
			id: 'table',
			header: TYPO3.lang['table'],
			dataIndex: 'tableName',
			width: 200,
			sortable: true
		},
		{
			id: 'description',
			header: TYPO3.lang['description'],
			dataIndex: 'description',
			width: 200,
			sortable: true
		},
		{
			id: 'priority',
			header: TYPO3.lang['priority'],
			dataIndex: 'priority',
			width: 40,
			sortable: true
		},
			// Information button
		{
			xtype: 'actioncolumn',
			id: 'info-button',
			header: '',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true,
			items: [
				{
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info',
					tooltip: TYPO3.lang['view_details'],
					handler: function(grid, rowIndex, colIndex) {
						var record = TYPO3.ExternalImport.ConfigurationStore.getAt(rowIndex);
						TYPO3.ExternalImport.showExternalImportInformation(record.json.table, record.json.index);
					}
				}
			]
		},
			// Synchronization button
		{
			xtype: 'actioncolumn',
			id: 'sync-button',
			header: '',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true,
			hidden: !TYPO3.settings.external_import.hasScheduler,
			items: [
				{
					tooltip: TYPO3.lang['synchronise'],
					handler: function(grid, rowIndex, colIndex, item) {
						var record = TYPO3.ExternalImport.ConfigurationStore.getAt(rowIndex);
							// Synchronize only if user has write access to the table
						if (record.json.writeAccess) {
								// Get the grid cell that was clicked
							var cell = Ext.get(grid.getView().getCell(rowIndex, colIndex));
								// Hide the action icon and replace it with a loading indicator
							var image = cell.query('img');
							Ext.fly(image[0]).hide();
							cell.addClass('loading-indicator');
								// Start the synchronization of the selected configuration
							TYPO3.ExternalImport.ExtDirect.launchSynchronization(record.json.table, record.json.index, function(response) {
									// If the response contains a general error, display that
								if (response.error) {
									TYPO3.Flashmessage.display(
										TYPO3.Severity.error,
										TYPO3.lang['failed'],
										response.error[0],
										5
									);

									// Otherwise display the returned messages
								} else {
									TYPO3.ExternalImport.renderMessages(TYPO3.Severity.error, response[2]);
									TYPO3.ExternalImport.renderMessages(TYPO3.Severity.warning, response[1]);
									TYPO3.ExternalImport.renderMessages(TYPO3.Severity.ok, response[0]);
								}
									// Remove the loading indicator and restore the action icon
								cell.removeClass('loading-indicator');
								Ext.fly(image[0]).show();
							});
						}
					},
					getClass: function(v, meta, record) {
							// If the user has no write access, don't display the sync icon
						if (!record.json.writeAccess) {
							return 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';
						} else {
							return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-refresh';
						}
					}
				}
			]
		}
	],
	viewConfig: {
		forceFit: true
	},
	enableColumnHide: false,
	plugins: [new Ext.ux.plugins.FitToParent()]
});

/**
 * This function reacts when a click event happens on an "external information" button.
 *
 * It opens a window with tabbed content displaying all the related external import information
 *
 * @param table The name of the table for which we want the details
 * @param index The index of the external configuration
 */
TYPO3.ExternalImport.showExternalImportInformation = function(table, index) {
	TYPO3.Windows.getWindow({
		id: 'external_import_details_' + table + '_' + index,
		title: TYPO3.lang['external_information'],
		layout: 'fit',
		width: Ext.getBody().getViewSize().width * 0.8,
		items: [
			new Ext.TabPanel({
				activeTab: 0,
				plain: true,
				items: [
						// First tab displays the general (ctrl) information
					{
						title: TYPO3.lang['general_information'],
						height: Ext.getBody().getViewSize().height * 0.8,
						autoScroll: true,
						cls: 'informationTab',
						html: TYPO3.ExternalImport.loadingIndicator,
						listeners: {
								// Get the information and display it inside the tab
							activate: function(panel) {
								panel.update(TYPO3.ExternalImport.loadingIndicator);
								TYPO3.ExternalImport.ExtDirect.getGeneralConfiguration(table, index, function(response) {
									panel.update(response, true);
								});
							}
						}
					},
						// Second tab display the columns-related information
					{
						title: TYPO3.lang['columns_mapping'],
						autoScroll: true,
						cls: 'informationTab',
						height: Ext.getBody().getViewSize().height * 0.8,
						html: TYPO3.ExternalImport.loadingIndicator,
						listeners: {
								// Get the information and display it inside the tab
							activate: function(panel) {
								panel.update(TYPO3.ExternalImport.loadingIndicator);
								TYPO3.ExternalImport.ExtDirect.getColumnsConfiguration(table, index, function(response) {
									panel.update(response, true);
								});
							}
						}
					}
				]
			})
		]
	}).show();
};

/**
 * Renders an list of message of a given severity
 *
 * @param severity Level of severity
 * @param messages Array of messages
 */
TYPO3.ExternalImport.renderMessages = function(severity, messages) {
	if (messages.length > 0) {
		for (var i = 0; i < messages.length; i++) {
			TYPO3.Flashmessage.display(severity, '', messages[i], 5);
		}
	}
};

Ext.onReady(function() {
		// Define the ExtDirect method to call for loading data into the configuration store
	TYPO3.ExternalImport.ConfigurationStore.proxy = new Ext.data.DirectProxy({
		directFn: TYPO3.ExternalImport.ExtDirect.getConfigurations
	});
		// Fire the loading of the data
	TYPO3.ExternalImport.ConfigurationStore.load({
		params: {isSynchronizable : true}
	});

		// Initialize tooltips
	Ext.QuickTips.init();

		// Set the main container and target it at the proper tag
	new Ext.Container({
		renderTo: 'external-import-grid',
		layout: 'fit',
		items: [TYPO3.ExternalImport.ConfigurationGrid]
	});
});

/**
 * This function turns on or off the display of a synchronization form
 * It also changes the icon displayed accordingly
 *
 * @param theID ID of the element to act on
 * @param theAction Type of action
function toggleSyncForm(theID, theAction) {
	var theLink = Ext.get(theID + '_container');
	var theElement = Ext.get(theID + '_wrapper');
	var theIcon;
	if (theElement.isDisplayed()) {
		theElement.setDisplayed(false);
		if (theAction === 'add') {
			theIcon = LOCALAPP.imageExpand_add;
		} else {
			theIcon = LOCALAPP.imageExpand_edit;
		}
		theLink.update(theIcon);
	} else {
		theElement.setDisplayed(true);
		if (theAction === 'add') {
			theIcon = LOCALAPP.imageCollapse_add;
		} else {
			theIcon = LOCALAPP.imageCollapse_edit;
		}
		theLink.update(theIcon);
	}
}
*/

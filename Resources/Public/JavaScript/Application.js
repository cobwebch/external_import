/**
 * Full ExtJS application for external_import's BE module
 *
 * $Id$
 */
Ext.namespace('TYPO3.ExternalImport');

	// Define general loading indicator
TYPO3.ExternalImport.loadingIndicator = '<div class="loading-indicator">' + TYPO3.lang.action_loading + '</div>';

	// Initialize with default values
TYPO3.ExternalImport.fullSynchronizationSettings = {
	id: 0,
	table: 'all',
	index: 0,
		// User is considered to have "write access" to the full sync only if he has write access to every table with sync
	writeAccess: (TYPO3.settings.external_import.globalWriteAccess == 'all')
};

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
		{name: 'writeAccess'},
		{name: 'automated'},
		{name: 'task'}
	],
	sortInfo: {
		field: 'priority',
		direction: 'ASC'
	}
});

	// Template for the rendering of the automated synchronization column
TYPO3.ExternalImport.AutosyncColumnTemplate = new Ext.XTemplate(
	'<div class="longCellWrap">',
		'<tpl if="automated == 0">',
			'<p>' + TYPO3.lang['no_autosync'] + '</p>',
		'</tpl>',
		'<tpl if="automated == 1">',
			'<p>{[String.format(TYPO3.lang["next_autosync"], values.task.nextexecution, values.task.frequency)]}</p>',
		'</tpl>',
	'</div>'
);

	// Define the grid where all configurations are displayed
TYPO3.ExternalImport.ConfigurationGrid = new Ext.grid.GridPanel({
	store: TYPO3.ExternalImport.ConfigurationStore,
	cls: 'configurationGrid',
	stripeRows: true,
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
			width: 150,
			sortable: true
		},
		{
			xtype: 'templatecolumn',
			id: 'description',
			header: TYPO3.lang['description'],
			dataIndex: 'description',
			width: 150,
			sortable: true,
			tpl: '[{values.index}] {values.description}'
		},
			// Hide if the view is not of the synchronizable tables
		{
			id: 'priority',
			header: TYPO3.lang['priority'],
			dataIndex: 'priority',
			width: 40,
			sortable: true,
			hidden: TYPO3.settings.external_import.view != 'sync'
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
			// Hide if the view is not of the synchronizable tables or the use has no write access to any table
		{
			xtype: 'actioncolumn',
			id: 'sync-button',
			header: '',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true,
			hidden: TYPO3.settings.external_import.view != 'sync' || TYPO3.settings.external_import.globalWriteAccess == 'none',
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
							Ext.Ajax.timeout = TYPO3.settings.external_import.timelimit;
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
		},
			// Information about automated synchronization
			// Hide if the Scheduler is not installed or if the view is not of the synchronizable tables or if user has not write access to any table
		{
			xtype: 'templatecolumn',
			id: 'automated',
			header: TYPO3.lang['autosync'],
			width: 100,
			sortable: true,
			hidden: TYPO3.settings.external_import.view != 'sync' || !TYPO3.settings.external_import.hasScheduler || TYPO3.settings.external_import.globalWriteAccess == 'none',
			tpl: TYPO3.ExternalImport.AutosyncColumnTemplate
		},
			// Button to call up the automated synchronization settings form
			// Hide if the Scheduler is not installed or if the view is not of the synchronizable tables or if user has not write access to any table
		{
			xtype: 'actioncolumn',
			id: 'scheduler-button',
			header: '',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true,
			hidden: TYPO3.settings.external_import.view != 'sync' || !TYPO3.settings.external_import.hasScheduler || TYPO3.settings.external_import.globalWriteAccess == 'none',
			items: [
				{
					tooltip: TYPO3.lang['change_sync'],
					handler: function(grid, rowIndex, colIndex) {
						var record = TYPO3.ExternalImport.ConfigurationStore.getAt(rowIndex);
							// Display form only if user has write access to the table
						if (record.json.writeAccess) {
							TYPO3.ExternalImport.showAutoSyncForm(record.json);
						}
					},
					getClass: function(v, meta, record) {
						if (record.json.writeAccess) {
								// If the automation is already active, display an edit icon, otherwise display a new icon
								// Display nothing if the user doesn't have write access to the table
							if (record.json.automated) {
								return 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open';
							} else {
								return 't3-icon t3-icon-actions t3-icon-actions-page t3-icon-page-new';
							}
						} else {
							return 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';
						}
					}
				}
			]
		},
			// Button to delete the automated synchronization settings
			// Hide if the Scheduler is not installed or if the view is not of the synchronizable tables or if user has not write access to any table
		{
			xtype: 'actioncolumn',
			id: 'delete-button',
			header: '',
			width: 30,
			fixed: true,
			sortable: false,
			menuDisabled: true,
			hidden: TYPO3.settings.external_import.view != 'sync' || !TYPO3.settings.external_import.hasScheduler || TYPO3.settings.external_import.globalWriteAccess == 'none',
			items: [
				{
					tooltip: TYPO3.lang['delete_sync'],
					handler: function(grid, rowIndex, colIndex) {
						var record = TYPO3.ExternalImport.ConfigurationStore.getAt(rowIndex);
						TYPO3.ExternalImport.removeAutoSync(record.json);
					},
					getClass: function(v, meta, record) {
							// If the automation is already active and user has write access to the table,
							// display a delete icon, otherwise an empty space
						if (record.json.automated && record.json.writeAccess) {
							return 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-delete';
						} else {
							return 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';
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
		// Make the grid use all the possible space available
		// @see t3lib/js/extjs/ux/Ext.ux.FitToParent.js
	plugins: [new Ext.ux.plugins.FitToParent()]
});

	// Template for the rendering of the automated synchronization column
TYPO3.ExternalImport.AutosyncBoxTemplate = new Ext.XTemplate(
	'<tpl for=".">',
		'<tpl if="id == 0">',
			'<p>' + TYPO3.lang['no_full_autosync'] + '</p>',
		'</tpl>',
		'<tpl if="id != 0">',
			'<p>{[String.format(TYPO3.lang["next_full_autosync"], values.task.nextexecution, values.task.frequency)]}</p>',
		'</tpl>',
	'</tpl>'
);

	// Container to display information (and action buttons) about the "full" synchronization, i.e.
	// the automation of the synchronization of all buttons
TYPO3.ExternalImport.FullSyncPanel = new Ext.Container({
	id: 'external_import_full_sync_panel',
	items: [
			// Display information about the full synchronization
		{
			id: 'external_import_full_sync_info',
			xtype: 'box',
			tpl: TYPO3.ExternalImport.AutosyncBoxTemplate,
			data: TYPO3.ExternalImport.fullSynchronizationSettings
		},
			// Display buttons for adding, modifying and removing the full synchronization
		{
			xtype: 'container',
			layout: 'column',
			items: [
				{
					id: 'external_import_full_sync_activate',
					xtype: 'button',
					text: '<span class="t3-icon t3-icon-actions t3-icon-actions-page t3-icon-page-new"></span>' + TYPO3.lang['activate'],
					hidden: false,
					handler: function(button, event) {
						TYPO3.ExternalImport.showAutoSyncForm(TYPO3.ExternalImport.fullSynchronizationSettings);
					}
				},
				{
					id: 'external_import_full_sync_modify',
					xtype: 'button',
					text: '<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open"></span>' + TYPO3.lang['modify'],
					hidden: true,
					handler: function(button, event) {
						TYPO3.ExternalImport.showAutoSyncForm(TYPO3.ExternalImport.fullSynchronizationSettings);
					}
				},
				{
					id: 'external_import_full_sync_deactivate',
					xtype: 'button',
					text: '<span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-delete"></span>' + TYPO3.lang['deactivate'],
					hidden: true,
					handler: function(button, event) {
						TYPO3.ExternalImport.removeAutoSync(TYPO3.ExternalImport.fullSynchronizationSettings);
					}
				}
			]
		}
	],
	hidden: true
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
 * Prepares and displays a form for defining (adding or editing) the automatic
 * synchronization parameters for a given configuration
 *
 * @param configuration
 */
TYPO3.ExternalImport.showAutoSyncForm = function(configuration) {
		// Act only if use has write access
	if (configuration.writeAccess) {
		new TYPO3.Components.Window({
			id: 'external_import_autosync_' + configuration.table + '_' + configuration.index,
			title: TYPO3.lang['sync_settings'],
			width: Ext.getBody().getViewSize().width * 0.5,
			items: [
				new Ext.form.FormPanel({
						// Define which ExtDirect method must be called upon submit
					api: {
						submit: TYPO3.ExternalImport.ExtDirect.saveSchedulerTask
					},
						// List of form fields
					items: [
							// If the configuration contains a task, we are editing an existing task
							// If not, we are adding a new one. All fields are initialized accordingly
						{
							xtype: 'hidden',
							name: 'uid',
							value: (configuration.task) ? configuration.task.uid : 0
						},
						{
							xtype: 'hidden',
							name: 'table',
							value: configuration.table
						},
						{
							xtype: 'hidden',
							name: 'index',
							value: configuration.index
						},
						{
							fieldLabel: TYPO3.lang['frequency'],
							name: 'frequency',
							xtype: 'textfield',
							value: (configuration.task) ? configuration.task.frequency : ''
						},
							// Simple box with help text
							// This is a workaround for the impossibility to have something like bubble help in ExtJS forms
						{
							xtype: 'box',
							autoEl: {
								tag: 'p',
								cls: 'help-text',
								html: TYPO3.lang['frequency_help']
							}
						},
							// Composite field to display date and time side by side
						{
							fieldLabel: TYPO3.lang['start_date'],
							xtype: 'compositefield',
							boxMinHeight: 25,
							items: [
								{
									name: 'start_date',
									xtype: 'datefield',
									format: TYPO3.settings.external_import.dateFormat,
									value: (configuration.task) ? configuration.task.start_date : ''
								},
								{
									name: 'start_time',
									xtype: 'timefield',
									width: 70,
									format: TYPO3.settings.external_import.timeFormat,
									value: (configuration.task) ? configuration.task.start_time : '',
									increment: 15
								}
							]
						},
							// Help text
						{
							xtype: 'box',
							autoEl: {
								tag: 'p',
								cls: 'help-text',
								html: TYPO3.lang['start_date_help']
							}
						}
					],
						// List of form buttons
					buttons: [
							// The save button triggers the submission of the form
						{
							text: TYPO3.lang['save'],
							handler: function(button, event) {
									// Find the parent form's BasicForm and submit it
								button.findParentByType('form').getForm().submit({
									success: function(form, action) {
										TYPO3.ExternalImport.renderMessages(TYPO3.Severity.ok, [TYPO3.lang['autosync_saved']]);
										button.findParentByType('window').close();
											// Update the display
											// For the full sync, update the various parts
										if (configuration.table == 'all') {
												// Read the full sync config again
											TYPO3.ExternalImport.ExtDirect.getFullSynchronizationTask(function(response) {
												if (response.id) {
													response.writeAccess = (TYPO3.settings.external_import.globalWriteAccess == 'all');
													TYPO3.ExternalImport.fullSynchronizationSettings = response;
														// Update the full sync info and show/hide the appropriate buttons
													Ext.getCmp('external_import_full_sync_info').update(response);
													Ext.getCmp('external_import_full_sync_activate').hide();
													Ext.getCmp('external_import_full_sync_modify').show();
													Ext.getCmp('external_import_full_sync_deactivate').show();
												}
											});

											// For a specific configuration, it's easier to reload the data and update the full grid
										} else {
												// Get the grid data to reload
												// (when a new automated sync is defined, several cells in the row need to be updated,
												// it is easier to refresh the data than to try and update all the relevant cells)
											TYPO3.ExternalImport.ConfigurationStore.load({
												params: {synchronizable : true}
											});
										}
									},
									failure: function(form, action) {
										TYPO3.ExternalImport.renderMessages(TYPO3.Severity.error, [action.result.errors['scheduler']]);
									}
								});
							}
						},
							// On cancel, just dismiss the window
						{
							text: TYPO3.lang['cancel'],
							handler: function(button, event) {
								button.findParentByType('window').close();
							}
						}
					]
				})
			]
		}).show();
	}
};

/**
 * Display a confirmation dialog about deleting the automatic synchronization settings
 * for a given configuration
 *
 * @param configuration
 */
TYPO3.ExternalImport.removeAutoSync = function(configuration) {
		// Act only if use has write access
	if (configuration.writeAccess && configuration.task) {
			// Display a confirmation dialog
		TYPO3.Dialog.QuestionDialog({
			msg: TYPO3.lang['delete_sync_confirm'],
			fn: function(button) {
					// If the "yes" button was pressed, delete the Scheduler task
				if (button == 'yes') {
					TYPO3.ExternalImport.ExtDirect.deleteSchedulerTask(configuration.task['uid'], function(response) {
							// Display a message depending on response status
						if (response.success) {
							TYPO3.ExternalImport.renderMessages(TYPO3.Severity.ok, [TYPO3.lang['delete_done']]);
								// Update the display
								// For the full sync, update the various parts
							if (configuration.table == 'all') {
									// Reset the full sync settings to default value
								TYPO3.ExternalImport.fullSynchronizationSettings = {
									id: 0,
									table: 'all',
									index: 0,
									writeAccess: (TYPO3.settings.external_import.globalWriteAccess == 'all')
								};
									// Update the full sync info and show/hide the appropriate buttons
								Ext.getCmp('external_import_full_sync_info').update(TYPO3.ExternalImport.fullSynchronizationSettings);
								Ext.getCmp('external_import_full_sync_activate').show();
								Ext.getCmp('external_import_full_sync_modify').hide();
								Ext.getCmp('external_import_full_sync_deactivate').hide();

								// For a specific configuration, it's easier to reload the data and update the full grid
							} else {
								TYPO3.ExternalImport.ConfigurationStore.load({
									params: {synchronizable : true}
								});
							}
						} else {
							TYPO3.ExternalImport.renderMessages(TYPO3.Severity.error, [TYPO3.lang['delete_failed']]);
						}
					});
				}
			}
		});
	}
};

/**
 * Renders a list of messages of a given severity
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
		params: {
			synchronizable: (TYPO3.settings.external_import.view == 'sync')
		},
		callback: function(records, options, success) {
				// If the call was successful, but the result set is empty, issue a warning
			if (success) {
				if (records.length == 0) {
					TYPO3.Flashmessage.display(TYPO3.Severity.warning, '', TYPO3.lang['no_configurations_warning'], 5);
				}

				// If the call was unsuccessful, issue an error
			} else {
				TYPO3.Flashmessage.display(TYPO3.Severity.error, '', TYPO3.lang['configuration_loading_error'], 5);
			}
		}
	});

		// Initialize tooltips
	Ext.QuickTips.init();

		// Set the container for the full synchronization info and target it at the proper tag
	if (Ext.fly('external-import-full-sync')) {
		new Ext.Container({
			renderTo: 'external-import-full-sync',
			layout: 'fit',
			items: [TYPO3.ExternalImport.FullSyncPanel]
		});
			// ExtDirect call to get the information about the full sync task, if any
		TYPO3.ExternalImport.ExtDirect.getFullSynchronizationTask(function(response) {
			if (response.id) {
				response.writeAccess = (TYPO3.settings.external_import.globalWriteAccess == 'all');
				TYPO3.ExternalImport.fullSynchronizationSettings = response;
				Ext.getCmp('external_import_full_sync_info').update(response);
				Ext.getCmp('external_import_full_sync_activate').hide();
				Ext.getCmp('external_import_full_sync_modify').show();
				Ext.getCmp('external_import_full_sync_deactivate').show();
			}
			Ext.getCmp('external_import_full_sync_panel').show();
		});
	}

		// Set the main container and target it at the proper tag
	new Ext.Container({
		renderTo: 'external-import-grid',
		layout: 'fit',
		items: [TYPO3.ExternalImport.ConfigurationGrid]
	});
});

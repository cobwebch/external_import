/**
 * Base file for External import's JavaScript
 *
 * $Id$
 */
Ext.namespace('TYPO3.ExternalImport');

/**
 * This function reacts when a click event happens on an "external information" button
 * It fetches some hidden HTML content and displays it in a MessageBox
 *
 * @param e EventObject
 */
TYPO3.ExternalImport.showExternalImportInformation = function(e) {
	var content = Ext.get(e.getTarget().id + '-content');
	TYPO3.Windows.getWindow({
		title: TYPO3.lang.external_information,
		html: content.dom.innerHTML
	}).show();
}

Ext.onReady(function() {
		// Add listener to "external information" icons
	Ext.select('img.external-information').on('click', TYPO3.ExternalImport.showExternalImportInformation);
});

/**
 * This function responds to calls for launching the synchronisation of a table
 * This is done using AJAX and displaying the response received
 *
 * @param theID ID of the item being handled (this is a simple numeric index)
 * @param theTable Table to synchronise
 * @param theIndex Index of the external configuration to use for the given table
 */
function syncTable(theID, theTable, theIndex) {
	Ext.fly('result' + theID).update(LOCALAPP.running);
	Ext.fly('container' + theID).update(LOCALAPP.syncRunningIcon);
	Ext.Ajax.request({
		url: LOCALAPP.ajaxUrl,
		method: 'post',
		timeout: 300000,
		params: {
			ajaxID: 'externalimport::synchronizeExternalTable',
			table: theTable,
			index: theIndex
		},
		success: function(result){
			var responseObject = Ext.util.JSON.decode(result.responseText);
			Ext.fly('result' + theID).update(responseObject.content);
			Ext.fly('container' + theID).update(LOCALAPP.syncStoppedIcon);
		},
		failure: function(result) {
			Ext.fly('result' + theID).update('Failed!');
			Ext.fly('container' + theID).update(LOCALAPP.syncStoppedIcon);
		}
	});
}

/**
 * This function turns on or off the display of a synchronization form
 * It also changes the icon displayed accordingly
 *
 * @param theID ID of the element to act on
 * @param theAction Type of action
 */
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

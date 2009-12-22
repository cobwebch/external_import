/* 
 * JavaScript functions used in the external_import BE module
 *
 * $Id$
 */

/**
 * @param	theID	ID of the element to act on
 * @param	action	Type of action
 */
function toggleSyncForm(theID, action) {
	var theLink = $(theID + '_link');
	var theElement = $(theID + '_wrapper');
	var theIcon;
	var theLabel;
	if (theElement.visible()) {
		theElement.hide();
		if (action === 'add') {
			theIcon = LOCALAPP.imageExpand_add;
			theLabel  = LOCALAPP.showSyncForm_add;
		} else {
			theIcon = LOCALAPP.imageExpand_edit;
			theLabel  = LOCALAPP.showSyncForm_edit;
		}
		theLink.update(theIcon);
		theLink.title = theLabel;
	} else {
		theElement.show();
		if (action === 'add') {
			theIcon = LOCALAPP.imageCollapse_add;
		} else {
			theIcon = LOCALAPP.imageCollapse_edit;
		}
		theLink.update(theIcon);
		theLink.title = LOCALAPP.hideSyncForm;
	}
}

/**
 * This function turns on or off the display of an element
 *
 * @param	theID	ID of the element to toggle
 */
function toggleElement(theID) {
	var theElement = $(theID);
	if (theElement.visible()) {
		theElement.hide();
	} else {
		theElement.show();
	}
}

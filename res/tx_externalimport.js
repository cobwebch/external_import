/* 
 * JavaScript functions used in the external_import BE module
 *
 * $Id$
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
	}
	else {
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


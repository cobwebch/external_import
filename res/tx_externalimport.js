/* 
 * JavaScript functions used in the external_import BE module
 *
 * $Id$
 */
function toggleSyncForm(theID) {
	var theLink = $(theID + '_link');
	var theElement = $(theID + '_wrapper');
	if (theElement.visible()) {
		theElement.hide();
		theLink.update(LOCALAPP.imageExpand);
		theLink.title = LOCALAPP.showSyncForm;
	}
	else {
		theElement.show();
		theLink.update(LOCALAPP.imageCollapse);
		theLink.title = LOCALAPP.hideSyncForm;
	}
}


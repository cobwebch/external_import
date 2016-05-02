<?php
namespace Cobweb\ExternalImport\ViewHelpers\Be;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * This class extends the base BE container View Helper to add specific initializations
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class ContainerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\ContainerViewHelper
{

    /**
     * Render start page with template.php and pageTitle
     *
     * @param string $pageTitle title tag of the module. Not required by default, as BE modules are shown in a frame
     * @param boolean $enableJumpToUrl If TRUE, includes "jumpTpUrl" javascript function required by ActionMenu. Defaults to TRUE
     * @param boolean $enableClickMenu If TRUE, loads clickmenu.js required by BE context menus. Defaults to TRUE
     * @param boolean $loadPrototype specifies whether to load prototype library. Defaults to TRUE
     * @param boolean $loadScriptaculous specifies whether to load scriptaculous libraries. Defaults to FALSE
     * @param string $scriptaculousModule additional modules for scriptaculous
     * @param boolean $loadExtJs specifies whether to load ExtJS library. Defaults to FALSE
     * @param boolean $loadExtJsTheme whether to load ExtJS "grey" theme. Defaults to FALSE
     * @param string $extJsAdapter load alternative adapter (ext-base is default adapter)
     * @param boolean $enableExtJsDebug if TRUE, debug version of ExtJS is loaded. Use this for development only
     * @param string $addCssFile Custom CSS file to be loaded
     * @param string $addJsFile Custom JavaScript file to be loaded
     * @param string $globalWriteAccess Whether uses has full access ("all"), "partial" access or none (to sync tables)
     * @param string $view Name of the current view ("sync" or "nosync")
     * @return string
     * @see template
     * @see t3lib_PageRenderer
     */
    public function render(
            $pageTitle = '',
            $enableJumpToUrl = true,
            $enableClickMenu = true,
            $loadPrototype = true,
            $loadScriptaculous = false,
            $scriptaculousModule = '',
            $loadExtJs = false,
            $loadExtJsTheme = true,
            $extJsAdapter = '',
            $enableExtJsDebug = false,
            $addCssFile = null,
            $addJsFile = null,
            $globalWriteAccess = 'none',
            $view = 'sync'
    ) {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['external_import']);

        $doc = $this->getDocInstance();
        $pageRenderer = $doc->getPageRenderer();
        $isTypo3Version62OrMore = version_compare(TYPO3_branch, '6.2', '>=');

        // Load ExtDirect
        $pageRenderer->addExtDirectCode(array('TYPO3.ExternalImport'));
        // Load the FitToParent ExtJS plugin
        if ($isTypo3Version62OrMore) {
            $uxPath = $doc->backPath . '../typo3/js/extjs/ux/';
        } else {
            $uxPath = $doc->backPath . '../t3lib/js/extjs/ux/';
        }
        $pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
        // Pass some settings to the JavaScript application
        // First calculate the time limit (which is multiplied by 1000, because JS uses milliseconds)
        // Defaults to 30 seconds
        $timeLimitConfiguration = intval($extensionConfiguration['timelimit']);
        // If the time limit is 0, duration is supposed to be unlimited. Set 1 day as arbitrary value.
        if ($timeLimitConfiguration === 0) {
            $timeLimit = 86400 * 1000;
        } else {
            $timeLimit = ($timeLimitConfiguration > 0) ? $timeLimitConfiguration * 1000 : 30000;
        }
        $pageRenderer->addInlineSettingArray(
                'external_import',
                array(
                        'timelimit' => $timeLimit,
                        'hasScheduler' => ExtensionManagementUtility::isLoaded('scheduler', false),
                        'globalWriteAccess' => $globalWriteAccess,
                        'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
                        'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
                        'view' => $view
                )
        );
        // Load JS-powered flash messages library
        if ($isTypo3Version62OrMore) {
            $notificationLibraryPath = ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/JavaScript/notifications.js';
        } else {
            $notificationLibraryPath = $doc->backPath . '../t3lib/js/extjs/notifications.js';
        }
        $pageRenderer->addJsFile($notificationLibraryPath, 'text/javascript', false);
        // Load the specific language file
        $pageRenderer->addInlineLanguageLabelFile('EXT:external_import/Resources/Private/Language/locallang.xml');
        $pageRenderer->addInlineLanguageLabelFile('EXT:lang/locallang_common.xml');

        $output = parent::render(
                $pageTitle,
                $enableJumpToUrl,
                $enableClickMenu,
                $loadPrototype,
                $loadScriptaculous,
                $scriptaculousModule,
                $loadExtJs,
                $loadExtJsTheme,
                $extJsAdapter,
                $enableExtJsDebug,
                $addCssFile,
                $addJsFile
        );
        return $output;
    }
}

<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "external_import".
 *
 * Auto generated 01-03-2017 16:24
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'External Data Import',
  'description' => 'Tool for importing data from external sources into the TYPO3 CMS database, using an extended TCA syntax. Provides a BE module, a Scheduler task and an API.',
  'category' => 'module',
  'author' => 'Francois Suter (Cobweb)',
  'author_email' => 'typo3@cobweb.ch',
  'state' => 'stable',
  'uploadfolder' => 0,
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'author_company' => '',
  'version' => '3.0.4',
  'constraints' => 
  array (
    'depends' => 
    array (
      'svconnector' => '3.0.0-0.0.0',
      'typo3' => '7.6.0-7.99.99',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
      'externalimport_tut' => '2.0.1-0.0.0',
      'scheduler' => '',
    ),
  ),
  '_md5_values_when_last_written' => 'a:110:{s:9:"ChangeLog";s:4:"a508";s:11:"LICENSE.txt";s:4:"6404";s:10:"README.txt";s:4:"3fcc";s:13:"composer.json";s:4:"91bc";s:21:"ext_conf_template.txt";s:4:"cbe2";s:12:"ext_icon.png";s:4:"5db9";s:17:"ext_localconf.php";s:4:"456d";s:14:"ext_tables.php";s:4:"c889";s:14:"ext_tables.sql";s:4:"8127";s:32:"Classes/DataHandlerInterface.php";s:4:"0a80";s:20:"Classes/Importer.php";s:4:"33e0";s:43:"Classes/Controller/DataModuleController.php";s:4:"eb3f";s:42:"Classes/Controller/LogModuleController.php";s:4:"a0cc";s:28:"Classes/Domain/Model/Log.php";s:4:"d3ca";s:53:"Classes/Domain/Repository/ConfigurationRepository.php";s:4:"b38d";s:43:"Classes/Domain/Repository/LogRepository.php";s:4:"60d6";s:49:"Classes/Domain/Repository/SchedulerRepository.php";s:4:"436e";s:53:"Classes/Task/AutomatedSyncAdditionalFieldProvider.php";s:4:"bb6e";s:34:"Classes/Task/AutomatedSyncTask.php";s:4:"a8c3";s:49:"Classes/Transformation/DateTimeTransformation.php";s:4:"b0f2";s:40:"Classes/Updates/SchedulerTasksWizard.php";s:4:"6dc8";s:67:"Classes/Updates/class.Tx_ExternalImport_Autosync_Scheduler_Task.php";s:4:"48d9";s:32:"Classes/Utility/DebugUtility.php";s:4:"f2cd";s:36:"Classes/Utility/ReportingUtility.php";s:4:"c368";s:52:"Classes/Validator/AbstractConfigurationValidator.php";s:4:"20a6";s:50:"Classes/Validator/ColumnConfigurationValidator.php";s:4:"77c0";s:51:"Classes/Validator/ControlConfigurationValidator.php";s:4:"3a0d";s:39:"Classes/Validator/DateTimeValidator.php";s:4:"7987";s:40:"Classes/Validator/FrequencyValidator.php";s:4:"f6ce";s:57:"Classes/ViewHelpers/DisplayValidationResultViewHelper.php";s:4:"db9f";s:38:"Classes/ViewHelpers/DumpViewHelper.php";s:4:"afbc";s:42:"Classes/ViewHelpers/HasErrorViewHelper.php";s:4:"3ede";s:53:"Classes/ViewHelpers/ProcessedParametersViewHelper.php";s:4:"e823";s:42:"Classes/ViewHelpers/TwinDumpViewHelper.php";s:4:"a586";s:61:"Classes/ViewHelpers/ValidateColumnConfigurationViewHelper.php";s:4:"34b4";s:62:"Classes/ViewHelpers/ValidateControlConfigurationViewHelper.php";s:4:"3dc5";s:45:"Classes/ViewHelpers/Be/PageLinkViewHelper.php";s:4:"67b7";s:47:"Classes/ViewHelpers/Be/RecordIconViewHelper.php";s:4:"b70d";s:47:"Classes/ViewHelpers/Be/TableTitleViewHelper.php";s:4:"3185";s:36:"Configuration/Backend/AjaxRoutes.php";s:4:"6954";s:56:"Configuration/TCA/tx_externalimport_domain_model_log.php";s:4:"2a92";s:26:"Documentation/Includes.txt";s:4:"c83c";s:23:"Documentation/Index.rst";s:4:"fe21";s:26:"Documentation/Settings.yml";s:4:"d413";s:25:"Documentation/Targets.rst";s:4:"cc7b";s:38:"Documentation/Administration/Index.rst";s:4:"1106";s:46:"Documentation/Administration/Columns/Index.rst";s:4:"bfc4";s:49:"Documentation/Administration/GeneralTca/Index.rst";s:4:"7588";s:46:"Documentation/Administration/Mapping/Index.rst";s:4:"9b4f";s:50:"Documentation/Administration/MmRelations/Index.rst";s:4:"967b";s:49:"Documentation/Administration/UserRights/Index.rst";s:4:"79d6";s:37:"Documentation/Configuration/Index.rst";s:4:"8d49";s:33:"Documentation/Developer/Index.rst";s:4:"b07c";s:37:"Documentation/Developer/Api/Index.rst";s:4:"6749";s:48:"Documentation/Developer/CustomHandlers/Index.rst";s:4:"b3f5";s:39:"Documentation/Developer/Hooks/Index.rst";s:4:"17b7";s:47:"Documentation/Developer/UserFunctions/Index.rst";s:4:"331b";s:41:"Documentation/Images/AutomationDialog.png";s:4:"3830";s:46:"Documentation/Images/ExternalImportProcess.png";s:4:"fea8";s:39:"Documentation/Images/FullAutomation.png";s:4:"7e2d";s:45:"Documentation/Images/InformationInspector.png";s:4:"352f";s:55:"Documentation/Images/InformationInspectorWithErrors.png";s:4:"a66e";s:34:"Documentation/Images/LogModule.png";s:4:"c645";s:56:"Documentation/Images/NonSynchronizableTablesOverview.png";s:4:"c9da";s:53:"Documentation/Images/SynchronizableTablesOverview.png";s:4:"513b";s:47:"Documentation/Images/SynchronizationResults.png";s:4:"05d0";s:37:"Documentation/Images/UpdateWizard.png";s:4:"2d10";s:36:"Documentation/Installation/Index.rst";s:4:"62ca";s:36:"Documentation/Introduction/Index.rst";s:4:"340b";s:37:"Documentation/KnownProblems/Index.rst";s:4:"758c";s:28:"Documentation/User/Index.rst";s:4:"75bc";s:42:"Documentation/User/BackendModule/Index.rst";s:4:"405b";s:34:"Documentation/User/Cache/Index.rst";s:4:"b1a2";s:38:"Documentation/User/Debugging/Index.rst";s:4:"0b7a";s:36:"Documentation/User/General/Index.rst";s:4:"17b7";s:40:"Documentation/User/MappingData/Index.rst";s:4:"546f";s:37:"Documentation/User/Overview/Index.rst";s:4:"7e2e";s:44:"Documentation/User/Troubleshooting/Index.rst";s:4:"a7e1";s:37:"Documentation/User/Tutorial/Index.rst";s:4:"cb16";s:41:"Resources/Private/Language/DataModule.xlf";s:4:"b57f";s:45:"Resources/Private/Language/ExternalImport.xlf";s:4:"13ef";s:40:"Resources/Private/Language/LogModule.xlf";s:4:"860b";s:41:"Resources/Private/Language/MainModule.xlf";s:4:"9004";s:40:"Resources/Private/Language/Validator.xlf";s:4:"1405";s:40:"Resources/Private/Language/locallang.xlf";s:4:"10c9";s:43:"Resources/Private/Language/locallang_db.xlf";s:4:"aada";s:65:"Resources/Private/Partials/DataModule/ColumnConfigurationTab.html";s:4:"3cff";s:66:"Resources/Private/Partials/DataModule/ControlConfigurationTab.html";s:4:"452f";s:56:"Resources/Private/Partials/DataModule/ErrorMessages.html";s:4:"1758";s:54:"Resources/Private/Partials/DataModule/SearchField.html";s:4:"1970";s:53:"Resources/Private/Partials/DataModule/TaskFields.html";s:4:"7df6";s:60:"Resources/Private/Partials/DataModule/Actions/AddButton.html";s:4:"c20a";s:63:"Resources/Private/Partials/DataModule/Actions/DeleteButton.html";s:4:"2912";s:61:"Resources/Private/Partials/DataModule/Actions/EditButton.html";s:4:"b113";s:52:"Resources/Private/Templates/DataModule/EditTask.html";s:4:"4d77";s:65:"Resources/Private/Templates/DataModule/ListNonSynchronizable.html";s:4:"e763";s:62:"Resources/Private/Templates/DataModule/ListSynchronizable.html";s:4:"4c65";s:51:"Resources/Private/Templates/DataModule/NewTask.html";s:4:"3ce7";s:61:"Resources/Private/Templates/DataModule/ViewConfiguration.html";s:4:"0932";s:47:"Resources/Private/Templates/LogModule/List.html";s:4:"f608";s:42:"Resources/Public/Images/DataModuleIcon.svg";s:4:"b649";s:31:"Resources/Public/Images/Log.png";s:4:"0013";s:41:"Resources/Public/Images/LogModuleIcon.svg";s:4:"cd6d";s:42:"Resources/Public/Images/MainModuleIcon.svg";s:4:"d4b2";s:41:"Resources/Public/JavaScript/DataModule.js";s:4:"467e";s:40:"Resources/Public/JavaScript/LogModule.js";s:4:"8afe";s:46:"Resources/Public/StyleSheet/ExternalImport.css";s:4:"bab3";s:47:"Tests/Unit/ColumnConfigurationValidatorTest.php";s:4:"f1aa";s:48:"Tests/Unit/ControlConfigurationValidatorTest.php";s:4:"181b";s:27:"Tests/Unit/ImporterTest.php";s:4:"a86e";}',
);


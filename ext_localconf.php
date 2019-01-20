<?php
defined('TYPO3_MODE') or die();

// Register the edit panel view.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = \TYPO3\CMS\Feedit\FrontendEditPanel::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['edit'] = [
    'module' => \TYPO3\CMS\Feedit\Modules\EditModule::class,
    'after' => ['cache'],
];
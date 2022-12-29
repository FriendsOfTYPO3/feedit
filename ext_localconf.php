<?php

// Register the edit panel view.
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = \TYPO3\CMS\Feedit\FrontendEditPanel::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['feedit'] = \TYPO3\CMS\Feedit\Hooks\StdWrapEditPanelHook::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['edit'] = [
    'module' => \TYPO3\CMS\Feedit\Modules\EditModule::class,
    'after' => ['cache'],
];

$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['EDITPANEL']
    = \TYPO3\CMS\Feedit\ContentObject\EditPanelContentObject::class;
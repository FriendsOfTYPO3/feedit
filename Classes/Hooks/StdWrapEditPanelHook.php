<?php

declare(strict_types=1);

namespace TYPO3\CMS\Feedit\Hooks;


use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Feedit\FrontendEditPanel;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;

class StdWrapEditPanelHook implements ContentObjectStdWrapHookInterface
{
    public function __construct()
    {

    }

    public function stdWrapPreProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapOverride($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapPostProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        $user = $this->getFrontendBackendUser();
        if ($user) {
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            if (($configuration['editPanel'] ?? false) && StateUtility::isOpen() && $configurationService->getConfigurationOption('edit', 'displayIcons')) {
                [$table, $uid] = explode(':', $parentObject->currentRecord);
                $allowedActions = $user->getAllowedEditActions($table, $configuration['editPanel.'] ?? [], $parentObject->data['pid']);
                $frontendEditPanel = GeneralUtility::makeInstance(FrontendEditPanel::class, $parentObject);
                return $frontendEditPanel->editPanel(
                    $content,
                    $configuration['editPanel.'] ?? [],
                    $parentObject->currentRecord,
                    $parentObject->data,
                    'tt_content',
                    $allowedActions
                );
            }
        }
        return $content;
    }

    protected function getFrontendBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
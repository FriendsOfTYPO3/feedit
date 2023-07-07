<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Feedit\Modules;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Feedit\Service\EditToolbarService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Edit Module
 */
class EditModule extends AbstractModule implements PageSettingsProviderInterface, RequestEnricherInterface, ResourceProviderInterface
{
    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @param UriBuilder $uriBuilder
     */
    public function __construct(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
        parent::__construct();
    }

    /**
     * Creates the content for the "edit" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getPageSettings(): string
    {
        $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
        $toolbar = $editToolbarService->createToolbar();
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:feedit/Resources/Private/Templates/Modules/Settings/Edit.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:feedit/Resources/Private/Partials']);
        $view->assignMultiple(
            [
                'display' => [
                    'fieldIcons' => $this->configurationService->getConfigurationOption('edit', 'displayFieldIcons'),
                    'displayIcons' => $this->configurationService->getConfigurationOption('edit', 'displayIcons'),
                ],
                'toolbar' => $toolbar,
                'script' => [
                    'backendScript' => $this->uriBuilder->buildUriFromRoute(
                        'web_layout',
                        [
                            'id' => (int)$this->getTypoScriptFrontendController()->page['uid'],
                        ]
                    ),
                    't3BeSitenameMd5' => md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']),
                ],
            ]
        );
        return $view->render();
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return string
     */
    private function getPageModule(): string
    {
        $pageModule = trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? '');
        return BackendUtility::isModuleSetInTBE_MODULES($pageModule) ? $pageModule : 'web_layout';
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'edit';
    }

    /**
     * @inheritdoc
     */
    public function getIconIdentifier(): string
    {
        return 'actions-open';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $locallangFileAndPath = 'LLL:EXT:feedit/Resources/Private/Language/locallang_edit.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    /**
     * Initialize the edit module
     * Includes the frontend edit initialization
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:feedit/Resources/Public/JavaScript/Modules/Edit.js'];
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [];
    }
}

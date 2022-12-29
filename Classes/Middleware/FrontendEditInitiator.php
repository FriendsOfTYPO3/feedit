<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Feedit\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Feedit\DataHandling\FrontendEditDataHandler;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * PSR-15 middleware initializing frontend editing
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:feedit and not part of TYPO3's Core API.
 */
class FrontendEditInitiator implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication && StateUtility::isOpen()) {
            $this->initializeTypoScriptFrontend(
                $GLOBALS['TSFE'],
                $request,
                GeneralUtility::makeInstance(ConfigurationService::class)
            );
            $config = $GLOBALS['BE_USER']->getTSConfig()['admPanel.'] ?? [];
            $active = (int)$GLOBALS['TSFE']->displayEditIcons === 1 || (int)$GLOBALS['TSFE']->displayFieldEditIcons === 1;
            // Include classes for editing IF editing module in Admin Panel is open
            if ($active && isset($config['enable.'])) {
                foreach ($config['enable.'] as $value) {
                    if ($value) {
                        $parameters = $request->getParsedBody()['TSFE_EDIT'] ?? $request->getQueryParams()['TSFE_EDIT'] ?? [];
                        if ($this->isValidEditAction($parameters)) {
                            GeneralUtility::makeInstance(FrontendEditDataHandler::class, $parameters)->editAction();
                        }
                        break;
                    }
                }
            }
        }
        return $handler->handle($request);
    }

    /**
     * Returns TRUE if an edit-action is sent from the Admin Panel
     *
     * @param array|null $parameters
     * @return bool
     */
    protected function isValidEditAction(array &$parameters = null): bool
    {
        if (!is_array($parameters)) {
            return false;
        }
        if ($parameters['cancel'] ?? false) {
            unset($parameters['cmd']);
        } else {
            $cmd = (string)($parameters['cmd'] ?? '');
            if (($cmd !== 'edit' || is_array($parameters['data']) && ($parameters['doSave'] || $parameters['update'] || $parameters['update_close'])) && $cmd !== 'new') {
                // $cmd can be a command like "hide" or "move". If $cmd is "edit" or "new" it's an indication to show the formfields. But if data is sent with update-flag then $cmd = edit is accepted because edit may be sent because of .keepGoing flag.
                return true;
            }
        }
        return false;
    }

    protected function initializeTypoScriptFrontend(
        TypoScriptFrontendController $typoScriptFrontend,
        ServerRequestInterface $request,
        ConfigurationService $configurationService
    ): void {
        $typoScriptFrontend->displayEditIcons = $configurationService->getConfigurationOption('edit', 'displayIcons');
        $typoScriptFrontend->displayFieldEditIcons = $configurationService->getConfigurationOption('edit', 'displayFieldIcons');

        if ($request->getQueryParams()['ADMCMD_editIcons'] ?? $request->getParsedBody()['ADMCMD_editIcons'] ?? false) {
            $typoScriptFrontend->displayFieldEditIcons = '1';
        }
        if ($typoScriptFrontend->displayEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display edit icons', true);
        }
        if ($typoScriptFrontend->displayFieldEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display field edit icons', true);
        }
    }
}

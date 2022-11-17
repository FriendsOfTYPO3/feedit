<?php
namespace TYPO3\CMS\Feedit;

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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Feedit\Service\EditToolbarService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * View class for the edit panels in frontend editing.
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:feedit and not part of TYPO3's Core API.
 */
class FrontendEditPanel
{
    /**
     * The Content Object Renderer
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    /**
     * Property for accessing TypoScriptFrontendController centrally
     *
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * @var FrontendBackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor for the edit panel
     *
     * @param mixed $_ Previous the database connection
     * @param TypoScriptFrontendController $frontendController
     * @param FrontendBackendUserAuthentication $backendUser
     */
    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null, FrontendBackendUserAuthentication $backendUser = null)
    {
        $this->frontendController = $frontendController ?: $GLOBALS['TSFE'];
        $this->backendUser = $backendUser ?: $GLOBALS['BE_USER'];
        $this->cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->start([]);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_tsfe.xlf');
    }

    /**
     * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
     * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
     * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
     *
     * @param string $content A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
     * @param array $conf TypoScript configuration properties for the editPanel
     * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
     * @param array $dataArr Alternative data array to use. Default is $this->data
     * @param string $table
     * @param array $allow
     * @param int $newUID
     * @param array $hiddenFields
     * @return string The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
     */
    public function editPanel($content, array $conf, $currentRecord = '', array $dataArr = [], $table = '', array $allow = [], $newUID = 0, array $hiddenFields = [])
    {
        $hiddenFieldString = '';

        // Special content is about to be shown, so the cache must be disabled.
        $this->frontendController->set_no_cache('Frontend edit panel is shown', true);
        GeneralUtility::makeInstance(AssetCollector::class)->addStyleSheet('feedit', GeneralUtility::getFileAbsFileName('EXT:feedit/Resources/Public/Css/feedit.css'));

        $formName = 'TSFE_EDIT_FORM_' . substr($this->frontendController->uniqueHash(), 0, 4);
        $formTag = '<form name="' . $formName . '" id ="' . $formName . '" action="' . htmlspecialchars($this->getReturnUrl($dataArr['uid'] ?? null)) . '" method="post" enctype="multipart/form-data">';
        $sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
        $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

        $panel = '';
        if (isset($allow['toolbar'])) {
            $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
            $panel .= $editToolbarService->createToolbar();
        }
        if (isset($allow['edit'])) {
            $icon = '<span title="' . $this->getLabel('p_editRecord') . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render('inline') . '</span>';
            $panel .= $this->editPanelLinkWrap($icon, $formName, 'edit', $dataArr['_LOCALIZED_UID'] ? $table . ':' . $dataArr['_LOCALIZED_UID'] : $currentRecord);
        }
        // Hiding in workspaces because implementation is incomplete
        if (isset($allow['move']) && $sortField && $this->backendUser->workspace === 0) {
            $icon = '<span title="' . $this->getLabel('p_moveUp') . '">' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render('inline') . '</span>';
            $panel .= $this->editPanelLinkWrap($icon, $formName, 'up');
            $icon = '<span title="' . $this->getLabel('p_moveDown') . '">' . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render('inline') . '</span>';
            $panel .= $this->editPanelLinkWrap($icon, $formName, 'down');
        }
        // Hiding in workspaces because implementation is incomplete
        // Hiding for localizations because it is unknown what should be the function in that case
        if (isset($allow['hide']) && $hideField && $this->backendUser->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
            if ($dataArr[$hideField]) {
                $icon = $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render('inline');
                $panel .= $this->editPanelLinkWrap($icon, $formName, 'unhide');
            } else {
                $icon = $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render('inline');
                $panel .= $this->editPanelLinkWrap($icon, $formName, 'hide', '', $this->getLabel('p_hideConfirm'));
            }
        }
        if (isset($allow['new'])) {
            if ($table === 'pages') {
                $icon = '<span title="' . $this->getLabel('p_newSubpage') . '">'
                    . $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render('inline')
                    . '</span>';
                $panel .= $this->editPanelLinkWrap($icon, $formName, 'new', $currentRecord, '');
            } else {
                $icon = '<span title="' . $this->getLabel('p_newRecordAfter') . '">'
                    . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render('inline')
                    . '</span>';
                $panel .= $this->editPanelLinkWrap($icon, $formName, 'new', $currentRecord, '', $newUID);
            }
        }
        // Hiding in workspaces because implementation is incomplete
        // Hiding for localizations because it is unknown what should be the function in that case
        if (isset($allow['delete']) && $this->backendUser->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
            $icon = '<span title="' . $this->getLabel('p_delete') . '">'
                . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render('inline')
                . '</span>';
            $panel .= $this->editPanelLinkWrap($icon, $formName, 'delete', '', $this->getLabel('p_deleteConfirm'));
        }
        // Final
        $labelTxt = $this->cObj->stdWrap($conf['label'], $conf['label.']);
        foreach ((array)$hiddenFields as $name => $value) {
            $hiddenFieldString .= '<input type="hidden" name="TSFE_EDIT[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '"/>' . LF;
        }

        $panel = '<!-- BE_USER Edit Panel: -->
                                ' . $formTag . $hiddenFieldString . '
                                    <input type="hidden" class="typo3-feedit-cmd" name="TSFE_EDIT[cmd]" value="" />
                                    <input type="hidden" name="TSFE_EDIT[record]" value="' . $currentRecord . '" />
                                    <div class="typo3-editPanel">'
                                        . '<div class="typo3-editPanel-btn-group">'
                                        . $panel
                                        . '</div>' .
            ($labelTxt ? '<div class="typo3-editPanel-label">' . sprintf($labelTxt, htmlspecialchars(GeneralUtility::fixed_lgd_cs($dataArr[$labelField], 50))) . '</div>' : '') . '
                                    </div>
                                </form>';

        // Wrap the panel
        if ($conf['innerWrap']) {
            $panel = $this->cObj->wrap($panel, $conf['innerWrap']);
        }
        if ($conf['innerWrap.']) {
            $panel = $this->cObj->stdWrap($panel, $conf['innerWrap.']);
        }

        // Wrap the complete panel
        if ($conf['outerWrap']) {
            $panel = $this->cObj->wrap($panel, $conf['outerWrap']);
        }
        if ($conf['outerWrap.']) {
            $panel = $this->cObj->stdWrap($panel, $conf['outerWrap.']);
        }
        if ($conf['printBeforeContent']) {
            $finalOut = $panel . $content;
        } else {
            $finalOut = $content . $panel;
        }

        $hidden = $this->isDisabled($table, $dataArr) ? ' typo3-feedit-element-hidden' : '';
        $outerWrapConfig = $conf['stdWrap.'] ?? ['wrap' => '<div class="typo3-feedit-element' . $hidden . '">|</div>'];
        $finalOut = $this->cObj->stdWrap($finalOut, $outerWrapConfig);

        return $finalOut;
    }

    /**
     * Adds an edit icon to the content string. The edit icon links to EditDocumentController with proper parameters for editing the table/fields of the context.
     * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
     *
     * @param string $content The content to which the edit icons should be appended
     * @param string $params The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to EditDocumentController
     * @param array $conf TypoScript properties for configuring the edit icons.
     * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
     * @param array $dataArr Alternative data array to use. Default is $this->data
     * @param string $addUrlParamStr Additional URL parameters for the link pointing to EditDocumentController
     * @param string $table
     * @param int $editUid
     * @param string $fieldList
     * @return string The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
     */
    public function editIcons($content, $params, array $conf = [], $currentRecord = '', array $dataArr = [], $addUrlParamStr = '', $table, $editUid, $fieldList)
    {
        // Special content is about to be shown, so the cache must be disabled.
        $this->frontendController->set_no_cache('Display frontend edit icons', true);
        $iconTitle = $this->cObj->stdWrap($conf['iconTitle'], $conf['iconTitle.']);
        $iconImg = '<span title="' . htmlspecialchars($iconTitle, ENT_COMPAT, 'UTF-8', false) . '" >'
            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render('inline')
            . '</span>';
        $noView = GeneralUtility::_GP('ADMCMD_view') ? 1 : 0;

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit[' . $table . '][' . $editUid . ']' => 'edit',
                'columnsOnly' => $fieldList,
                'noView' => $noView,
                'feEdit' => 1,
                'returnUrl' => htmlspecialchars($this->getReturnUrl($editUid)),
            ]
        ) . $addUrlParamStr;
        $icon = $this->editPanelLinkWrap_doWrap($iconImg, $url, 'content-link');
        if ($conf['beforeLastTag'] < 0) {
            $content = $icon . $content;
        } elseif ($conf['beforeLastTag'] > 0) {
            $cBuf = rtrim($content);
            $secureCount = 30;
            while ($secureCount && substr($cBuf, -1) === '>' && substr($cBuf, -4) !== '</a>') {
                $cBuf = rtrim(preg_replace('/<[^<]*>$/', '', $cBuf));
                $secureCount--;
            }
            $content = strlen($cBuf) && $secureCount ? substr($content, 0, strlen($cBuf)) . $icon . substr($content, strlen($cBuf)) : ($content = $icon . $content);
        } else {
            $content .= $icon;
        }
        return $content;
    }

    /**
     * Helper function for editPanel() which wraps icons in the panel in a link with the action of the panel.
     * The links are for some of them not simple hyperlinks but onclick-actions which submits a little form which the panel is wrapped in.
     *
     * @param string $string The string to wrap in a link, typ. and image used as button in the edit panel.
     * @param string $formName The name of the form wrapping the edit panel.
     * @param string $cmd The command of the link. There is a predefined list available: edit, new, up, down etc.
     * @param string $currentRecord The "table:uid" of the record being processed by the panel.
     * @param string $confirm Text string with confirmation message; If set a confirm box will be displayed before carrying out the action (if Yes is pressed)
     * @param int|string $nPid "New pid" - for new records
     * @return string A <a> tag wrapped string.
     */
    protected function editPanelLinkWrap($string, $formName, $cmd, $currentRecord = '', $confirm = '', $nPid = '')
    {
        $noView = GeneralUtility::_GP('ADMCMD_view') ? 1 : 0;
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        if ($cmd === 'edit') {
            $rParts = explode(':', $currentRecord);
            $out = $this->editPanelLinkWrap_doWrap(
                $string,
                (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit[' . $rParts[0] . '][' . $rParts[1] . ']' => 'edit',
                        'noView' => $noView,
                        'feEdit' => 1,
                        'returnUrl' => htmlspecialchars($this->getReturnUrl($rParts[1])),
                    ]
                ),
                $currentRecord
            );
        } elseif ($cmd === 'new') {
            $rParts = explode(':', $currentRecord);
            $uidForReturn = null;
            if (is_numeric($rParts[1])) {
                $uidForReturn = $rParts[1];
            }
            if ($rParts[0] === 'pages') {
                $out = $this->editPanelLinkWrap_doWrap(
                    $string,
                    (string)$uriBuilder->buildUriFromRoute(
                        'db_new',
                        [
                            'id' => $rParts[1],
                            'pagesOnly' => 1,
                            'returnUrl' => htmlspecialchars($this->getReturnUrl($uidForReturn)),
                        ]
                    ),
                    $currentRecord
                );
            } else {
                if (!(int)$nPid) {
                    $nPid = MathUtility::canBeInterpretedAsInteger($rParts[1]) ? -$rParts[1] : $this->frontendController->id;
                }
                $out = $this->editPanelLinkWrap_doWrap(
                    $string,
                    (string)$uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit[' . $rParts[0] . '][' . $nPid . ']' => 'new',
                            'noView' => $noView,
                            'returnUrl' => htmlspecialchars($this->getReturnUrl($uidForReturn)),
                        ]
                    ),
                    $currentRecord
                );
            }
        } else {
            if ($confirm && $this->backendUser->jsConfirmation(JsConfirmation::FE_EDIT) === false) {
                $confirm = '';
            }
            $out = '<a href="#" class="typo3-editPanel-btn typo3-editPanel-btn-default typo3-feedit-btn-submitForm" data-feedit-confirm="' . htmlspecialchars($confirm) . '" data-feedit-formname="' . htmlspecialchars($formName) . '" data-feedit-cmd="' . htmlspecialchars($cmd) . '">' . $string . '</a>';
        }
        return $out;
    }

    /**
     * Creates a link to a script (eg. EditDocumentController or NewRecordController) which either opens in the current frame OR in a pop-up window.
     *
     * @param string $string The string to wrap in a link, typ. and image used as button in the edit panel.
     * @param string $url The URL of the link. Should be absolute if supposed to work with <base> path set.
     * @param string $additionalClasses Additional CSS classes
     * @return string A <a> tag wrapped string.
     * @see editPanelLinkWrap()
     */
    protected function editPanelLinkWrap_doWrap($string, $url, $additionalClasses = '')
    {
        $classes = 'typo3-editPanel-btn typo3-editPanel-btn-default typo3-feedit-btn-openBackend frontEndEditIconLinks ' . htmlspecialchars($additionalClasses);
        return '<a href="#" class="' . $classes . '" ' . $this->getDataAttributes($url) . '>' .
            $string .
            '</a>';
    }

    /**
     * Returns TRUE if the input table/row would be hidden in the frontend, according to the current time and simulate user group
     *
     * @param string $table The table name
     * @param array $row The data record
     * @return bool
     */
    protected function isDisabled($table, array $row)
    {
        $status = false;
        if (
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] &&
            $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']] ||
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'] &&
            $this->frontendController->simUserGroup &&
            $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group']] == $this->frontendController->simUserGroup ||
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'] &&
            $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME'] ||
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'] &&
            $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] &&
            $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME']
        ) {
            $status = true;
        }

        return $status;
    }

    /**
     * Returns htmlescaped label for key.
     *
     * @param string $key Localization key as accepted by LanguageService
     * @return string The value for the $key
     */
    protected function getLabel(string $key): string
    {
        return htmlspecialchars($this->getLanguageService()->getLL($key));
    }

    /**
     * Returns data attributes to call the provided url via JavaScript.
     *
     * @param string $url The url to call via JavaScript.
     * @return string Data attributes without whitespace at beginning or end.
     */
    protected function getDataAttributes(string $url): string
    {
        $t3BeSitenameMd5 = md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);

        return implode(' ', [
            'data-backendScript="' . $url . '"',
            'data-t3BeSitenameMd5="' . $t3BeSitenameMd5 . '"',
        ]);
    }

    /**
     * Returns the returnUrl used by TYPO3. Add this as "returnUrl=" to any url that allows the user to go back or close an form.
     *
     * @param int $recordUid The record which was edited. Or null if no record was edited. Used to jump back to that record.
     * @return string The return url.
     */
    protected function getReturnUrl(int $recordUid = null): string
    {
        $url = GeneralUtility::getIndpEnv('REQUEST_URI');

        if (is_int($recordUid)) {
            $uri = new Uri($url);
            $uri = $uri->withFragment('#c' . $recordUid);
            $url = (string) $uri;
        }

        return $url;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

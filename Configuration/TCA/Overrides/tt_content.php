<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        foreach(['person', 'institution', 'map'] as $controller) {
            foreach(['', 'list', 'searchform'] as $action) {
                if (!($controller == 'map' && $action == 'searchform')) {
                    $pluginSignature = 'nkcaddress_'.$controller.$action;
                    $filename = sprintf('FILE:EXT:nkc_address/Configuration/FlexForms/flexform_%s.xml', $action ? $controller.'_'.$action : $controller);
                    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
                    ExtensionManagementUtility::addPiFlexFormValue(
                        $pluginSignature,
                        $filename
                    );
                }
            }
        }
    }
);

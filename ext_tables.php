<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Person',
            'Person(en) darstellen'
        );

        $pluginSignature = str_replace('_', '', 'nkc_address') . '_person';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:nkc_address/Configuration/FlexForms/flexform_person.xml');

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Institution',
            'Institution(en) darstellen'
        );

        $pluginSignature = str_replace('_', '', 'nkc_address') . '_institution';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:nkc_address/Configuration/FlexForms/flexform_institution.xml');

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Map',
            'Karte mit Institutionen/Personen darstellen'
        );

        $pluginSignature = str_replace('_', '', 'nkc_address') . '_map';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:nkc_address/Configuration/FlexForms/flexform_map.xml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('nkc_address', 'Configuration/TypoScript', 'Nordkirche Address Client');
    }
);

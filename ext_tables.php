<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'PersonList',
            'Person(en): Listenansicht'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'PersonSearchForm',
            'Person(en): Suchformular'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Person',
            'Person(en): Visitenkarte'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'PersonRedirect',
            'Person(en): Umleitung auf sprechende Url'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'InstitutionList',
            'Institution: Listenansicht'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'InstitutionSearchForm',
            'Institution: Suchformular'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Institution',
            'Institution: Visitenkarte'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'InstitutionRedirect',
            'Institution: Umleitung auf sprechende Url'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'Map',
            'Karte mit Institutionen/Personen darstellen'
        );

        ExtensionUtility::registerPlugin(
            'NkcAddress',
            'MapList',
            'Karte und Liste mit Institutionen/Personen darstellen'
        );

        ExtensionManagementUtility::addStaticFile('nkc_address', 'Configuration/TypoScript', 'Nordkirche Address Client');
    }
);

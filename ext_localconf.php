<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {

        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_nkcaddress_map[page]';

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Person',
            [
                \Nordkirche\NkcAddress\Controller\PersonController::class => 'show, list, search, searchForm, redirect'
            ],
            // non-cacheable actions
            [
                \Nordkirche\NkcAddress\Controller\PersonController::class => 'redirect'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Institution',
            [
                \Nordkirche\NkcAddress\Controller\InstitutionController::class => 'show, list, search, searchForm, redirect'
            ],
            // non-cacheable actions
            [
                \Nordkirche\NkcAddress\Controller\InstitutionController::class => 'redirect'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Map',
            [
                \Nordkirche\NkcAddress\Controller\MapController::class => 'show,list,data,paginatedData',
            ],
            // non-cacheable actions
            [
                \Nordkirche\NkcAddress\Controller\MapController::class => 'paginatedData'
            ]
        );

        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            'mod {
                wizards.newContentElement.wizardItems.plugins {
                    elements {
                        address_person {
                            iconIdentifier = content-image
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_domain_model_person
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_domain_model_person.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_person
                            }
                        }
                        address_institution {
                            iconIdentifier = content-image
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_domain_model_institution
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_domain_model_institution.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institution
                            }
                        }
                        address_map {
                            iconIdentifier = content-image
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_map
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_map.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_map
                            }
                        }
                    }
                    show = *
                }
           }'
        );

        // Page module hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['nkc_address'] =
            \Nordkirche\NkcAddress\Hook\CmsLayout::class;

        // Cache
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'] = [];
        }
    }
);

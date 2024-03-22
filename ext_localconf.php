<?php

use Nordkirche\NkcAddress\Controller\InstitutionController;
use Nordkirche\NkcAddress\Controller\MapController;
use Nordkirche\NkcAddress\Controller\PersonController;
use Nordkirche\NkcAddress\Hook\CmsLayout;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_nkcaddress_map[page]';

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Person',
            [
                PersonController::class => 'show',
            ],
            [
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'PersonList',
            [
                PersonController::class => 'search,list',
            ],
            [
                PersonController::class => 'search',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'PersonSearchForm',
            [
                PersonController::class => 'searchForm',
            ],
            [
                PersonController::class => 'searchForm',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'PersonRedirect',
            [
                PersonController::class => 'redirect',
            ],
            // non-cacheable actions
            [
                PersonController::class => 'redirect',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Institution',
            [
                InstitutionController::class => 'show',
            ],
            [
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'InstitutionList',
            [
                InstitutionController::class => 'list, search',
            ],
            [
                InstitutionController::class => 'search',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'InstitutionSearchForm',
            [
                InstitutionController::class => 'searchForm',
            ],
            [
                InstitutionController::class => 'searchForm',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'InstitutionRedirect',
            [
                InstitutionController::class => 'redirect',
            ],
            [
                InstitutionController::class => 'redirect',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'Map',
            [
                MapController::class => 'show,data,paginatedData',
            ],
            // non-cacheable actions
            [
                MapController::class => 'paginatedData',
            ]
        );

        ExtensionUtility::configurePlugin(
            'NkcAddress',
            'MapList',
            [
                MapController::class => 'list,data,paginatedData',
            ],
            // non-cacheable actions
            [
                MapController::class => 'paginatedData',
            ]
        );

        // wizards
        ExtensionManagementUtility::addPageTSConfig(
            'mod {
                wizards.newContentElement.wizardItems.nordkirche_address {
                    header = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.header
                    elements {

                        address_person_list {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_personlist
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_personlist.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_personlist
                            }
                        }
                        address_person_searchform {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_personsearchform
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_personsearchform.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_personsearchform
                            }
                        }
                        address_person {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_person
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_person.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_person
                            }
                        }

                        address_institution_list {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institutionlist
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institutionlist.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institutionlist
                            }
                        }
                        address_institution_searchform {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institutionsearchform
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institutionsearchform.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institutionsearchform
                            }
                        }
                        address_institution {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institution
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_institution.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institution
                            }
                        }

                        address_map {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_map
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_map.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_map
                            }
                        }
                        address_maplist {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard,nkcaddress_maplist
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.nkcaddress_maplist.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_maplist
                            }
                        }

                    }
                    show = *
                }
           }'
        );

        // Cache
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'] = [];
        }

        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = 0;
    }
);

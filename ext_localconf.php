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
                wizards.newContentElement.wizardItems.nordkirche {
                    header = Nordkirche
                    elements {

                        address_person_list {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_list
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_list.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_personlist
                            }
                        }
                        address_person_search {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_search
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_search.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_personsearch
                            }
                        }
                        address_person_searchform {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_searchform
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person_searchform.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_personsearchform
                            }
                        }
                        address_person {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_person.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_person
                            }
                        }

                        address_institution_list {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_list
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_list.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institutionlist
                            }
                        }
                        address_institution_search {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_search
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_search.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institutionsearch
                            }
                        }
                        address_institution_searchform {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_searchform
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution_searchform.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institutionsearchform
                            }
                        }
                        address_institution {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.address_institution.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_institution
                            }
                        }

                        address_map {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_map
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_map.description
                            tt_content_defValues {
                                CType = list
                                list_type = nkcaddress_map
                            }
                        }
                        address_maplist {
                            iconIdentifier = content-plugin
                            title = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_maplist
                            description = LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:tx_nkc_address_maplist.description
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

        // Page module hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['nkc_address'] =
            CmsLayout::class;

        // Cache
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_institution_relation'] = [];
        }

        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = 0;
    }
);

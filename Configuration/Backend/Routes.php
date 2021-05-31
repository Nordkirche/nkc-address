<?php

/**
 * Definitions for routes provided by EXT:nk_base
 * Contains all "regular" routes for entry points
 *
 * Please note that this setup is preliminary until all core use-cases are set up here.
 * Especially some more properties regarding modules will be added until TYPO3 CMS 7 LTS, and might change.
 *
 * Currently the "access" property is only used so no token creation + validation is made,
 * but will be extended further.
 */
return [
    // Register napi wizard
    'wizard_napi' => [
        'path' => '/wizard/napi',
        'target' => Nordkirche\NkcBase\Wizard\NapiWizardController::class . '::mainAction'
    ]
];

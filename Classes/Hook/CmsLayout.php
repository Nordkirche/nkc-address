<?php

namespace Nordkirche\NkcAddress\Hook;

/**
 * This file is part of the "nkc_address" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use Nordkirche\Ndk\Api;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\Ndk\Service\Result;
use Nordkirche\NkcAddress\Controller\MapController;
use Nordkirche\NkcBase\Controller\BaseController;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook to display verbose information about the plugin
 */
class CmsLayout implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $flexformData;

    /**
     * Preprocesses the preview rendering of a content element.
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionalities
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     * @throws \Nordkirche\NkcBase\Exception\ApiException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['list_type'] == 'nkcaddress_institution') {
            $this->api = ApiService::get();

            $this->flexformData = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);

            $drawItem = false;

            $headerContent = '<h3>Institution(en) darstellen</h3>';

            list($controller, $action) = explode('->', $this->getFieldFromFlexform('switchableControllerActions', 'sDEF'));

            $content = '<p>Funktion: ' . ucfirst($action) . '</p>';

            $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

            if ($action == 'show') {
                $layoutKey = $this->getFieldFromFlexform('settings.flexform.showTemplate', 'sTemplate');

                $content .= '<p>Layout: ' . ($layoutKey == 'MiniVCard' ? 'Mini-Visitenkarte' : 'Visitenkarte') . '</p>';

                $content .= $this->renderInstitutionSingleView();
            } elseif ($action == 'searchForm') {
                $content .= '';
            } else {
                $content .= $this->renderInstitutionListView();
            }

            $itemContent = $content;
        } elseif ($row['list_type'] == 'nkcaddress_person') {
            $this->api = ApiService::get();

            $this->flexformData = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);

            $drawItem = false;

            $headerContent = '<h3>Person(en) darstellen</h3>';

            list($controller, $action) = explode('->', $this->getFieldFromFlexform('switchableControllerActions', 'sDEF'));

            $content = '<p>Funktion: ' . ucfirst($action) . '</p>';

            $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

            if ($action == 'show') {
                $layoutKey = $this->getFieldFromFlexform('settings.flexform.showTemplate', 'sTemplate');

                $content .= '<p>Layout: ' . ($layoutKey == 'MiniVCard' ? 'Mini-Visitenkarte' : 'Visitenkarte') . '</p>';

                $content .= $this->renderPersonSingleView();
            } elseif ($action == 'searchForm') {
                $content .= '';
            } else {
                $content .= $this->renderPersonListView();
            }

            $itemContent = $content;
        } elseif ($row['list_type'] == 'nkcaddress_map') {
            $this->api = ApiService::get();

            $this->flexformData = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);

            $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

            $drawItem = false;

            $headerContent = '<h3>Karte mit Institutionen / Personen darstellen</h3>';

            $itemContent = $this->renderMapView();
        }
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    private function renderMapView()
    {
        $content = '';

        $settings = [
            'asyncLoadingMaxItems' => 10,
            'flexform' => [
                'allInstitutions' => $this->getFieldFromFlexform('settings.flexform.allInstitutions', 'sMarker'),
                'allPersons' => $this->getFieldFromFlexform('settings.flexform.allPersons', 'sMarker'),
                'institutionCollection' => $this->getFieldFromFlexform('settings.flexform.institutionCollection', 'sMarker'),
                'institutionType' => $this->getFieldFromFlexform('settings.flexform.institutionType', 'sMarker'),
                'categories' => $this->getFieldFromFlexform('settings.flexform.categories', 'sMarker'),
                'functionType' => $this->getFieldFromFlexform('settings.flexform.functionType', 'sMarker'),
                'availableFunction' => $this->getFieldFromFlexform('settings.flexform.availableFunction', 'sMarker'),
                'personCollection' => $this->getFieldFromFlexform('settings.flexform.personCollection', 'sMarker')
                ]
        ];

        $mapController = $this->objectManager->get(MapController::class);

        $mapController->initializeAction();

        list($limit, $records) = $mapController->getMapItems($settings);

        $content .= '<p>Marker:<br /><ul>';

        foreach ($records as $record) {
            $content .= '<li>';
            $content .= htmlentities($record->getLabel());
            $content .= ' [' . intval($record->getId()) . ']';
            $content .= '</li>';
        }

        $content .= '</ul></p>';

        if ($limit) {
            $content .= '... ';
        }

        return $content;
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    private function renderInstitutionListView()
    {
        $content = '';

        $institutionRepository = $this->api->factory(InstitutionRepository::class);
        $baseController = $this->objectManager->get(BaseController::class);
        $query = $this->api->factory(\Nordkirche\Ndk\Domain\Query\InstitutionQuery::class);

        // Set pagination parameters
        $query->setPageSize(10);

        // Filter institutions
        $baseController->setInstitutionFilter($query, $this->getFieldFromFlexform('settings.flexform.institutionCollection', 'sDEF'), $this->getFieldFromFlexform('settings.flexform.selectOption', 'sDEF'));

        // Filter by type
        $baseController->setInstitutionTypeFilter($query, $this->getFieldFromFlexform('settings.flexform.institutionType', 'sDEF'));

        // Filter by geoposition
        $baseController->setGeoFilter(
            $query,
            $this->getFieldFromFlexform('settings.flexform.geosearch', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.latitude', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.longitude', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.radius', 'sDEF')
        );

        // Get institutions
        $institutions = $institutionRepository->get($query);

        if ($institutions) {
            $content .= '<p>Liste von Institutionen:<br /><ul>';

            foreach ($institutions as $institution) {
                $content .= '<li>';
                $content .= htmlentities($institution->getName());
                $content .= ' [' . intval($institution->getId()) . ']';
                $content .= '</li>';
            }

            $content .= '</ul></p>';

            if ($institutions->getPageCount() > 1) {
                $content .= '... ' . $institutions->getRecordCount() . ' Institutionen';
            }
        } else {
            $content .= 'Keine Treffer!';
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderInstitutionSingleView()
    {
        $content = '';
        $institutionResource = $this->getFieldFromFlexform('settings.flexform.singleInstitution', 'sDEF');
        if ($institutionResource) {
            $content .= '<p>Zeige ausgewählte Institution: ';
            $napiService = $this->api->factory(NapiService::class);
            $institution = $napiService->resolveUrl($institutionResource);
            if ($institution instanceof Institution) {
                $content .= htmlentities($institution->getName());
                $content .= ' [' . intval($institution->getId()) . ']';
            } else {
                $content .= '[nicht gefunden]';
            }
            $content .= '</p>';
        } else {
            $content .= '<p>Zeige Institution via URL Parameter</p>';
        }
        return $content;
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    private function renderPersonListView()
    {
        $content = '';

        $personRepository = $this->api->factory(PersonRepository::class);
        $baseController = $this->objectManager->get(BaseController::class);
        $query = $this->api->factory(\Nordkirche\Ndk\Domain\Query\PersonQuery::class);

        // Set pagination parameters
        $query->setPageSize(10);

        // Filter by person
        $baseController->setPersonFilter($query, $this->getFieldFromFlexform('settings.flexform.personCollection', 'sDEF'));

        // Filter persons
        $baseController->setPersonInstitutionFilter($query, $this->getFieldFromFlexform('settings.flexform.institutionCollection', 'sDEF'));

        // Filter by type
        $baseController->setFunctionTypeFilter($query, $this->getFieldFromFlexform('settings.flexform.functionType', 'sDEF'));

        // Filter by available function
        $baseController->setAvailableFunctionFilter($query, $this->getFieldFromFlexform('settings.flexform.availableFunction', 'sDEF'));

        // Filter by geoposition
        $baseController->setGeoFilter(
            $query,
            $this->getFieldFromFlexform('settings.flexform.geosearch', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.latitude', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.longitude', 'sDEF'),
            $this->getFieldFromFlexform('settings.flexform.radius', 'sDEF')
        );

        // Get persons
        /** @var Result $persons */
        $persons = $personRepository->get($query);

        if ($persons) {
            $content .= '<p>Liste von Personen:<br /><ul>';

            foreach ($persons as $person) {
                $content .= '<li>';
                $content .= htmlentities($person->getName()->getFormatted());
                $content .= ' [' . intval($person->getId()) . ']';
                $content .= '</li>';
            }
            $content .= '</ul></p>';

            if ($persons->getPageCount() > 1) {
                $content .= '... ' . $persons->getRecordCount() . ' Personen';
            }
        } else {
            $content .= 'Keine Treffer!';
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderPersonSingleView()
    {
        $content = '';
        $personResource = $this->getFieldFromFlexform('settings.flexform.singlePerson', 'sDEF');
        if ($personResource) {
            $content .= '<p>Zeige ausgewählte Person: ';
            $napiService = $this->api->factory(NapiService::class);
            $person = $napiService->resolveUrl($personResource);
            if ($person instanceof Person) {
                $content .= htmlentities($person->getName()->getFormatted());
                $content .= ' [' . intval($person->getId()) . ']';
            } else {
                $content .= '[nicht gefunden]';
            }
            $content .= '</p>';
        } else {
            $content .= '<p>Zeige Person via URL Parameter</p>';
        }
        return $content;
    }

    /**
     * Get field value from flexform configuration,
     * including checks if flexform configuration is available
     *
     * @param string $key name of the key
     * @param string $sheet name of the sheet
     * @return string|null if nothing found, value if found
     */
    protected function getFieldFromFlexform($key, $sheet = 'sDEF')
    {
        $flexform = $this->flexformData;

        if (isset($flexform['data'])) {
            $flexform = $flexform['data'];
            if (is_array($flexform) && is_array($flexform[$sheet]) && is_array($flexform[$sheet]['lDEF'])
                && is_array($flexform[$sheet]['lDEF'][$key]) && isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
            ) {
                return $flexform[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }
}

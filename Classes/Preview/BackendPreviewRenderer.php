<?php

namespace Nordkirche\NkcAddress\Preview;

use Nordkirche\Ndk\Api;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Query\InstitutionQuery;
use Nordkirche\Ndk\Domain\Query\PersonQuery;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\Ndk\Service\Result;
use Nordkirche\NkcAddress\Controller\MapController;
use Nordkirche\NkcBase\Controller\BaseController;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendPreviewRenderer implements \TYPO3\CMS\Backend\Preview\PreviewRendererInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var array
     */
    protected $flexformData;

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        $row = $item->getRecord();
        return sprintf('<h3>%s</h3>', LocalizationUtility::translate('LLL:EXT:nkc_address/Resources/Private/Language/locallang_db.xlf:wizard.' . $row['list_type']));
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $row = $item->getRecord();
        $this->api = ApiService::get();
        $this->flexformData = GeneralUtility::xml2array($row['pi_flexform']);
        $content = '';

        switch ($row['list_type']) {
            case 'nkcaddress_institution':
                $layoutKey = $this->getFieldFromFlexform('settings.flexform.showTemplate', 'sTemplate');
                $content .= '<p>Layout: ' . ($layoutKey == 'MiniVCard' ? 'Mini-Visitenkarte' : 'Visitenkarte') . '</p>';
                $content .= $this->renderInstitutionSingleView();
                break;
            case 'nkcaddress_institutionlist':
                $content .= $this->renderInstitutionListView();
                break;
            case 'nkcaddress_person':
                $layoutKey = $this->getFieldFromFlexform('settings.flexform.showTemplate', 'sTemplate');
                $content .= '<p>Layout: ' . ($layoutKey == 'MiniVCard' ? 'Mini-Visitenkarte' : 'Visitenkarte') . '</p>';
                $content .= $this->renderPersonSingleView();
                break;
            case 'nkcaddress_personlist':
                $content .= $this->renderPersonListView();
                break;
            case 'nkcaddress_map':
            case 'nkcaddress_maplist':
                $content = $this->renderMapView();
                break;
        }
        return $content;
    }

    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        return 'Powered by NAPI';
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        return $previewHeader . $previewContent;
    }

    /**
     * @return string
     * @throws \Exception
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
                'personCollection' => $this->getFieldFromFlexform('settings.flexform.personCollection', 'sMarker'),
            ],
        ];

        $mapController = GeneralUtility::makeInstance(MapController::class);

        $mapController->initializeAction();

        [$limit, $records] = $mapController->getMapItems($settings);

        $content .= '<p>Marker:<br /><ul>';

        foreach ($records as $record) {
            $content .= '<li>';
            $content .= htmlentities($record->getLabel());
            $content .= ' [' . (int)($record->getId()) . ']';
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
     * @throws \Exception
     */
    private function renderInstitutionListView()
    {
        $content = '';

        $institutionRepository = $this->api->factory(InstitutionRepository::class);
        $baseController = GeneralUtility::makeInstance(BaseController::class);
        $query = $this->api->factory(InstitutionQuery::class);

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
            $content .= '<p>Vorschau:<br /><ul>';

            foreach ($institutions as $institution) {
                $content .= '<li>';
                $content .= htmlentities($institution->getName());
                $content .= ' [' . (int)($institution->getId()) . ']';
                $content .= '</li>';
            }

            $content .= '</ul></p>';

            if ($institutions->getPageCount() > 1) {
                $content .= '... und ' . $institutions->getRecordCount() - 10 . ' weitere Institutionen';
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
                $content .= ' [' . (int)($institution->getId()) . ']';
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
     * @throws \Exception
     */
    private function renderPersonListView()
    {
        $content = '';

        $personRepository = $this->api->factory(PersonRepository::class);
        $baseController = GeneralUtility::makeInstance(BaseController::class);
        $query = $this->api->factory(PersonQuery::class);

        // Set pagination parameters
        $query->setPageSize(10);

        // Filter by person
        $baseController->setPersonFilter($query, $this->getFieldFromFlexform('settings.flexform.personCollection', 'sDEF'));

        // Filter by institution
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
            $content .= '<p>Vorschau:<br /><ul>';

            foreach ($persons as $person) {
                $content .= '<li>';
                $content .= htmlentities($person->getName()->getFormatted());
                $content .= ' [' . (int)($person->getId()) . ']';
                $content .= '</li>';
            }
            $content .= '</ul></p>';

            if ($persons->getPageCount() > 1) {
                $content .= '... und ' . $persons->getRecordCount() - 10 . ' weitere Personen';
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
                $content .= ' [' . (int)($person->getId()) . ']';
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

        if (!empty($flexform['data'])) {
            $flexform = $flexform['data'];
            if (is_array($flexform) && !empty($flexform[$sheet]) && is_array($flexform[$sheet]) && is_array($flexform[$sheet]['lDEF'])
                && isset($flexform[$sheet]['lDEF'][$key]) && is_array($flexform[$sheet]['lDEF'][$key]) && isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
            ) {
                return $flexform[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }
}

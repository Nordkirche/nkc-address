<?php

namespace Nordkirche\NkcAddress\Controller;

use Nordkirche\NkcBase\Controller\BaseController;
use Psr\Http\Message\ResponseInterface;
use Nordkirche\Ndk\Domain\Query\PersonQuery;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use Nordkirche\Ndk\Domain\Model\Institution\InstitutionType;
use Nordkirche\Ndk\Domain\Model\Address;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcAddress\Domain\Dto\SearchRequest;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PersonController extends BaseController
{

    /**
     * @var PersonRepository
     */
    protected $personRepository;

    /**
     * @var NapiService
     */
    protected $napiService;

    /**
     * @var StandaloneView
     */
    protected $standaloneView = false;

    public function initializeAction()
    {
        parent::initializeAction();
        $this->personRepository = $this->api->factory(PersonRepository::class);
        $this->napiService = $this->api->factory(NapiService::class);

        if ($this->arguments->hasArgument('searchRequest')) {
            $this->arguments->getArgument('searchRequest')->getPropertyMappingConfiguration()->allowProperties('search');
            $this->arguments->getArgument('searchRequest')->getPropertyMappingConfiguration()->allowProperties('city');
            $this->arguments->getArgument('searchRequest')->getPropertyMappingConfiguration()->allowProperties('category');
        }
    }

    /**
     * List action
     *
     * @param int $currentPage
     * @param SearchRequest $searchRequest
     */
    public function listAction($currentPage = 1, $searchRequest = null): ResponseInterface
    {
        $query = new PersonQuery();

        // Set pagination parameters
        $this->setPagination($query, $currentPage);

        // Filter by person
	    if (!empty($this->settings['flexform']['personCollection'])) {
	        $this->setPersonFilter($query, $this->settings['flexform']['personCollection']);
	    }


        // Filter by type
        if (!empty($this->settings['flexform']['functionType'])) {
			$this->setFunctionTypeFilter($query, $this->settings['flexform']['functionType']);
        }

        // Filter by available function
        if (!empty($this->settings['flexform']['availableFunction'])) {
			$this->setAvailableFunctionFilter($query, $this->settings['flexform']['availableFunction']);
        }

        // Filter by Institution
        if (!empty($this->settings['flexform']['institutionCollection'])) {
			$this->setPersonInstitutionFilter($query, $this->settings['flexform']['institutionCollection']);
        }

        // Filter by geoposition
        if (!empty($this->settings['flexform']['geosearch'])) {
	        $this->setGeoFilter(
		        $query,
		        $this->settings['flexform']['geosearch'],
		        $this->settings['flexform']['latitude'],
		        $this->settings['flexform']['longitude'],
		        $this->settings['flexform']['radius']
	        );
        }

        if ($searchRequest instanceof SearchRequest) {
            $this->setUserFilters($query, $searchRequest);
        } else {
            $searchRequest = new SearchRequest();
        }

        // Sorting
        if (!empty($this->settings['flexform']['sortOption'])) {
            $query->setSort($this->settings['flexform']['sortOption']);
        }

        // Get persons
        $persons = $this->personRepository->get($query);

        // Get current cObj
        $cObj = $this->configurationManager->getContentObject();

        $this->view->assignMultiple([
            'query' => $query,
            'persons' => $persons,
            'content' => $cObj->data,
            'filter' => $this->getFilterValues(),
            'searchPid' => $GLOBALS['TSFE']->id,
            'searchRequest' => $searchRequest,
            'pagination' => $this->getPagination($persons, $currentPage)

        ]);
        return $this->htmlResponse();
    }

    /**
     * @param SearchRequest $searchRequest
     */
    public function searchFormAction($searchRequest = null): ResponseInterface
    {
        if (!($searchRequest instanceof SearchRequest)) {
            $searchRequest = GeneralUtility::makeInstance(SearchRequest::class);
        }

        $this->view->assignMultiple([
            'searchPid' => $this->settings['flexform']['pidList'] ? $this->settings['flexform']['pidList'] : $GLOBALS['TSFE']->id,
            'filter' => $this->getFilterValues(),
            'searchRequest' => $searchRequest
        ]);
        return $this->htmlResponse();
    }

    /**
     * @param SearchRequest $searchRequest
     * @throws StopActionException
     */
    public function searchAction($searchRequest = null)
    {
        if (!($searchRequest instanceof SearchRequest)) {
            $searchRequest = GeneralUtility::makeInstance(SearchRequest::class);
        }

        $this->uriBuilder->setRequest($this->request);
        $uri = $this->uriBuilder->uriFor('list', ['searchRequest' => $searchRequest->toArray()]);
        $this->redirectToURI($uri);
    }

    /**
     * @throws StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function redirectAction()
    {
        if ($nkcp = intval(GeneralUtility::_GP('nkcp'))) {

            $this->uriBuilder->reset()->setTargetPageUid($this->settings['flexform']['pidSingle']);
            $uri = $this->uriBuilder->uriFor('show', ['uid' => $nkcp]);
            $this->redirectToURI($uri);
        }  else {
            $this->redirectToURI('/');
        }
    }

    /**
     * @param int $uid
     * @throws ImmediateResponseException
     */
    public function showAction($uid = null): ResponseInterface
    {
        $includes = [   Person::RELATION_FUNCTIONS => [
                            PersonFunction::RELATION_ADDRESS,
                            PersonFunction::RELATION_AVAILABLE_FUNCTION,
                            PersonFunction::RELATION_INSTITUTION => [
                                Institution::RELATION_ADDRESS,
                                Institution::RELATION_INSTITUTION_TYPE
                            ]
                        ]
        ];

        try {

            if (!empty($this->settings['flexform']['singlePerson'])) {
                // Personis selected in flexform
                $person = $this->napiService->resolveUrl($this->settings['flexform']['singlePerson'], $includes);
            } elseif ((int)$uid) {
                // Find by uid
                $person = $this->personRepository->getById($uid, $includes);
            } else {
                $person = false;
            }

            $this->settings['mapInfo']['recordUid'] = $person ? $person->getId() : 0;

            // Get map markers for all functions
            $mapMarkers = $person ? $this->getMapMarkers($person) : [];

            // Get current cObj
            $cObj = $this->configurationManager->getContentObject();

            $this->view->assignMultiple([
                'person' => $person,
                'mapMarkers' => $mapMarkers,
                'content' => $cObj->data
            ]);

        } catch (\Exception $e) {
            // Page not found
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $GLOBALS['TYPO3_REQUEST'],
                'Person konnte nicht gefunden werden',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
            throw new ImmediateResponseException($response);
        }
        return $this->htmlResponse();
    }

    /**
     * Create marker array
     *
     * @param Person $person
     * @return array
     */
    private function getMapMarkers($person)
    {
        $mapMarkers = [];

        foreach ($person->getFunctions() as $personFunction) {
            if ($personFunction->getInstitution() && $personFunction->getAddress()) {
                $this->createMarker($mapMarkers, $personFunction->getId(), $person, $personFunction->getInstitution(), $personFunction->getAddress());
            } elseif ($personFunction->getInstitution()) {
                $this->createMarker($mapMarkers, $personFunction->getId(), $person, $personFunction->getInstitution(), $personFunction->getInstitution()->getAddress());
            }
        }

        return $mapMarkers;
    }

    /**
     * Add a marker, when geo-coordinates are available
     *
     * @param array $mapMarkers
     * @param int $functionId
     * @param Person $person
     * @param Institution $institution
     * @param \Nordkirche\Ndk\Domain\Model\Address
     */
    private function createMarker(&$mapMarkers, $functionId, $person, $institution, $address)
    {

        // Get type of institution
        if ($institution->getInstitutionType() instanceof InstitutionType) {
            $type = $institution->getInstitutionType()->getName();
        } else {
            $type = 'default';
        }

        if ($address instanceof Address) {
            // Check geo coordinates
            if ($address->getLatitude() && $address->getLongitude()) {
                $marker = [
                    'title' => $institution->getName(),
                    'lat' 	=> $address->getLatitude(),
                    'lon' 	=> $address->getLongitude(),
                    'info' 	=> $this->renderMapInfo($person, $institution, $address),
                    'type'  => $type,
                    'icon' 	=> $this->settings['personIconName'],
                    'object' => 'p',
                    'id'    => $person->getId()

                ];
                $mapMarkers[$functionId] = $marker;
            }
        }
    }

    /**
     * Add a marker
     *
     * @param Person $person
     * @param boolean $asyncInfo
     * @return array
     */
    public function createSingleMarker($person, $asyncInfo = TRUE)

    {
        $address = false;
        $institution = false;
        $type = 'person-default';

        foreach ($person->getFunctions() as $function) {
            if ($function instanceof PersonFunction) {
                if ($functionType = $function->getFunctionType()) {
                    $type .= ' person-' . $functionType->getId();
                }
                if (!$address) {
                    if ($function->getInstitution() && $function->getInstitution()->getAddress()) {
                        $address = $function->getInstitution()->getAddress();
                        $institution = $function->getInstitution();
                    } elseif ($function->getAddress()) {
                        $address = $function->getAddress();
                    }
                }
            }
        }

        if (!($address instanceof Address)) {
            $address = $person->getAddress();
        }

        if ($address instanceof Address) {
            // Check geo coordinates
            if ($address->getLatitude() && $address->getLongitude()) {
                $marker = [
                    'name'  => $person->getName()->getFormatted(),
                    'lat' 	=> $address->getLatitude(),
                    'lon' 	=> $address->getLongitude(),
                    'info' 	=> $asyncInfo ? '' : $this->renderMapInfo($person, $institution, $address),
                    'icon' 	=> $this->settings['personIconName'],
                    'type'  => $type,
                    'object' => 'p',
                    'id'    => $person->getId()
                ];
                return $marker;
            }
            return [];
        }
    }

    /**
     * @param Person $person
     * @param Institution $institution
     * @param \Nordkirche\Ndk\Domain\Model\Address
     * @param string $template
     * @return string
     */
    private function renderMapInfo($person, $institution, $address, $template = 'Person/MapInfo')
    {
        if ($this->standaloneView == false) {
            // Init standalone view

            $config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            $absTemplatePaths = [];
            if (is_array($config['view']['templateRootPaths'])) {
                foreach ($config['view']['templateRootPaths'] as $path) {
                    $absTemplatePaths[] = GeneralUtility::getFileAbsFileName($path);
                }
            }
            if (count($absTemplatePaths) == 0) {
                $absTemplatePaths[] =  GeneralUtility::getFileAbsFileName('EXT:nkc_address/Resources/Private/Templates/');
            }

            $absLayoutPaths = [];
            if (is_array($config['view']['layoutRootPaths'])) {
                foreach ($config['view']['layoutRootPaths'] as $path) {
                    $absLayoutPaths[] = GeneralUtility::getFileAbsFileName($path);
                }
            }
            if (count($absLayoutPaths) == 0) {
                $absLayoutPaths[] = GeneralUtility::getFileAbsFileName('EXT:nkc_address/Resources/Private/Layouts/');
            }

            $absPartialPaths = [];
            if (is_array($config['view']['partialRootPaths'])) {
                foreach ($config['view']['partialRootPaths'] as $path) {
                    $absPartialPaths[] = GeneralUtility::getFileAbsFileName($path);
                }
            }
            if (count($absPartialPaths) == 0) {
                $absPartialPaths[] = GeneralUtility::getFileAbsFileName('EXT:nkc_address/Resources/Private/Partials/');
            }

            $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);

            $this->standaloneView->setLayoutRootPaths(
                $absLayoutPaths
            );
            $this->standaloneView->setPartialRootPaths(
                $absPartialPaths
            );
            $this->standaloneView->setTemplateRootPaths(
                $absTemplatePaths
            );

            $this->standaloneView->setTemplate($template);
        }

        $this->standaloneView->assignMultiple([     'person' 	    => $person,
                                                    'institution'   => $institution,
                                                    'address'       => $address,
                                                    'settings'	    => $this->settings]);

        return $this->standaloneView->render();
    }


    /**
     * @param array $personList
     * @param array $config
     * @return string
     * @return array|mixed
     */
    public function retrieveMarkerInfo($personList, $config) {

        parent::initializeAction();

        $this->personRepository = $this->api->factory(PersonRepository::class);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationManager = $objectManager->get(ConfigurationManager::class);

        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = $objectManager->get(ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($contentObjectRenderer);

        // Required for link.email viewhelper
        $GLOBALS['TSFE']->cObj = $contentObjectRenderer;

        $this->settings = $config['plugin']['tx_nkcaddress_person']['settings'];

        $this->settings['flexform'] = $this->settings['flexformDefault'];

        $query = new PersonQuery();

        $query->setInclude(
            [
                Person::RELATION_FUNCTIONS => [
                    PersonFunction::RELATION_ADDRESS,
                    PersonFunction::RELATION_AVAILABLE_FUNCTION,
                    PersonFunction::RELATION_INSTITUTION => [
                        Institution::RELATION_ADDRESS,
                        Institution::RELATION_INSTITUTION_TYPE
                    ]
                ]
            ]);

        $query->setPersons($personList);

        $query->setPageSize(100);

        $persons = $this->personRepository->get($query);

        $result = '';

        foreach($persons as $person) {

            $address = FALSE;
            $institution = FALSE;

            foreach($person->getFunctions() as $function) {
                if ($function instanceof PersonFunction) {
                    if (!$address) {
                        if ($function->getInstitution() && $function->getInstitution()->getAddress()) {
                            $address = $function->getInstitution()->getAddress();
                            $institution = $function->getInstitution();
                        } elseif ($function->getAddress()) {
                            $address = $function->getAddress();
                        }
                    }
                }
            }

            if (!($address instanceof Address)) {
                $address = $person->getAddress();
            }

            if ($address instanceof Address) {
                $result .= $this->renderMapInfo($person, $institution, $address, 'Person/AsyncMapInfo');
            }
        }

        return $result;
    }

    /**
     * @param PersonQuery $query
     * @param SearchRequest $searchRequest
     */
    private function setUserFilters($query, $searchRequest)
    {
        if (($searchRequest->getSearch() != '') && (strlen($searchRequest->getSearch()) > 2)) {
            $query->setQuery($searchRequest->getSearch());
        }

        if (($searchRequest->getCity() != '') && (strlen($searchRequest->getCity()) > 2)) {
            if (($searchRequest->getCity() != '') && (strlen($searchRequest->getCity()) > 2)) {
                if (is_numeric($searchRequest->getCity())) {
                    $query->setZipCodes([$searchRequest->getCity()]);
                } else {
                    $query->setCities(GeneralUtility::trimExplode(',', $searchRequest->getCity()));
                }
            }
        }

    }

    /**
     * @return array
     */
    private function getFilterValues()
    {
        $filter = [];

        if (!empty($this->settings['filter']['cityCollection'])) {
            $cities = GeneralUtility::trimExplode(',', $this->settings['filter']['cityCollection']);

            $index = 0;
            if (count($cities)) {
                foreach ($cities as $city) {
                    $filter['cities'][$index] = [];
                    $filter['cities'][$index]['name'] = $city;
                    $index++;
                }
            }
        }

        return $filter;
    }
}

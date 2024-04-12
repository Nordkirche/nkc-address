<?php

namespace Nordkirche\NkcAddress\Controller;

use Nordkirche\Ndk\Domain\Model\Address;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Institution\InstitutionType;
use Nordkirche\Ndk\Domain\Model\Institution\OpeningHours;
use Nordkirche\Ndk\Domain\Model\Institution\Team;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Query\InstitutionQuery;
use Nordkirche\Ndk\Domain\Repository\FunctionTypeRepository;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\NkcAddress\Domain\Dto\SearchRequest;
use Nordkirche\NkcBase\Domain\Repository\CategoryRepository;
use Nordkirche\NkcAddress\Event\ModifyAssignedListValuesForInstitutionEvent;
use Nordkirche\NkcAddress\Event\ModifyAssignedValuesForInstitutionEvent;
use Nordkirche\NkcAddress\Event\ModifyInstitutionQueryEvent;
use Nordkirche\NkcBase\Controller\BaseController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

class InstitutionController extends BaseController
{
    /**
     * @var InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * @var PersonRepository
     */
    protected $personRepository;

    /**
     * @var FunctionTypeRepository
     */
    protected $functionTypeRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var ServerRequestInterface
     */
    protected $middleWareRequest;

    /**
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function initializeAction()
    {
        parent::initializeAction();
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
        $this->personRepository = $this->api->factory(PersonRepository::class);
        $this->functionTypeRepository = $this->api->factory(FunctionTypeRepository::class);

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
    public function listAction($currentPage = 1, SearchRequest $searchRequest = null): ResponseInterface
    {
        $query = new InstitutionQuery();

        // Set Included objects
        $query->setInclude([Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE]);

        // Set pagination parameters
        $this->setPagination($query, $currentPage);

        // Apply flexform filters
        $this->setFilter($query);

        if ($searchRequest instanceof SearchRequest) {
            $this->setUserFilters($query, $searchRequest);
        } else {
            $searchRequest = new SearchRequest();
        }

        /** @var ModifyInstitutionQueryEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyInstitutionQueryEvent($this, $query, $this->request)
        );
        $query = $event->getInstitutionQuery();

        // Get institutions
        $institutions = $this->institutionRepository->get($query);

        // Get current cObj
        $cObj = $this->request->getAttribute('currentContentObject');

        $assignedListValues = [
            'query' => $query,
            'institutions' => $institutions,
            'content' => $cObj->data,
            'filter' => $this->getFilterValues(),
            'searchPid' => $GLOBALS['TSFE']->id,
            'searchRequest' => $searchRequest,
            'pagination' => $this->getPagination($institutions, $currentPage),

        ];

        /** @var ModifyAssignedListValuesForInstitutionEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyAssignedListValuesForInstitutionEvent($this, $assignedListValues, $this->request)
        );

        $assignedListValues = $event->getAssignedListValues();

        $this->view->assignMultiple($assignedListValues);

        return $this->htmlResponse();
    }

    /**
     * @param SearchRequest $searchRequest
     */
    public function searchFormAction(SearchRequest $searchRequest = null): ResponseInterface
    {
        if (!($searchRequest instanceof SearchRequest)) {
            $searchRequest = GeneralUtility::makeInstance(SearchRequest::class);
            $searchRequest->decorate($this->request->getParsedBody()['tx_nkcaddress_institutionlist']['searchRequest'] ?? $this->request->getQueryParams()['tx_nkcaddress_institutionlist']['searchRequest'] ?? null);
        }

        $this->view->assignMultiple([
            'searchPid' => $this->settings['flexform']['pidList'] ?: $GLOBALS['TSFE']->id,
            'filter' => $this->getFilterValues(),
            'searchRequest' => $searchRequest,
        ]);
        return $this->htmlResponse();
    }

    /**
     * @param SearchRequest $searchRequest
     */
    public function searchAction(SearchRequest $searchRequest = null): ResponseInterface
    {
        if (!($searchRequest instanceof SearchRequest)) {
            $searchRequest = GeneralUtility::makeInstance(SearchRequest::class);
        }

        // Forward to list view
        $response = new ForwardResponse('list');
        return $response->withArguments(['searchRequest' => $searchRequest->toArray()]);
    }

    /**
     * @param int $uid
     * @throws ImmediateResponseException
     */
    public function showAction($uid = null): ResponseInterface
    {
        if (!empty($this->settings['flexform']['singleInstitution'])) {
            // Institution is selected in flexform
            try {
                /** @var Institution $institution */
                $institution = $this->napiService->resolveUrl($this->settings['flexform']['singleInstitution'], $this->getDetailIncludes());
            } catch (\Exception $e) {
                $institution = false;
            }
        } elseif ((int)$uid) {
            // Find by uid
            try {
                /** @var Institution $institution */
                $institution = $this->institutionRepository->getById($uid, $this->getDetailIncludes());
            } catch (\Exception $e) {
                $institution = false;
            }
        } else {
            $institution = false;
        }

        if ($institution) {
            // Prepare data
            $this->settings['mapInfo']['recordUid'] = $institution->getId();

            // Get children
            $childInstitutions = $this->getChildInstitutions($institution);

            // Get map markers
            $mapMarkers = $this->getMapMarkers($institution, false);

            // Parse opening hours table
            $openingHours = !empty($this->settings['flexform']['openingHours']) ? $this->prepareOpeningHours($institution) : [];

            $assignedValues = [
                'institution' => $institution,
                'childInstitutions' => $childInstitutions,
                'mapMarkers' => $mapMarkers,
                'openingHours' => $openingHours,
                'content' => $this->request->getAttribute('currentContentObject')->data,
            ];
        } else {
            if (!empty($this->settings['flexform']['showTemplate']) && ($this->settings['flexform']['showTemplate'] == 'MiniVCard')) {
                // Ignore error for mini vcard
                $assignedValues = [
                    'institution' => false,
                    'childInstitutions' => [],
                    'mapMarkers' => [],
                    'openingHours' => [],
                    'content' => '',
                ];
            } else {
                // Page not found
                $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $GLOBALS['TYPO3_REQUEST'],
                    'Einrichtung konnte nicht gefunden werden',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
                throw new ImmediateResponseException($response);
            }
        }

        /** @var ModifyAssignedValuesForInstitutionEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyAssignedValuesForInstitutionEvent($this, $assignedValues, $this->request)
        );

        $assignedValues = $event->getAssignedValues();

        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function redirectAction():ResponseInterface
    {
        $nkci = $this->request->getParsedBody()['nkci'] ?? $this->request->getQueryParams()['nkci'] ?? null;

        if ((int)$nkci) {
            $this->uriBuilder->reset()->setTargetPageUid($this->settings['flexform']['pidSingle']);
            $uri = $this->uriBuilder->uriFor('show', ['uid' => $nkci]);
        } else {
            $uri ='/';
        }
        return $this->redirectToURI($uri);
    }

    /**
     * Returns the NAPU includes for detail view
     * @return string[]
     */
    private function getDetailIncludes():array
    {
        return [
            Institution::RELATION_ADDRESS,
            Institution::RELATION_INSTITUTION_TYPE,
            Institution::RELATION_MAP_CHILDREN,
            Institution::RELATION_PARENT_INSTITUTIONS,
            Institution::RELATION_TEAMS => [
                Team::RELATION_FUNCTIONS => [
                    PersonFunction::RELATION_PERSON,
                    PersonFunction::RELATION_FUNCTION_TYPE,
                    PersonFunction::RELATION_AVAILABLE_FUNCTION,
                ],
                Team::RELATION_FUNCTION_TYPE,
            ],
        ];
    }

    /**
     * Create marker array
     *
     * @param Institution $institution
     * @param bool
     * @return array
     */
    private function getMapMarkers($institution, $asyncInfo = true)
    {
        $mapMarkers = [];

        if ($institution->getMapVisibility() == true) {
            $this->createMarker($mapMarkers, $institution, $asyncInfo);
            if ($institution->getMapChildren()) {
                /** @var Institution $childInstitution */
                foreach ($institution->getMapChildren() as $childInstitution) {
                    $this->createMarker($mapMarkers, $childInstitution, $asyncInfo);
                }
            }
        }

        return $mapMarkers;
    }

    /**
     * @param Institution $institution
     * @param string $template
     * @return string
     */
    public function renderMapInfo($institution, $template = 'Institution/MapInfo')
    {
        if ($this->standaloneView == false) {
            // Init standalone view
            $config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);

            $this->standaloneView->setLayoutRootPaths(
                $this->getPath($config, 'layout', 'nkc_address')
            );
            $this->standaloneView->setPartialRootPaths(
                $this->getPath($config, 'partial', 'nkc_address')
            );
            $this->standaloneView->setTemplateRootPaths(
                $this->getPath($config, 'template', 'nkc_address')
            );

            $this->standaloneView->setTemplate($template);
        }

        $this->standaloneView->assignMultiple(['institution' => $institution,
            'settings' => $this->settings]);

        $this->standaloneView->setRequest($this->middleWareRequest);

        return $this->standaloneView->render();
    }


    /**
     * Add a marker, when geo-coordinates are available
     *
     * @param array $mapMarkers
     * @param Institution $institution
     * @param bool $asyncInfo
     */
    public function createMarker(&$mapMarkers, $institution, $asyncInfo = true)
    {
        // Get type of institution
        if ($institution->getInstitutionType() instanceof InstitutionType) {
            $typeId = $institution->getInstitutionType()->getId();
        } else {
            $typeId = 0;
        }

        /** @var Address $address */
        $address = $institution->getAddress();
        if ($address instanceof Address) {
            // Check geo coordinates
            if ($address->getLatitude() && $address->getLongitude()) {
                $marker = [
                    'object' => 'i',
                    'id'    => $institution->getId(),
                    'title' => $institution->getName(),
                    'lat' 	=> $address->getLatitude(),
                    'lon' 	=> $address->getLongitude(),
                    'info' 	=> $asyncInfo ? '' : $this->renderMapInfo($institution),
                    'type'  => 'institution-' . $typeId,
                    'icon' 	=> $this->getIcon($typeId),
                ];
                $mapMarkers[] = $marker;
            }
        }
    }

    /**
     * @param array $institutionList
     * @param array $config
     * @return string
     * @return array|mixed
     */
    public function retrieveMarkerInfo($institutionList, $config)
    {
        parent::initializeAction();

        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);

        $this->settings = $config['plugin']['tx_nkcaddress_institution']['settings'];

        $this->settings['flexform'] = $this->settings['flexformDefault'];

        $query = new InstitutionQuery();

        $query->setInclude([Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE]);

        $query->setInstitutions($institutionList);

        $query->setPageSize(100);

        $institutions = $this->institutionRepository->get($query);

        $result = '';

        foreach ($institutions as $institution) {
            $result .= $this->renderMapInfo($institution, 'Institution/AsyncMapInfo');
        }

        return $result;
    }

    /**
     * @param $id
     * @return string
     */
    public function mapInstitutionTypeToKeyword($id)
    {
        return !empty($this->settings['mapping']['institutionIcon'][$id]) ? $this->settings['mapping']['institutionIcon'][$id] : 'default';
    }

    /**
     * @param int $typeId
     * @return string
     */
    private function getIcon($typeId)
    {
        $filename = PathUtility::getPublicResourceWebPath(sprintf($this->settings['institutionIconName'], $this->mapInstitutionTypeToKeyword($typeId)));
        if (!is_file(GeneralUtility::getFileAbsFileName($filename))) {
            $filename = PathUtility::getPublicResourceWebPath(sprintf($this->settings['institutionIconName'], 'default'));
        }

        return $filename;
    }

    /**
     * Get all child institutions, clustered by type
     *
     * @param $institution
     * @return array
     */
    private function getChildInstitutions($institution)
    {
        $groupedChildInstitutions = [];

        $query = new InstitutionQuery();

        $query->setParentInstitutions([$institution->getId()]);

        $query->setInclude([ Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE ]);

        $query->setPageSize(99);

        $childInstitutions = $this->institutionRepository->get($query);

        if ($childInstitutions) {
            foreach ($childInstitutions as $childInstitution) {
                if ($childInstitution->getInstitutionType()) {
                    $groupedChildInstitutions[$childInstitution->getInstitutionType()->getName()][] = $childInstitution;
                } else {
                    $groupedChildInstitutions['Sonstige'][] = $childInstitution;
                }
            }
        }

        ksort($groupedChildInstitutions);

        return $groupedChildInstitutions;
    }

    /**
     * @param $query
     * @return void
     */
    private function setFilter($query)
    {
        // Filter institutions
        if (!empty($this->settings['flexform']['institutionCollection'])) {
            $this->setInstitutionFilter($query, $this->settings['flexform']['institutionCollection'], $this->settings['flexform']['selectOption']);
        }

        // Filter by type
        if (!empty($this->settings['flexform']['institutionType'])) {
            $this->setInstitutionTypeFilter($query, $this->settings['flexform']['institutionType']);
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

        // Sorting
        if (!empty($this->settings['flexform']['sortOption'])) {
            $query->setSort($this->settings['flexform']['sortOption']);
        }
    }

    /**
     * @param InstitutionQuery $query
     * @param SearchRequest $searchRequest
     */
    private function setUserFilters($query, $searchRequest)
    {
        if (strlen($searchRequest->getSearch()) > 2) {
            $query->setQuery($searchRequest->getSearch());
        }

        if (strlen($searchRequest->getCity()) > 2) {
            if (is_numeric($searchRequest->getCity())) {
                $query->setZipCodes([$searchRequest->getCity()]);
            } else {
                $query->setCities(GeneralUtility::trimExplode(',', $searchRequest->getCity()));
            }
        }

        if ($searchRequest->getCategory() > 0) {
            if ($categories = $query->getCategories()) {
                if (in_array($searchRequest->getCategory(), $categories)) {
                    $query->setCategories([$searchRequest->getCategory()]);
                }
            } else {
                $query->setCategories([$searchRequest->getCategory()]);
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

        if (!empty($this->settings['filter']['categoryCollection'])) {
            $categories = GeneralUtility::trimExplode(',', $this->settings['filter']['categoryCollection']);
            foreach ($categories as $categoryUid) {
                $category = $this->categoryRepository->findByUid($categoryUid);
                if ($category instanceof Category) {
                    $filter['categories'][] = [
                        'uid'   => $category->getUid(),
                        'label' => $category->getTitle(),
                    ];
                }
            }
        }

        return $filter;
    }

    /**
     * Prepare opening hours for output
     *
     * @param Institution $institution
     * @return array
     */
    private function prepareOpeningHours($institution)
    {
        $openingHoursTable = [];
        /** @var OpeningHours $openingHours */
        foreach ($institution->getOpeningHours() as $openingHours) {
            if (!isset($openingHoursTable[$openingHours->getDayOfWeek()])) {
                $openingHoursTable[$openingHours->getDayOfWeek()] = [
                    'day'   => $openingHours->getDayOfWeek(),
                    'name'  => '',
                    'items' => [],
                ];
            }

            if ($openingHours->getName()) {
                $openingHoursTable[$openingHours->getDayOfWeek()]['name'] = $openingHours->getName();
            }

            $openingHoursTable[$openingHours->getDayOfWeek()]['items'][] = $openingHours;
        }

        $max = 0;
        foreach ($openingHoursTable as $openingHours) {
            $size = count($openingHours['items']);
            if ($size > $max) {
                $max = $size;
            }
        }

        $size = $max;

        foreach ($openingHoursTable as $index => $openingHours) {
            if (count($openingHours['items']) < $size) {
                for ($i = count($openingHours['items']); $i < $max; $i++) {
                    $openingHoursTable[$index]['items'][] = false;
                }
            }
        }

        return $openingHoursTable;
    }

    /**
     * Instantiate TSFE (for link viewhelper)
     *
     * @return mixed|LoggerAwareInterface|SingletonInterface|TypoScriptFrontendController
     */
    private function getTSFE()
    {
        $context = GeneralUtility::makeInstance(Context::class, []);
        $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $firstSite = array_key_first($sites);
        $siteLanguages = $sites[$firstSite]->getAllLanguages();
        $pageArguments = GeneralUtility::makeInstance(PageArguments::class, $sites[$firstSite]->getRootPageId(), 0, []);
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        return GeneralUtility::makeInstance(TypoScriptFrontendController::class, $context, $sites[$firstSite], $siteLanguages[0], $pageArguments, $frontendUser);
    }

    /**
     * @param ServerRequestInterface $request
     * @return void
     */
    public function setMiddleWareRequest(ServerRequestInterface  $request)
    {
        $this->middleWareRequest = $request;
    }

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }
}

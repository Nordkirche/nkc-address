<?php

namespace Nordkirche\NkcAddress\Controller;

use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Repository\AvailableFunctionRepository;
use Nordkirche\Ndk\Domain\Repository\FunctionTypeRepository;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Domain\Repository\InstitutionTypeRepository;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcBase\Controller\BaseController;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class MapController extends BaseController
{

    /**
     * @var \Nordkirche\Ndk\Domain\Repository\InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * @var \Nordkirche\Ndk\Domain\Repository\InstitutionTypeRepository
     */
    protected $institutionTypeRepository;

    /**
     * @var \Nordkirche\Ndk\Domain\Repository\PersonRepository
     */
    protected $personRepository;

    /**
     * @var \Nordkirche\Ndk\Domain\Repository\AvailableFunctionRepository
     */
    protected $availableFunctionRepository;

    /**
     * @var \Nordkirche\Ndk\Domain\Repository\FunctionTypeRepository
     */
    protected $functionTypeRepository;

    /**
     * @var \Nordkirche\NkcAddress\Controller\InstitutionController
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $institutionController;

    /**
     * @var \Nordkirche\NkcAddress\Controller\PersonController
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $personController;

    /**
     * @var \Nordkirche\Ndk\Service\NapiService
     */
    protected $napiService;

    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * @var array
     */
    protected $facets = [];

    public function initializeAction()
    {
        parent::initializeAction();
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
        $this->institutionTypeRepository = $this->api->factory(InstitutionTypeRepository::class);
        $this->personRepository = $this->api->factory(PersonRepository::class);
        $this->availableFunctionRepository = $this->api->factory(AvailableFunctionRepository::class);
        $this->functionTypeRepository = $this->api->factory(FunctionTypeRepository::class);
        $this->napiService = $this->api->factory(NapiService::class);

        $this->institutionController->initializeAction();
        $this->personController->initializeAction();
    }

    /**
     * Show map action
     */
    public function showAction()
    {
        $this->createView();
    }

    /**
     * Show map and list action
     *
     * @param int $currentPage
     */
    public function listAction($currentPage = 1)
    {
        $this->createView($currentPage);
    }

    /**
     * initializeDataAction
     */
    public function initializeDataAction()
    {
        $this->defaultViewObjectName = JsonView::class;
    }


    /**
     * @param int $forceReload
     * @return string
     */
    public function dataAction($forceReload = 0)
    {
        $this->view->setVariablesToRender(['json']);

        // Get current cObj
        $cObj = $this->configurationManager->getContentObject();

        $cacheInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('tx_nkgooglemaps');

        $mapMarkerJson = $cacheInstance->get($this->getCacheKey($cObj));

        // Check if new rendering is required
        if (($forceReload === 1) && trim($mapMarkerJson)) {
            try {
                $mapMarkers = json_decode($mapMarkerJson);
                if ($mapMarkers->crdate > time() - 3600) {
                    $forceReload = 0;
                }
            } catch (\Exception $e) {
                $forceReload = 1;
            }
        }

        if (!trim($mapMarkerJson) || $forceReload) {
            list($limit, $mapItems, $recordCount) = $this->getMapItems($this->settings, true);

            $mapMarkerJson = json_encode(['crdate' => time(), 'data' => $this->createMarkers($mapItems)]);

            $cacheInstance->set($this->getCacheKey($cObj), $mapMarkerJson);
        }

        $this->view->assignMultiple(['json' => json_decode($mapMarkerJson, TRUE)]);
    }

    /**
     * @param $content
     * @param $forceReload
     */
    public function buildCache($content, $forceReload)
    {
        $cObj = new \StdClass();
        $cObj->data = $content;

        $cacheInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('tx_nkgooglemaps');

        $mapMarkerJson = $cacheInstance->get($this->getCacheKey($cObj));

        // Check if new rendering is required
        if (($forceReload === 1) && trim($mapMarkerJson)) {
            try {
                $mapMarkers = json_decode($mapMarkerJson);
                if ($mapMarkers->crdate > time() - 3600) {
                    $forceReload = 0;
                }
            } catch (\Exception $e) {
                $forceReload = 1;
            }
        }

        if (!trim($mapMarkerJson) || $forceReload) {

            // Get TS Config and add to local settings
            $tsConfig = $this->getTypoScriptConfiguration();
            $this->settings['institutionIconName'] = $tsConfig['plugin']['tx_nkcaddress_map']['settings']['institutionIconName'];
            $this->settings['personIconName'] = $tsConfig['plugin']['tx_nkcaddress_map']['settings']['personIconName'];
            $this->settings['mapping'] = $tsConfig['plugin']['tx_nkcaddress_map']['settings']['mapping'];

            $this->institutionController->setSettings($this->settings);
            $this->personController->setSettings($this->settings);

            list($limit, $mapItems, $recordCount) = $this->getMapItems($this->settings, true);

            $mapMarkerJson = json_encode(['crdate' => time(), 'data' => $this->createMarkers($mapItems)]);

            $cacheInstance->set($this->getCacheKey($cObj), $mapMarkerJson);
        }
    }

    /**
     * initializePaginatedDataAction
     */
    public function initializePaginatedDataAction()
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * @param int $page
     * @return string
     */
    public function paginatedDataAction($page = 1)
    {
        $this->view->setVariablesToRender(['json']);

        // Manually activation of pagination mode
        $this->settings['flexform']['paginate']['mode'] = 1;

        // Get current cObj
        $cObj = $this->configurationManager->getContentObject();

        $cacheInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('tx_nkgooglemaps');

        $mapMarkerJson = $cacheInstance->get($this->getCacheKey($cObj));

        if (trim($mapMarkerJson)) {
            $this->view->assignMultiple(['json' => json_decode($mapMarkerJson, TRUE)]);
        } else {
            list($limit, $mapItems, $recordCount) = $this->getMapItems($this->settings, false, $page);

            $mapMarkers = $this->createMarkers($mapItems);

            $this->view->assignMultiple(['json' => ['data' => $mapMarkers]]);
        }
    }

    /**
     * @param $currentPage
     */
    private function createView($currentPage = 1)
    {
        list($limitExceeded, $mapItems, $recordCount) = $this->getMapItems($this->settings, false, $currentPage);

        if ($limitExceeded) {
            // Too many objects: async loading

            $cObj = $this->configurationManager->getContentObject();

            if (!$this->settings['flexform']['stream']) {
                // In einem Rutsch nachladen
                $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setTargetPageType($this->settings['ajaxTypeNum'])
                    ->setArguments(['tx_nkcaddress_map[action]' => 'data', 'uid' => $cObj->data['uid']]);

                $this->view->assign('requestUri', $this->uriBuilder->build());
            } else {
                // Sukzessive nachladen
                $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setTargetPageType($this->settings['ajaxTypeNum'])
                    ->setUseCacheHash(false)
                    ->setArguments(['tx_nkcaddress_map[action]' => 'paginatedData', 'uid' => $cObj->data['uid']]);

                $this->view->assign('streamUri', $this->uriBuilder->build());
            }
        } else {
            $mapMarkers = $this->createMarkers($mapItems);

            $this->view->assign('mapMarkers', $mapMarkers);
        }

        list($currentPage, $pageSize) = $this->getPagination($currentPage, $this->settings);

        $numPages = ceil($recordCount / $pageSize);

        // Get current cObj
        $cObj = $this->configurationManager->getContentObject();

        $this->view->assignMultiple([   'mapItems'      =>  $mapItems,
                                        'content'       => $cObj->data,
                                        'currentPage'   => $currentPage,
                                        'numPages'      => $numPages,
                                        'recordCount'   => $recordCount,
                                        'nextPage'      => ($currentPage < $numPages) ? $currentPage + 1 : false
        ]);

        if ($this->settings['flexform']['showFilter'] == 1) {
            $this->view->assign('facets', $this->getFacets());
        }
    }

    /**
     * @param $mapItems
     * @return array
     */
    private function createMarkers($mapItems)
    {
        $markers = [];

        foreach ($mapItems as $item) {
            if ($item instanceof Institution) {
                $this->institutionController->createMarker($markers, $item);
            } elseif ($item instanceof Person) {
                $personMarker = $this->personController->createSingleMarker($item);
                if ($personMarker) {
                    $markers[] = $personMarker;
                }
            }
        }
        return $markers;
    }

    /**
     * @param array $settings
     * @param bool $allItems
     * @return array
     */
    public function getMapItems($settings, $allItems = false, $page = 1)
    {
        $mapItems = [];

        $limitExceeded = false;

        $recordCount = 0;

        list($page, $pageSize) = $this->getPagination($page, $settings);

        if ($settings['flexform']['allInstitutions'] == 1) {
            // All Institutions
            $query = $this->getInstitutionQuery($pageSize, $page);

            if ($this->getInstitutionsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                $limitExceeded = true;
            }
        } else {
            // Selected institutions
            if ($settings['flexform']['institutionCollection']) {
                $query = $this->getInstitutionQuery($pageSize, $page);

                // Filter institutions
                $this->setInstitutionFilter(
                    $query,
                    $settings['flexform']['institutionCollection'],
                    $settings['flexform']['selectInstitutionOption']
                );

                // Add category filter
                $this->setCategoryFilter($query, $settings['flexform']['categories']);

                if ($this->getInstitutionsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }

            // Institutions by type
            if ($settings['flexform']['institutionType']) {
                $query = $this->getInstitutionQuery($pageSize, $page);

                // Filter by type
                $this->setInstitutionTypeFilter($query, $settings['flexform']['institutionType']);

                // Add category filter
                $this->setCategoryFilter($query, $settings['flexform']['categories']);

                if ($this->getInstitutionsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }

            // Institutions by category
            if ($settings['flexform']['categories'] &&
                (!$settings['flexform']['institutionType'] && !$settings['flexform']['institutionCollection'])
            ) {
                $query = $this->getInstitutionQuery($pageSize, $page);

                // Add category filter
                $this->setCategoryFilter($query, $settings['flexform']['categories']);

                if ($this->getInstitutionsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }
        }

        if ($settings['flexform']['allPersons'] == 1) {
            // All people
            $query = $this->getPersonQuery($pageSize, $page);

            if ($this->getPersonsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                $limitExceeded = true;
            }
        } else {

            // People by function type
            if ($settings['flexform']['functionType']) {
                $query = $this->getPersonQuery($pageSize, $page);

                // Filter by type
                $this->setFunctionTypeFilter($query, $settings['flexform']['functionType']);

                if ($this->getPersonsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }

            // People by available function
            if ($settings['flexform']['availableFunction']) {
                $query = $this->getPersonQuery($pageSize, $page);

                // Filter by available function
                $this->setAvailableFunctionFilter($query, $settings['flexform']['availableFunction']);

                if ($this->getPersonsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }

            // Selected people
            if ($settings['flexform']['personCollection']) {
                $query = $this->getPersonQuery($pageSize, $page);

                // Filter by type
                $this->setPersonFilter($query, $settings['flexform']['personCollection']);

                if ($this->getPersonsByQuery($query, $allItems, $pageSize, $mapItems, $recordCount) === false) {
                    $limitExceeded = true;
                }
            }
        }

        return [$limitExceeded, $mapItems, $recordCount];
    }

    /**
     * @param $settings
     * @return int
     */
    private function getNumberOfFilters($settings)
    {
        $filter = 0;

        if ($settings['flexform']['allInstitutions']) {
            $filter++;
        } else {
            if ($settings['flexform']['institutionCollection']) {
                $filter++;
            }
            if ($settings['flexform']['institutionCollection']) {
                $filter++;
            }
            if ($settings['flexform']['categories'] && (!$settings['flexform']['institutionType'] && !$settings['flexform']['institutionCollection'])) {
                $filter++;
            }
        }
        if ($settings['flexform']['allPersons'] == 1) {
            $filter++;
        } else {
            if ($settings['flexform']['functionType']) {
                $filter++;
            }
            if ($settings['flexform']['availableFunction']) {
                $filter++;
            }
            if ($settings['flexform']['personCollection']) {
                $filter++;
            }
        }
        return $filter;
    }

    /**
     * @param int $currentPage
     * @param array $settings
     * @return array
     */
    private function getPagination($currentPage = 1, $settings = [])
    {
        $pageNumber = $currentPage;

        $filters = $this->getNumberOfFilters($settings);

        if ((int)$settings['flexform']['paginate']['mode'] > 0) {
            // Pagination is active: use page limit
            $limit = $settings['flexform']['paginate']['itemsPerPage'] ?: $settings['paginate']['itemsPerPage'];
            $pageSize = floor($limit / max($filters, 1));

            // Set page
            if ($pagination = $this->getWidgetValues()) {
                $pageNumber = $pagination['currentPage'];
            } elseif ($currentPage) {
                $pageNumber = $currentPage;
            }
        } else {
            // Pagination is inactive: use general limit
            $limit = $settings['flexform']['maxItems'] ?: $settings['maxItems'];
            $pageSize = floor($limit / 4);
        }

        return [$pageNumber, $pageSize];
    }

    /**
     * @param $query
     * @param $categories
     */
    private function setCategoryFilter($query, $categories)
    {
        if ($categories) {
            // Add category filter
            $query->setCategories(GeneralUtility::intExplode(',', $categories));
        }
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Nordkirche\Ndk\Domain\Query\InstitutionQuery
     */
    private function getInstitutionQuery($limit, $page = 1)
    {
        $query = new \Nordkirche\Ndk\Domain\Query\InstitutionQuery();
        if ($limit) {
            $query->setPageSize($limit);
        }
        $query->setPageNumber($page);
        $query->setInclude([Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE]);
        $query->setFacets([Institution::FACET_INSTITUTION_TYPE]);
        return $query;
    }

    /**
     * @param $query
     * @param $allItems
     * @param $limit
     * @param $mapItems
     * @param $recordCount
     * @return bool
     */
    private function getInstitutionsByQuery($query, $allItems, $limit, &$mapItems, &$recordCount)
    {
        if ($allItems) {
            $result = ApiService::getAllItems($this->institutionRepository, $query, [Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE]);
        } else {
            $result = $this->institutionRepository->get($query, [Institution::RELATION_ADDRESS, Institution::RELATION_INSTITUTION_TYPE]);
        }

        if ($result instanceof \Nordkirche\Ndk\Service\Result) {
            if ($result->getFacets()) {
                $this->addFacets($result->getFacets());
            }
            $recordCount += $result->getRecordCount();
        } else {
            $recordCount += count($result);
        }

        foreach ($result as $institution) {
            if ($institution instanceof Institution) {
                $mapItems['institution-' . $institution->getId()] = $institution;
            }
        }

        if ((!$allItems) && ($result->getRecordCount() > $limit)) {
            return false;
        }

        return true;
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Nordkirche\Ndk\Domain\Query\PersonQuery
     */
    private function getPersonQuery($limit, $page = 1)
    {
        $query = new \Nordkirche\Ndk\Domain\Query\PersonQuery();

        if ($limit) {
            $query->setPageSize($limit);
        }

        $query->setPageNumber($page);

        $query->setInclude([Person::RELATION_FUNCTIONS,
                            Person::RELATION_FUNCTIONS => [
                                PersonFunction::RELATION_INSTITUTION => [
                                    Institution::RELATION_ADDRESS,
                                    Institution::RELATION_INSTITUTION_TYPE
                                ],
                                PersonFunction::RELATION_AVAILABLE_FUNCTION,
                                PersonFunction::RELATION_FUNCTION_TYPE,
                                PersonFunction::RELATION_INSTITUTION,
                                PersonFunction::RELATION_ADDRESS]
                            ]);

        $query->setFacets([Person::FACET_FUNCTION_TYPE]);

        return $query;
    }

    /**
     * @param $query
     * @param $allItems
     * @param $limit
     * @param $mapItems
     * @param $recordCount
     * @return bool
     */
    private function getPersonsByQuery($query, $allItems, $limit, &$mapItems, &$recordCount)
    {
        if ($allItems) {
            $result = ApiService::getAllItems($this->personRepository, $query);
        } else {
            $result = $this->personRepository->get($query);
        }

        if ($result instanceof \Nordkirche\Ndk\Service\Result) {
            if ($result->getFacets()) {
                $this->addFacets($result->getFacets());
            }
            $recordCount += $result->getRecordCount();
        } else {
            $recordCount = count($result);
        }

        foreach ($result as $person) {
            if ($person instanceof Person) {
                $mapItems['person-' . $person->getId()] = $person;
            }
        }

        if ((!$allItems) && ($result->getRecordCount() > $limit)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $facets
     */
    private function addFacets($facets)
    {
        foreach ($facets as $facetKey => $facetValues) {
            if (is_array($this->facets[$facetKey])) {
                foreach ($facetValues as $id) {
                    $added = false;
                    list($newFacetType, $newFacetValue) = each($id);
                    foreach ($this->facets[$facetKey] as $index => $facetValue) {
                        list($facetValueType, $facetValueCount) = each($facetValue);
                        if ($newFacetType == $facetValueType) {
                            $this->facets[$facetKey][$index][$newFacetType] += $newFacetValue;
                            $added = true;
                        }
                    }
                    if (!$added) {
                        $this->facets[$facetKey][] = $id;
                    }
                }
            } else {
                $this->facets[$facetKey] = $facetValues;
            }
        }
    }

    /**
     * @return array
     */
    private function getFacets()
    {
        $facetsArray = [];

        // Resolve facets
        foreach ($this->facets as $facet_type => $facets) {
            if ($facet_type == 'institution_types') {
                $objects = ApiService::getAllItems(
                    $this->institutionTypeRepository,
                    new \Nordkirche\Ndk\Domain\Query\PageQuery()
                );
            } elseif ($facet_type == 'functions') {
                $objects = ApiService::getAllItems(
                    $this->functionTypeRepository,
                    new \Nordkirche\Ndk\Domain\Query\PageQuery()
                );
            } else {
                $objects = [];
            }

            if (count($objects)) {
                foreach ($facets as $facet) {
                    list($id, $number) = each($facet);
                    foreach ($objects as $object) {
                        if ($object->getId() == $id) {
                            $facetsArray[$facet_type][$id] = $object->getLabel() . sprintf(' (%s)', $number);
                        }
                    }
                }
            }
            asort($facetsArray[$facet_type]);
        }

        return $facetsArray;
    }

    /**
     * @return string
     */
    private function getCacheKey($cObj)
    {
        $key = 'tx_nkcaddress_map--dataAction--' . $cObj->data['tstamp'];
        return $cObj->data['uid'] . '--' . md5($key);
    }
}

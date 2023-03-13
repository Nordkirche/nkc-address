<?php

namespace Nordkirche\NkcAddress\Service;

use TYPO3\CMS\Core\SingletonInterface;
use Nordkirche\Ndk\Api;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use Nordkirche\NkcBase\Exception\ApiException;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Query\InstitutionQuery;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Anno v. Heimburg <avonheimburg@agenturwerft.de>, Agenturwerft GmbH
 */
class InstitutionRelationService implements SingletonInterface
{
    const TYPE_MEMBER = 1;

    const TYPE_PARTNER = 2;

    const TYPE_SUPERIOR_INSTITUTION = 3;

    const TYPE_EQUAL_INSTITUTION = 4;

    const DIRECT_CHILDREN = 1;

    const ALL_CHILDREN = 2;

    const NO_CHILDREN = 0;

    /**
     * @var CacheManager the cache frontend
     */
    protected $cache;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var NapiService
     */
    protected $napiService;

    /**
     * @var InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * @throws NoSuchCacheException
     */
    public function initializeObject()
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->cache = $cacheManager->getCache('cache_institution_relation');
    }

    /**
     * InstitutionRelationService constructor.
     * @throws NoSuchCacheException
     * @throws ApiException
     */
    public function __construct()
    {
        $this->initializeObject();
        $this->api = ApiService::get();
        $this->napiService = $this->api->factory(NapiService::class);
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
    }

    /**
     * Get the child institution uid's of a given institution
     *
     * Includes the given institution's id.
     *
     * @param Institution $institution
     * @param int $childType
     *
     * @return array
     */
    public function getChildInstitutionIds(Institution $institution, $childType)
    {
        $cacheKey = 'institution-' . $institution->getId() . '-' . $childType;
        $childUids = [];
        if ($this->cache->has($cacheKey)) {
            $childUids = $this->cache->get($cacheKey);
        } else {
            $children = [];
            switch ($childType) {
                case self::DIRECT_CHILDREN:
                    $this->getChildInstitutions([$institution->getId()], $children, false);
                    break;
                case self::ALL_CHILDREN:
                    $this->getChildInstitutions([$institution->getId()], $children, true);
                    break;
            }

            foreach ($children as $child) {
                /** @var $child \Nordkirche\Ndk\Domain\Model\Institution\Institution */
                $childUids[] = $child->getId();
            }

            // always include self
            $childUids[] = $institution->getId();

            $this->cache->set($cacheKey, $childUids, [], 60*60*24*30);
        }
        return $childUids;
    }

    /**
     * Look up whether a given person is a member of an institution or the institutions below
     *
     * @param Institution $institution
     * @param Person $person
     * @param int $childType
     *
     * @return bool
     */
    public function isMember(Institution $institution, Person $person, $childType = self::ALL_CHILDREN)
    {
        $institutionUids = $this->getChildInstitutionIds($institution, $childType);

        $isMember = false;
        /** @var PersonFunction $role */
        foreach ($person->getFunctions() as $role) {
            if ($role->getInstitution() instanceof Institution) {
                $institutionUid = $role->getInstitution()->getId();
                if (in_array($institutionUid, $institutionUids)) {
                    $isMember = true;
                    break;
                }
            }
        }

        return $isMember;
    }

    /**
     * Look up whether a given person is a member of an institution or the institutions below
     *
     * @param Institution $institution
     * @param Institution $member
     *
     * @return bool
     */
    public function isMemberInstitution(Institution $institution, Institution $member)
    {
        $institutionUids = $this->getChildInstitutionIds($institution, self::TYPE_MEMBER);

        return in_array($member->getId(), $institutionUids);
    }

    /**
     * Get all child institutions
     *
     * @param array $institutionIds
     * @param mixed $institutions
     * @param bool $recursive
     */
    public function getChildInstitutions($institutionIds, &$institutions, $recursive = false)
    {
        $query = new InstitutionQuery();

        $query->setParentInstitutions($institutionIds);

        $query->setInclude([]);

        $query->setPageSize(499);

        $result = $this->institutionRepository->get($query);

        if ($result->count()) {
            $parentIdList = [];
            foreach ($result as $subinstitution) {
                $institutions[] = $subinstitution;
                $parentIdList[] = $subinstitution->getId();
            }
            if ($recursive) {
                $this->getChildInstitutions($parentIdList, $institutions, $recursive);
            }
        }
    }
}

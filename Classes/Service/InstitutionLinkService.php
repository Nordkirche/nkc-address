<?php

namespace Nordkirche\NkcAddress\Service;

use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class InstitutionLinkService implements SingletonInterface
{
    /**
     * @var InstitutionRelationService
     */
    protected $institutionRelationService;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * @var array ts-settings
     */
    protected $settings = [];

    /**
     * @param ConfigurationManagerInterface $cm
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $cm)
    {
        $this->configurationManager = $cm;
        $this->settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'nkcaddress',
            'institution'
        );
    }

    /**
     * @param InstitutionRelationService $institutionRelationService
     * @return void
     */
    public function injectInstitutionRelationService(InstitutionRelationService $institutionRelationService): void
    {
        $this->institutionRelationService = $institutionRelationService;
    }

    /**
     * @throws \Nordkirche\NkcBase\Exception\ApiException
     */
    public function __construct()
    {
        $this->api = ApiService::get();
        $this->napiService = $this->api->factory(NapiService::class);
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
    }

    /**
     * @param Institution $targetInstitution
     *
     * @return bool
     */
    public function isInternalLink($targetInstitution)
    {
        if ($targetInstitution->getId() == $this->settings['institutionUid']) {
            return true;
        }

        if ((int)($this->settings['institutionUid']) > 0) {
            // get the main institution of the current website
            $rootBkInstitutionUid = (int)($this->settings['institutionUid']);

            /** @var $rootBkInstitution \Nordkirche\Ndk\Domain\Model\Institution\Institution */
            $rootBkInstitution = $this->institutionRepository->getById($rootBkInstitutionUid, []);

            $institutionUids = [];

            switch ($this->settings['linkChildInstitutionsInternal']) {
                case 'none':
                    // only the root institution will be linked locally
                    $institutionUids[] = $rootBkInstitution->getId();
                    break;
                case 'directMembers':
                    foreach ($targetInstitution->getMembers() as $institution) {
                        $institutionUids[] = $institution->getId();
                    }
                    break;
                case 'directChildren':
                    // only the direct children will be linked
                    $institutionUids = $this->institutionRelationService->getChildInstitutionIds($rootBkInstitution, InstitutionRelationService::DIRECT_CHILDREN);
                    break;
                case 'allChildren':
                default:
                    $institutionUids = $this->institutionRelationService->getChildInstitutionIds($rootBkInstitution, InstitutionRelationService::ALL_CHILDREN);
                    break;
            }

            if (!empty($this->settings['linkSpecificInstitutionsInternal'])) {
                $manualInstitutionUids = GeneralUtility::intExplode(',', $this->settings['linkSpecificInstitutionsInternal'], true);
                $institutionUids = array_merge($institutionUids, $manualInstitutionUids);
            }

            if (!empty($institutionUids)) {
                //if the target institution is allowed to be linked internal -> return true and render internal link
                switch ($this->settings['linkChildInstitutionsInternal']) {
                    case 'directMembers':
                        if (in_array($this->settings['institutionUid'], $institutionUids)) {
                            return true;
                        }
                        break;
                    default:
                        if (in_array($targetInstitution->getId(), $institutionUids)) {
                            return true;
                        }
                }
            }

            return false;
        }
        // if we have no root institution id, fall back to internal linking
        return true;
    }

}

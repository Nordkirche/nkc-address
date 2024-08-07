<?php

namespace Nordkirche\NkcAddress\ViewHelpers;

use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Domain\Repository\PersonRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcAddress\Service\InstitutionLinkService;
use Nordkirche\NkcAddress\Service\InstitutionRelationService;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Build a link for a given person based on custom rules
 *
 * Class PersonLinkViewHelper
 */
class PersonLinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var InstitutionLinkService
     */
    protected $institutionLinkService;

    /**
     * @var InstitutionRelationService
     */
    protected $institutionRelations;

    /**
     * @var InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * @var PersonRepository
     */
    protected $personRepository;

    /**
     * tag-type to render (overwrites the core tagName 'div' )
     * @var string
     */
    protected $tagName = 'a';

    /**
     * basePath for institutionLink --- default (internal): ''
     * @var string
     */
    protected $basePath = '';

    /**
     * The configuration of nk_address
     * @var array
     */
    protected $addressConfig = [];

    /**
     * TypoScript-Settings of nk_address
     * @var array
     */
    protected $settings = [];

    /**
     * @param ConfigurationManagerInterface $cm
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $cm)
    {
        $this->configurationManager = $cm;
        $this->settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'nkaddress',
            'institutioncards'
        );
    }

    /**
     * set standard values
     */
    public function __construct()
    {
        parent::__construct();
        $this->addressConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nkc_address');
        $this->api = ApiService::get();
        $this->napiService = $this->api->factory(NapiService::class);
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
        $this->personRepository = $this->api->factory(PersonRepository::class);
    }

    /**
     * initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('person', Person::class, 'target person', true);
        $this->registerArgument('classInternal', 'string', 'Add a class for internal links to the link tag', false, 'link-intern');
        $this->registerArgument('classExternal', 'string', 'Add a class for external links to the link tag', false, 'link-extern');
        $this->registerArgument('prependDefaultDomain', 'string', 'Prepend default domain in external links', false, '1');
    }

    /**
     * render the link
     *
     * @return string
     */
    public function render()
    {
        $includes = [
            Person::RELATION_FUNCTIONS => [
                PersonFunction::RELATION_ADDRESS,
                PersonFunction::RELATION_AVAILABLE_FUNCTION,
                PersonFunction::RELATION_INSTITUTION => [
                    Institution::RELATION_ADDRESS,
                    Institution::RELATION_INSTITUTION_TYPE,
                ],
            ],
        ];
        /** @var Person */
        $targetPerson = $this->arguments['person'];

        if (!($targetPerson  instanceof Person)) {
            /** @var Person $targetPerson */
            $targetPerson = $this->personRepository->getById($this->arguments['institution'], $includes);
        }

        $detailPid = $this->addressConfig['basePersonDetailPid'];
        $renderInternalLink = true;
        if ((int)($this->settings['institutionUid']) > 0) {
            /** @var Institution $hpInstitution */
            $hpInstitution = $this->institutionRepository->getById((int)($this->settings['institutionUid']));
            // check if person should be displayed on nordkirche.de (external) or on the bk institution website (internal)
            if ($hpInstitution) {
                $renderInternalLink = $this->institutionRelations->isMember($hpInstitution, $targetPerson);
                if ($renderInternalLink === true) {
                    $detailPid = $this->settings['bkPersonDetailPid'];
                } else {
                    if ($this->arguments['prependDefaultDomain'] === '1') {
                        $this->basePath = '//' . $this->addressConfig['defaultDomain'];
                    }
                }
            }
        }

        // target link path
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder->reset()->setTargetPageUid($detailPid)->uriFor('show', ['person' => $targetPerson->getId()], 'Person', 'nkaddress', 'PersonCards');
        $this->tag->addAttribute('href', $this->basePath . $uri);

        if ($renderInternalLink === true) {
            if (trim($this->arguments['classInternal'])) {
                $this->tag->addAttribute('class', $this->arguments['classInternal']);
            }
        } else {
            if (trim($this->arguments['classExternal'])) {
                $this->tag->addAttribute('class', $this->arguments['classExternal']);
            }
            $this->tag->addAttribute('target', '_blank');
        }

        // set link content
        $content = trim($this->renderChildren());
        if ($content == null || empty($content)) {
            $content = $targetPerson->getName()->getFormatted();
        }
        $this->tag->setContent($content);

        $this->tag->forceClosingTag(true);
        $output = $this->tag->render();

        return $output;
    }

    /**
     * @param InstitutionLinkService $institutionLinkService
     */
    public function injectInstitutionLinkService(InstitutionLinkService $institutionLinkService): void
    {
        $this->institutionLinkService = $institutionLinkService;
    }

    /**
     * @param InstitutionRelationService $institutionRelations
     */
    public function injectInstitutionRelations(InstitutionRelationService $institutionRelations): void
    {
        $this->institutionRelations = $institutionRelations;
    }
}

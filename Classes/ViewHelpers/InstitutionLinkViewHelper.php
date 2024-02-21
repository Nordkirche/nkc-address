<?php

namespace Nordkirche\NkcAddress\ViewHelpers;

use Nordkirche\Ndk\Api;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Nordkirche\NkcAddress\Service\InstitutionLinkService;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Repository\InstitutionRepository;
use Nordkirche\Ndk\Service\NapiService;
use Nordkirche\NkcBase\Service\ApiService;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Build a link for a given institution based on custom rules
 *
 * Class InstitutionLinkViewHelper
 */
class InstitutionLinkViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var NapiService
     */
    protected $napiService;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var InstitutionLinkService
     */
    protected $institutionLinkService;

    /**
     * @var InstitutionRepository
     */
    protected $institutionRepository;

    /**
     * tag-type to render (overwrites the core tagName 'div' )
     * @var string
     */
    protected $tagName = 'a';

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
            'nkcaddress',
            'institution'
        );
    }

    /**
     * set standard values
     */
    public function __construct()
    {
        parent::__construct();
        $this->api = ApiService::get();
        $this->napiService = $this->api->factory(NapiService::class);
        $this->institutionRepository = $this->api->factory(InstitutionRepository::class);
    }

    /**
     * initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('institution', Institution::class, 'target institution', true);
        $this->registerArgument('pageUid', 'integer', 'target page', false);
        $this->registerArgument('classInternal', 'string', 'Add a class for internal links to the link tag', false, 'link-intern');
        $this->registerArgument('classExternal', 'string', 'Add a class for external links to the link tag', false, 'link-extern');
        $this->registerArgument('forceInternalLink', 'bool', 'Forces the link to be rendered as an internal link', false, false);
        $this->registerArgument('checkSpecialVcards', 'bool', 'Support external links', false, false);
        $this->registerArgument('legacyLinkFormat', 'bool', 'Use legacy link format', false, false);
    }

    /**
     * render the link
     *
     *
     * @return string
     */
    public function render()
    {
        $includes = [ Institution::RELATION_MEMBERS, Institution::RELATION_MAP_CHILDREN, Institution::RELATION_PARENT_INSTITUTIONS ];

        $targetInstitution  = $this->arguments['institution'];

        if (!($targetInstitution  instanceof Institution)) {
            /** @var Institution $targetInstitution */
            $targetInstitution = $this->institutionRepository->getById($this->arguments['institution'], $includes);
        }

        $forceInternalLink = $this->arguments['forceInternalLink'];

        $checkSpecialVcards = (bool)$this->arguments['checkSpecialVcards'];

        $detailPid = false;

        $renderInternalLink = true;
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();

        if ($checkSpecialVcards && $targetInstitution->getVcardType() == 1) {
            if (is_numeric($targetInstitution->getVcardLink())) {
                $uri = $uriBuilder->reset()->setTargetPageUid($targetInstitution->getVcardLink())->build();
            } else {
                $uri = $targetInstitution->getVcardLink();
            }
        } elseif (!empty($this->settings['institutionUid']) && ((int)$this->settings['institutionUid'] > 0)) {

            // check if institution should be displayed on nordkirche.de (external) or on the local website (internal)
            $renderInternalLink = $forceInternalLink || $this->institutionLinkService->isInternalLink($targetInstitution);

            if ($renderInternalLink === true) {
                $detailPid = $this->arguments['pageUid'];
            } else {
                $uri = $this->settings['nordkircheUri'] . '?nkci=' . $targetInstitution->getId();
            }
        } else {
            $detailPid = $this->arguments['pageUid'];
        }

        // Build local link
        if ($detailPid) {
            if ($this->arguments['legacyLinkFormat'] == true) {
                // Use legacy link format
                $uri = $uriBuilder->reset()->setTargetPageUid($detailPid)->setArguments(['tx_nkaddress_institutioncards' => ['institution' => $targetInstitution->getId()]])->build();
            } else {
                // Use regular link format
                $uri = $uriBuilder->reset()->setTargetPageUid($detailPid)->setArguments(['tx_nkcaddress_institution' => ['uid' => $targetInstitution->getId()]])->build();
            }
        }

        $this->tag->addAttribute('href', $uri);

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

        $this->tag->addAttribute('title', $targetInstitution->getName());

        // set link content
        $content = trim($this->renderChildren());

        if ($content == null || empty($content)) {
            $content = htmlspecialchars($targetInstitution->getName());
        }
        $this->tag->setContent($content);

        $this->tag->forceClosingTag(true);

        $output = $this->tag->render();

        return $output;
    }

    public function injectInstitutionLinkService(InstitutionLinkService $institutionLinkService): void
    {
        $this->institutionLinkService = $institutionLinkService;
    }
}

<?php

namespace Nordkirche\NkcAddress\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
class InstitutionIconViewHelper extends AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('type', 'string', 'type', false, null);
        $this->registerArgument('mapping', 'array', 'mapping', false, []);
        $this->registerArgument('baseName', 'string', 'base name', false, '');
    }

    /**
     * @return string
     */
    public function render()
    {
        $type = $this->arguments['type'];
        $mapping = $this->arguments['mapping'];
        $baseName = $this->arguments['baseName'];

        if ($type === null) {
            $type = $this->renderChildren();
        }

        $iconName = !empty($mapping[$type]) ? $mapping[$type] : 'default';

        return $baseName ? sprintf($baseName, $iconName) : $iconName;
    }
}

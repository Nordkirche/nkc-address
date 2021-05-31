<?php

namespace Nordkirche\NkcAddress\ViewHelpers;

class InstitutionIconViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
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

        $iconName = $mapping[$type] ? $mapping[$type] : 'default';

        return $baseName ? sprintf($baseName, $iconName) : $iconName;
    }
}

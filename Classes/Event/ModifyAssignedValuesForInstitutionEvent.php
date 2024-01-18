<?php
declare(strict_types=1);

namespace Nordkirche\NkcAddress\Event;

use Nordkirche\NkcAddress\Controller\InstitutionController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyAssignedValuesForInstitutionEvent
{
    public function __construct(
        private readonly InstitutionController $controller,
        private array $assignedValues,
        private readonly Request $request,
    ) {
    }

    public function getAssignedValues(): array
    {
        return $this->assignedValues;
    }

    public function setAssignedValues(array $assignedValues): void
    {
        $this->assignedValues = $assignedValues;
    }

    public function getController(): InstitutionController
    {
        return $this->controller;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}

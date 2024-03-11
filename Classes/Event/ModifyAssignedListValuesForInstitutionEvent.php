<?php

declare(strict_types=1);

namespace Nordkirche\NkcAddress\Event;

use Nordkirche\NkcAddress\Controller\InstitutionController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyAssignedListValuesForInstitutionEvent
{
    public function __construct(
        private readonly InstitutionController $controller,
        private array $assignedListValues,
        private readonly Request $request
    ) {}

    public function getAssignedListValues(): array
    {
        return $this->assignedListValues;
    }

    public function setAssignedListValues(array $assignedListValues): void
    {
        $this->assignedListValues = $assignedListValues;
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

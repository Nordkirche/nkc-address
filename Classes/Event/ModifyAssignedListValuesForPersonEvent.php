<?php
declare(strict_types=1);

namespace Nordkirche\NkcAddress\Event;

use Nordkirche\NkcAddress\Controller\PersonController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyAssignedListValuesForPersonEvent
{
    public function __construct(
        private readonly PersonController $controller,
        private array $assignedListValues,
        private readonly Request $request
    ) {
    }

    public function getAssignedListValues(): array
    {
        return $this->assignedListValues;
    }

    public function setAssignedListValues(array $assignedListValues): void
    {
        $this->assignedListValues = $assignedListValues;
    }

    public function getController(): PersonController
    {
        return $this->controller;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}

<?php

declare(strict_types=1);

namespace Nordkirche\NkcAddress\Event;

use Nordkirche\Ndk\Domain\Query\PersonQuery;
use Nordkirche\NkcAddress\Controller\EventController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyPersonQueryEvent
{
    public function __construct(
        private readonly EventController $controller,
        private PersonQuery $personQuery,
        private readonly Request $request,
    ) {}

    public function getPersonQuery(): PersonQuery
    {
        return $this->personQuery;
    }

    public function setPersonQuery(PersonQuery $personQuery): void
    {
        $this->personQuery = $personQuery;
    }

    public function getController(): EventController
    {
        return $this->controller;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}

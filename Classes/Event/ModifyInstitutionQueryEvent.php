<?php

declare(strict_types=1);

namespace Nordkirche\NkcAddress\Event;

use Nordkirche\Ndk\Domain\Query\InstitutionQuery;
use Nordkirche\NkcAddress\Controller\InstitutionController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyInstitutionQueryEvent
{
    public function __construct(
        private readonly InstitutionController $controller,
        private InstitutionQuery $institutionQuery,
        private readonly Request $request
    ) {}

    public function getInstitutionQuery(): InstitutionQuery
    {
        return $this->institutionQuery;
    }

    public function setInstitutionQuery(InstitutionQuery $institutionQuery): void
    {
        $this->institutionQuery = $institutionQuery;
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

<?php
declare(strict_types=1);

namespace Nordkirche\NkcEvent\Event;

use Nordkirche\Ndk\Domain\Query\InstitutionQuery;
use Nordkirche\NkcEvent\Controller\EventController;
use TYPO3\CMS\Extbase\Mvc\Request;

final class ModifyInstitutionQueryEvent
{
    public function __construct(
        private readonly EventController $controller,
        private InstitutionQuery $institutionQuery,
        private readonly Request $request,
    ) {
    }

    public function getInstitutionQuery(): InstitutionQuery
    {
        return $this->institutionQuery;
    }

    public function setInstitutionQuery(InstitutionQuery $institutionQuery): void
    {
        $this->institutionQuery = $institutionQuery;
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

<?php

namespace Nordkirche\NkcAddress\Service;

use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;

class InstitutionService
{

    /**
     * Returns the persons assigned to the given institution by one or more functions in an array grouped and ordered by the function type
     *
     * @param $institution
     * @param $persons
     * @param $functionTypes
     * @return array
     */
    public static function groupPersonByRoleType($institution, $persons, $functionTypes)
    {
        $roleTypeGroupedPersons = [];

        foreach ($functionTypes as $genFunctionType) {
            /** @var Person $person */
            foreach ($persons as $person) {
                /** @var PersonFunction $personFunction */
                foreach ($person->getFunctions() as $personFunction) {
                    // is this function linked to institution?
                    $functionInstitution = $personFunction->getInstitution();
                    if ($functionInstitution && ($functionInstitution->getId() == $institution->getId())) {
                        if ($functionType = $personFunction->getFunctionType()) {
                            if ($genFunctionType->getId() == $functionType->getId()) {
                                if (!isset($roleTypeGroupedPersons[$functionType->getId()])) {
                                    $roleTypeGroupedPersons[$functionType->getId()] = [
                                        'name' => $functionType->getTitle(),
                                        'functions' => []
                                    ];
                                }
                                $roleTypeGroupedPersons[$functionType->getId()]['functions'][$person->getId()] = [
                                    'person' => $person,
                                    'function' => $personFunction
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $roleTypeGroupedPersons;
    }

    /**
     * Returns the persons assigned to the given institution by one or more functions in an array grouped by the available role
     *
     * @param $institution
     * @param $persons
     * @param array $mapping
     * @return array
     */
    public static function groupPersonByAvailableRole($institution, $persons, $mapping)
    {
        $roleTypeGroupedPersons = [];

        foreach ($persons as $person) {
            foreach ($person->getFunctions() as $personFunction) {
                // is this role available?
                $functionInstitution = $personFunction->getInstitution();
                if ($functionInstitution && ($functionInstitution->getId() == $institution->getId())) {
                    if ($personFunction->getAvailableFunction()) {
                        $roleId = $personFunction->getAvailableFunction()->getId();
                        if ($mapping['id'][$roleId]) {
                            $roleId = $mapping['id'][$roleId];
                        }
                        if ($mapping['label'][$roleId]) {
                            $roleName = $mapping['label'][$roleId];
                        } else {
                            $roleName = $personFunction->getAvailableFunction()->getName();
                        }
                        if (!isset($roleTypeGroupedPersons[$roleId])) {
                            $roleTypeGroupedPersons[$roleId] = [
                                'name' => $roleName,
                                'functions' => []
                            ];
                        }

                        $roleTypeGroupedPersons[$roleId]['functions'][$person->getId()] = [
                            'person' => $person,
                            'function' => $personFunction
                        ];
                    }
                }
            }
        }

        return $roleTypeGroupedPersons;
    }
}

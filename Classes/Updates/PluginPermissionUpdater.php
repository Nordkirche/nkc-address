<?php

declare(strict_types=1);

namespace Nordkirche\NkcAddress\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class PluginPermissionUpdater implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'txNkcAddressPluginPermissionUpdater';
    }

    public function getTitle(): string
    {
        return 'EXT:nkc_address: Migrate plugin permissions';
    }

    public function getDescription(): string
    {
        $description = 'This update wizard updates all permissions and allows **all** address plugins instead of the previous two plugins.';
        $description .= ' Count of affected groups: ' . count($this->getMigrationRecords());
        return $description;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->checkIfWizardIsRequired();
    }

    public function executeUpdate(): bool
    {
        return $this->performMigration();
    }

    public function checkIfWizardIsRequired(): bool
    {
        return count($this->getMigrationRecords()) > 0;
    }

    public function performMigration(): bool
    {
        $records = $this->getMigrationRecords();

        foreach ($records as $record) {
            $this->updateRow($record);
        }

        return true;
    }

    protected function getMigrationRecords(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like(
                        'explicit_allowdeny',
                        $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:nkcaddress_institution') . '%')
                    ),
                    $queryBuilder->expr()->like(
                        'explicit_allowdeny',
                        $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:nkcaddress_person') . '%')
                    ),
                    $queryBuilder->expr()->like(
                        'explicit_allowdeny',
                        $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:nkcaddress_map') . '%')
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function updateRow(array $row): void
    {
        $defaultInstitution = 'tt_content:CType:nkcaddress_institutionlist,tt_content:CType:nkcaddress_institution,tt_content:CType:nkcaddress_institutionsearchform';
        $defaultPerson = 'tt_content:CType:nkcaddress_personlist,tt_content:CType:nkcaddress_person,tt_content:CType:nkcaddress_personsearchform';
        $defaultMap = 'tt_content:CType:nkcaddress_map,tt_content:CType:nkcaddress_maplist';

        $searchReplace = [
            'tt_content:list_type:tx_nkcaddress_institution:ALLOW' => $defaultInstitution,
            'tt_content:list_type:tx_nkcaddress_institution:DENY' => '',
            'tt_content:list_type:tx_nkcaddress_institution' => $defaultInstitution,
            'tt_content:list_type:tx_nkcaddress_person:ALLOW' => $defaultPerson,
            'tt_content:list_type:tx_nkcaddress_person:DENY' => '',
            'tt_content:list_type:tx_nkcaddress_person' => $defaultPerson,
            'tt_content:list_type:tx_nkcaddress_map:ALLOW' => $defaultMap,
            'tt_content:list_type:tx_nkcaddress_map:DENY' => '',
            'tt_content:list_type:tx_nkcaddress_map' => $defaultMap,
        ];

        $newList = str_replace(array_keys($searchReplace), array_values($searchReplace), $row['explicit_allowdeny']);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $queryBuilder->update('be_groups')
            ->set('explicit_allowdeny', $newList)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }
}

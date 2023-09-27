<?php
declare(strict_types=1);


namespace WapplerSystems\FilecollectionGallery\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * This is a generic updater to migrate content of TCA rows.
 *
 * Multiple classes implementing interface "RowUpdateInterface" can be
 * registered here, each for a specific update purpose.
 *
 * The updater fetches each row of all TCA registered tables and
 * visits the client classes who may modify the row content.
 *
 * The updater remembers for each class if it run through, so the updater
 * will be shown again if a new updater class is registered that has not
 * been run yet.
 *
 * A start position pointer is stored in the registry that is updated during
 * the run process, so if for instance the PHP process runs into a timeout,
 * the job can restart at the position it stopped.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('migrateSfToWsGalleryUpdateWizard')]
class MigrateSfToWsGalleryUpdateWizard implements UpgradeWizardInterface, ConfirmableInterface
{


    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate sf_filecollection_gallery records to filecollection_gallery records';
    }


    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $recordsToMigrate = $queryBuilder->count('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                    $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter('sffilecollectiongallery_pifilecollectiongallery'))
                )
            )
            ->executeQuery()
            ->fetchOne();
        return $recordsToMigrate > 0;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }


    /**
     * Moves data from pages.urltype to pages.url
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $updateQueryBuilder = $connection->createQueryBuilder();
        $updateQueryBuilder
            ->update('tt_content')
            ->where(
                $updateQueryBuilder->expr()->and(
                    $updateQueryBuilder->expr()->eq('CType', $updateQueryBuilder->createNamedParameter('list')),
                    $updateQueryBuilder->expr()->eq('list_type', $updateQueryBuilder->createNamedParameter('sffilecollectiongallery_pifilecollectiongallery'))
                )
            )
            ->set('CType', 'filecollectiongallery_gallery')
            ->set('list_type', '');
        $updateQueryBuilder->executeStatement();

        return true;
    }


    /**
     * @return Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return GeneralUtility::makeInstance(
            Confirmation::class,
            'Are you sure?',
            'Do you want to continue?',
            false
        );
    }

    public function getDescription(): string
    {
        return '';
    }

}

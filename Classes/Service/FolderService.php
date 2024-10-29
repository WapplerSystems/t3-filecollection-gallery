<?php
namespace WapplerSystems\FilecollectionGallery\Service;


use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;

/**
 * FolderService
 *
 * @author Sven Wappler <typo3@wappler.systems>
 */
class FolderService
{

    /**
     *
     * @param File $file
     * @return Folder|InaccessibleFolder
     * @throws InsufficientFolderAccessPermissionsException
     */
    public function getFolderByFile($file): Folder|InaccessibleFolder
    {
        $storage = $file->getStorage();
        $folderIdentifier = $storage->getFolderIdentifierFromFileIdentifier($file->getIdentifier());
        return $storage->getFolder($folderIdentifier);
    }
}

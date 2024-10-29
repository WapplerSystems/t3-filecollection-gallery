<?php

namespace WapplerSystems\FilecollectionGallery\Service;


use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;

/**
 * FileCollectionService
 *
 * @author Sven Wappler <typo3@wappler.systems>
 */
class FileCollectionService
{


    public function __construct(
        readonly FileCollectionRepository $fileCollectionRepository,
        readonly FrontendConfigurationManager $frontendConfigurationManager)
    {

    }


    /**
     * Returns an array of file objects for the given UIDs of fileCollections
     *
     * @param array $collectionUids The uids
     * @param string $sortingDirection
     * @return array
     * @throws ResourceDoesNotExistException
     */
    public function getFileObjectsFromCollection(array $collectionUids, string $sortingDirection = 'asc'): array
    {
        $imageItems = [];
        foreach ($collectionUids as $collectionUid) {
            $collection = $this->fileCollectionRepository->findByUid($collectionUid);
            if ($collection === null) {
                continue;
            }
            $collection->loadContents();
            foreach ($collection->getItems() as $item) {
                $collectionProperties = [
                    'uid' => $collection->getUid(),
                    'title' => $collection->getTitle(),
                    'description' => $collection->getDescription()
                ];
                if ($item instanceof FileReference) {
                    $file = $this->getFileObjectFromFileReference($item);
                    $file->updateProperties(['collection' => $collectionProperties]);
                    $imageItems[] = $file;
                } else {
                    $item->updateProperties(['collection' => $collectionProperties]);
                    $imageItems[] = $item;
                }
            }
        }
        return $this->sortFileObjects($imageItems, $sortingDirection);
    }


    /**
     * Returns an array of gallery covers for the given UIDs of fileCollections
     * Use if you have recursive folder collection.
     *
     * @param $collectionUids
     * @param $galleryFolderHash
     * @param string $sortingDirection
     * @return array
     * @throws ResourceDoesNotExistException
     */
    public function getGalleryItemsByFolderHash($collectionUids, $galleryFolderHash, string $sortingDirection = 'asc'): array
    {
        $imageItems = [];

        // Load all images from collection
        foreach ($collectionUids as $collectionUid) {
            $collection = $this->fileCollectionRepository->findByUid($collectionUid);
            if ($collection === null) {
                continue;
            }
            $collection->loadContents();
            $allItems = [];

            // Load all image and sort them by folder_hash
            foreach ($collection->getItems() as $item) {
                if ($item->getProperty('folder_hash') === $galleryFolderHash) {
                    if ($item instanceof FileReference) {
                        $allItems[] = $this->getFileObjectFromFileReference($item);
                    } else {
                        $allItems[] = $item;
                    }
                }
            }
            $imageItems = $this->sortFileObjects($allItems, $sortingDirection);
        }
        return $imageItems;
    }

    /**
     * Returns the array including pagination settings
     *
     * @param array $settings The current settings
     * @return array
     */
    public function buildPaginationArray($settings): array
    {
        $paginationArray = [];
        if (!empty($settings)) {
            $paginationArray = [
                'itemsPerPage' => $settings['imagesPerPage'],
                'maximumVisiblePages' => $settings['numberOfPages'],
                'insertAbove' => $settings['insertAbove'],
                'insertBelow' => $settings['insertBelow']
            ];
        }
        return $paginationArray;
    }

    /**
     * Returns the array including pagination settings for nested views
     *
     * @param array $settings The current settings
     * @return array
     */
    public function buildPaginationArrayForNested(array $settings): array
    {
        $paginationArray = [];
        if (!empty($settings)) {
            $paginationArray = [
                'itemsPerPage' => $settings['nestedImagesPerPage'],
                'maximumVisiblePages' => $settings['nestedNumberOfPages'],
                'insertAbove' => $settings['nestedInsertAbove'],
                'insertBelow' => $settings['nestedInsertBelow']
            ];
        }
        return $paginationArray;
    }


    protected function sortFileObjectsByName($items, int $direction): void
    {
        $lowercaseNames = array_map(function ($n) {
            return strtolower($n->getName());
        }, $items);

        array_multisort($lowercaseNames, $direction, SORT_STRING, $items);
    }

    protected function sortFileObjectsByDate($items, int $direction): void
    {
        $dates = array_map(function ($n) {
            return strtolower($n->getCreationTime());
        }, $items);

        array_multisort($dates, $direction, SORT_NUMERIC, $items);
    }

    protected function sortFileObjectsByFolderHash(&$items, int $direction): void
    {
        $folderhashes = array_map(function ($n) {
            return strtolower($n->getProperty('folder_hash'));
        }, $items);

        array_multisort($folderhashes, $direction, SORT_NUMERIC, $items);
    }

    /**
     * Sorts the Result Array according to the Flexform Settings
     *
     * @param array $imageItems The image items
     *
     * @return array
     */
    protected function sortFileObjects(array $imageItems, string $sortingDirection = 'asc'): array
    {
        switch ($sortingDirection) {
            case 'desc':
                $this->sortFileObjectsByName($imageItems, SORT_DESC);
                break;
            case 'date-desc':
                $this->sortFileObjectsByDate($imageItems, SORT_DESC);
                break;
            case 'date-asc':
                $this->sortFileObjectsByDate($imageItems, SORT_ASC);
                break;
            case 'manual':
                // Do not sort. This could be default, but could be breaking, since default was ASC before.
                break;
            default:
                $this->sortFileObjectsByName($imageItems, SORT_ASC);
                break;
        }
        return $imageItems;
    }

    /**
     * Returns an FileObject from a given FileReference
     *
     */
    protected function getFileObjectFromFileReference(FileReference $item): File
    {
        /**
         * The item to return
         *
         * @var File $returnItem
         */
        $returnItem = $item->getOriginalFile();
        $returnItem->updateProperties($item->getProperties());
        return $returnItem;
    }
}

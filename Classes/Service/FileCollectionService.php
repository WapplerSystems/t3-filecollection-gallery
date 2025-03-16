<?php

namespace WapplerSystems\FilecollectionGallery\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
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

    public function __construct(readonly FileCollectionRepository $fileCollectionRepository, readonly FrontendConfigurationManager $frontendConfigurationManager)
    {

    }

    /**
     * Returns an array of file objects for the given UIDs of fileCollections
     *
     * @param array $collectionUids The uids
     *
     * @return array
     * @throws ResourceDoesNotExistException
     */
    public function getFileObjectsFromCollection(array $collectionUids)
    {
        $imageItems = [];
        foreach ($collectionUids as $collectionUid) {
            if ($collectionUid === '') {
                continue;
            }
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
                if ($item instanceof \TYPO3\CMS\Core\Resource\FileReference) {
                    $file = $this->getFileObjectFromFileReference($item);
                    $file->updateProperties(['collection' => $collectionProperties]);
                    $imageItems[] = $file;
                } else {
                    $item->updateProperties(['collection' => $collectionProperties]);
                    $imageItems[] = $item;
                }
            }
        }
        return $this->sortFileObjects($imageItems);
    }


    /**
     * Returns an array of gallery covers for the given UIDs of fileCollections
     * Use if you have recursive folder collection.
     *
     * @param $collectionUids
     * @param $galleryFolderHash
     * @return array
     */
    public function getGalleryItemsByFolderHash($collectionUids, $galleryFolderHash)
    {
        $imageItems = [];

        // Load all images from collection
        foreach ($collectionUids as $collectionUid) {
            $collection = $this->fileCollectionRepository->findByUid($collectionUid);
            $collection->loadContents();
            $allItems = [];

            // Load all image and sort them by folder_hash
            foreach ($collection->getItems() as $item) {
                if ($item->getProperty('folder_hash') === $galleryFolderHash) {
                    if (get_class($item) === 'TYPO3\CMS\Core\Resource\FileReference') {
                        array_push($allItems, $this->getFileObjectFromFileReference($item));
                    } else {
                        array_push($allItems, $item);
                    }
                }
            }
            $imageItems = $this->sortFileObjects($allItems);
        }
        return $imageItems;
    }

    /**
     * Returns the array including pagination settings
     *
     * @param array $settings The current settings
     * @return array
     */
    public function buildPaginationArray($settings)
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
    public function buildPaginationArrayForNested($settings)
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


    protected function sortFileObjectsByName($items, int $direction)
    {
        $lowercaseNames = array_map(function ($n) {
            return strtolower($n->getName());
        }, $items);

        array_multisort($lowercaseNames, $direction, SORT_STRING, $items);
    }

    protected function sortFileObjectsByDate($items, int $direction)
    {
        $dates = array_map(function ($n) {
            return strtolower($n->getCreationTime());
        }, $items);

        array_multisort($dates, $direction, SORT_NUMERIC, $items);
    }

    protected function sortFileObjectsByFolderHash(&$items, int $direction)
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
    protected function sortFileObjects($imageItems)
    {
        $configuration = $this->frontendConfigurationManager->getConfiguration();
        switch ($configuration['settings']['order'] ?? '') {
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
    protected function getFileObjectFromFileReference(FileReference $item): \TYPO3\CMS\Core\Resource\File
    {
        /**
         * The item to return
         *
         * @var \TYPO3\CMS\Core\Resource\File $returnItem
         */
        $returnItem = $item->getOriginalFile();
        $returnItem->updateProperties($item->getProperties());
        return $returnItem;
    }
}

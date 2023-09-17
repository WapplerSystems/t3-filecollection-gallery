<?php
defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'filecollection_gallery',
    'Gallery',
    [
        \WapplerSystems\FilecollectionGallery\Controller\GalleryController::class => 'list,nested,nestedFromFolder,listFromFolder',
    ],
    []
);


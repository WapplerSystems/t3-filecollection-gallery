<?php
defined('TYPO3') or die();

use \TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'filecollection_gallery',
    'Gallery',
    [
        \WapplerSystems\FilecollectionGallery\Controller\GalleryController::class => 'list,nested,nestedFromFolder,listFromFolder',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);


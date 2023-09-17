<?php

/**
 * Register the plugin
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'filecollection_gallery',
    'Gallery',
    'FileCollection Gallery'
);

/**
 * Add flexform
 */
$pluginSignature = 'sffilecollectiongallery_pifilecollectiongallery';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:filecollection_gallery/Configuration/FlexForms/gallery.xml'
);

/**
 * Add static TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'filecollection_gallery',
    'Configuration/TypoScript',
    'FileCollection Gallery'
);

/**
 * Remove unused fields
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,recursive,pages';

<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'SKYFILLERS.sf_filecollection_gallery',
    'Pifilecollectiongallery',
    [
        'Gallery' => 'list,nested,nestedFromFolder,listFromFolder',
    ],
    []
);

// Use hook from http://www.derhansen.de/2014/06/typo3-how-to-prevent-empty-flexform.html
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['SKYFILLERS.sf_filecollection_gallery'] = 'SKYFILLERS\SfFilecollectionGallery\Hooks\DataHandlerHooks';

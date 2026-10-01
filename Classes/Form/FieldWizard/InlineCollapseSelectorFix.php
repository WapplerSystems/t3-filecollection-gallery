<?php

declare(strict_types=1);

namespace WapplerSystems\FilecollectionGallery\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Loads a backend module that escapes the collapse targets of inline records.
 *
 * Inline records inside a FlexForm get a DOM id derived from the field name,
 * e.g. "...settings.inlineFileCollection...". The core renders that id unescaped
 * into data-bs-target, so Bootstrap reads ".inlineFileCollection" as a class
 * selector, finds nothing and the record can't be expanded.
 */
class InlineCollapseSelectorFix extends AbstractNode
{
    public function render(): array
    {
        $result = $this->initializeResultArray();
        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@wapplersystems/filecollection-gallery/backend/inline-collapse-selector-fix.js'
        );
        return $result;
    }
}

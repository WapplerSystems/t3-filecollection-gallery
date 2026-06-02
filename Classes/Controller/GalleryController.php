<?php

namespace WapplerSystems\FilecollectionGallery\Controller;


use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface;
use WapplerSystems\FilecollectionGallery\Service\FileCollectionService;
use WapplerSystems\FilecollectionGallery\Service\FolderService;

/**
 * GalleryController
 *
 * @author Sven Wappler <typo3@wappler.systems>
 */
class GalleryController extends ActionController
{


    public function __construct(
        readonly FileCollectionService $fileCollectionService,
        readonly FileCollectionRepository $fileCollectionRepository,
        readonly FolderService $folderService,
        readonly ViewResolverInterface $viewResolver)
    {

    }


    /**
     * Initializes the view before invoking an action method.
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     */
    protected function initializeView($view)
    {
        $view->assign('contentObjectData', $this->request->getAttribute('currentContentObject')->data);
    }

    /**
     * FlexForm fields can override site settings, but an empty FlexForm
     * value would otherwise wipe the site setting. Restore the raw
     * TypoScript value for every override key that the editor left empty.
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();

        $overridablePaths = [
            ['mobile', 'image', 'maxWidth'],
            ['desktop', 'preview', 'image', 'width'],
            ['desktop', 'preview', 'image', 'height'],
            ['downloadButton'],
            ['lightbox', 'maxWidth'],
            ['lightbox', 'maxHeight'],
        ];

        $fullTypoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $rawTsSettings = $fullTypoScript['plugin.']['tx_filecollectiongallery.']['settings.'] ?? [];
        $tsSettings = GeneralUtility::makeInstance(TypoScriptService::class)
            ->convertTypoScriptArrayToPlainArray($rawTsSettings);

        foreach ($overridablePaths as $path) {
            $flexValue = $this->getNestedSetting($this->settings, $path);
            if ($flexValue === '' || $flexValue === null) {
                $tsValue = $this->getNestedSetting($tsSettings, $path);
                if ($tsValue !== null) {
                    $this->setNestedSetting($this->settings, $path, $tsValue);
                }
            }
        }
    }

    private function getNestedSetting(array $settings, array $path): mixed
    {
        $value = $settings;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private function setNestedSetting(array &$settings, array $path, mixed $value): void
    {
        $ref = &$settings;
        $lastIndex = count($path) - 1;
        foreach ($path as $i => $segment) {
            if ($i === $lastIndex) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /**
     * List action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     * @throws ResourceDoesNotExistException
     */
    public function listAction(int $offset = 0): ResponseInterface
    {
        $collectionUids = (trim($this->settings['fileCollection'] ?? '') !== '') ? explode(',', $this->settings['fileCollection']) : [];
        if (($this->settings['inlineFileCollection'] ?? '') !== '') {
            $collectionUids = array_merge($collectionUids, explode(',', $this->settings['inlineFileCollection']));
        }
        $cObj = $this->request->getAttribute('currentContentObject');
        $currentUid = $cObj->data['uid'];
        $columnPosition = $cObj->data['colPos'];
        /** @var AbstractFileCollection $collection */
        $collection = null;

        $showBackToGallerySelectionLink = false;

        if ($collectionUids === []) {
            return $this->htmlErrorResponse('LLL:EXT:filecollection_gallery/Resources/Private/Language/locallang.xlf:error.noGallerySelected');
        }

        if ($this->request->hasArgument('galleryUID')) {
            $gallery = [$this->request->getArgument('galleryUID')];
            $mediaItems = $this->fileCollectionService->getFileObjectsFromCollection($gallery, $this->settings['order'] ?? 'asc');
            $collection = $this->fileCollectionRepository->findByUid($this->request->getArgument('galleryUID'));
            $showBackToGallerySelectionLink = true;
        } else {
            $mediaItems = $this->fileCollectionService->getFileObjectsFromCollection($collectionUids, $this->settings['order'] ?? 'asc');
        }

        if ($collection === null && count($collectionUids) === 1) {
            $collection = $this->fileCollectionRepository->findByUid($collectionUids[0]);
        }

        if ($collection !== null) {
            $collection->loadContents();
            $this->view->assign('galleryListName', $collection->getTitle());
        }

        $this->view->assignMultiple([
            'mediaItems' => $mediaItems,
            'currentUid' => $currentUid,
            'columnPosition' => $columnPosition,
            'showBackToGallerySelectionLink' => $showBackToGallerySelectionLink
        ]);

        return $this->htmlResponse();
    }

    /**
     * List from folder action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     * @throws ResourceDoesNotExistException|InsufficientFolderAccessPermissionsException
     */
    public function listFromFolderAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $showBackToGallerySelectionLink = false;
            $mediaItems = [];
            //if a special gallery is requested
            if ($this->request->hasArgument('galleryFolder') && $this->request->hasArgument('galleryUID')) {
                $galleryFolderHash = $this->request->getArgument('galleryFolder');
                $galleryUid = [$this->request->getArgument('galleryUID')];
                $mediaItems = $this->fileCollectionService->getGalleryItemsByFolderHash($galleryUid, $galleryFolderHash);
                $showBackToGallerySelectionLink = true;
            }

            if ($mediaItems) {
                $this->view->assign('galleryFolderName', $this->folderService->getFolderByFile($mediaItems[0])->getName());
            }

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArray($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                $showBackToGallerySelectionLink
            ));
        }

        return $this->htmlResponse();
    }

    /**
     * Nested action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     */
    public function nestedAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $collectionUids = explode(',', $this->settings['fileCollection']);

            $mediaItems = $this->fileCollectionService->getGalleryCoversFromCollections($collectionUids);

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArrayForNested($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                false
            ));
        }

        return $this->htmlResponse();
    }

    /**
     * Nested action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     */
    public function nestedFromFolderAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $collectionUids = explode(',', $this->settings['fileCollection']);

            $mediaItems = $this->fileCollectionService->getGalleryCoversFromNestedFoldersCollection($collectionUids);

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArrayForNested($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                false
            ));
        }

        return $this->htmlResponse();
    }


    protected function htmlErrorResponse(?string $errorLabel = null): ResponseInterface
    {
        $this->view->setTemplatePathAndFilename('EXT:filecollection_gallery/Resources/Private/Templates/Gallery/Error.html');
        $this->view->assign('errorLabel', $errorLabel);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withStatus(500)
            ->withBody($this->streamFactory->createStream($this->view->render()));
    }
}

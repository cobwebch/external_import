<?php
declare(strict_types=1);
namespace Cobweb\ExternalImport\Transformation;

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

use Cobweb\ExternalImport\ImporterAwareInterface;
use Cobweb\ExternalImport\ImporterAwareTrait;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Transformation class for External Import offering example user functions for storing images
 * during an import process. Use as is or as an inspiration for your own needs.
 *
 * The critical part is that the function is expected to return the uid of a sys_file record.
 *
 * @package Cobweb\ExternalImport\Transformation
 */
class ImageTransformation implements SingletonInterface, ImporterAwareInterface
{
    use ImporterAwareTrait;

    /**
     * @var string Used to return a dummy identifier in preview mode
     */
    static public $previewMessage = 'Preview mode. Image not handled, nor saved.';

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var Folder[] Array of Folder objects
     */
    protected $storageFolders = [];

    public function __construct()
    {
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Gets an image from a URI and saves it into the given file storage and path.
     *
     * The parameters array is expected to contain the following information:
     *
     *      - "storage": a combined FAL identifier to a folder (e.g. "1:imported_images")
     *      - "nameField": the external data field from which the name for the file should be taken. If empty, the file's basename is used
     *      - "defaultExtension": a file extension in case it cannot be found in the URI (e.g. "jpg")
     *
     * @param array $record The full record that is being transformed
     * @param string $index The index of the field to transform
     * @param array $parameters Additional parameters from the TCA
     * @return mixed Uid of the saved sys_file record (or a message in preview mode)
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function saveImageFromUri(array $record, string $index, array $parameters)
    {
        // If there's no value to handle, return null
        if (empty($record[$index])) {
            return null;
        }

        // In preview mode, we don't want to handle or save images and just return a dummy string
        if ($this->importer->isPreview()) {
            return self::$previewMessage;
        }

        // Throw an exception if storage is not defined
        if (empty($parameters['storage'])) {
            throw new \InvalidArgumentException(
                    'No storage given for importing files',
                    1602170223
            );
        }

        // Ensure the storage folder is loaded (we keep a local cache of folder objects for efficiency)
        if ($this->storageFolders[$parameters['storage']] === null) {
            $this->storageFolders[$parameters['storage']] = $this->resourceFactory->getFolderObjectFromCombinedIdentifier(
                    $parameters['storage']
            );
        }
        // Assemble a file name
        $urlParts = parse_url($record[$index]);
        $pathParts = pathinfo($urlParts['path']);
        if (isset($parameters['nameField'], $record[$parameters['nameField']])) {
            $fileName = $record[$parameters['nameField']];
            if (isset($pathParts['extension'])) {
                $fileName .= '.' . $pathParts['extension'];
            } elseif (isset($parameters['defaultExtension'])) {
                $fileName .= '.' . $parameters['defaultExtension'];
            } else {
                throw new \InvalidArgumentException(
                        sprintf(
                                'No extension could be found for imported file %s',
                                $record[$index]
                        ),
                        1602170422
                );
            }
        } else {
            $fileName = $pathParts['basename'];
            if (empty($pathParts['extension'])) {
                if (isset($parameters['defaultExtension'])) {
                    $fileName .= '.' . $parameters['defaultExtension'];
                } else {
                    throw new \InvalidArgumentException(
                            sprintf(
                                    'No extension could be found for imported file %s',
                                    $record[$index]
                            ),
                            1602170422
                    );
                }
            }
        }
        $fileName = $this->storageFolders[$parameters['storage']]->getStorage()->sanitizeFileName(
                $fileName,
                $this->storageFolders[$parameters['storage']]
        );
        // Check if the file already exists
        if ($this->storageFolders[$parameters['storage']]->hasFile($fileName)) {
            $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier(
                    $parameters['storage'] . '/' . $fileName
            );
        // If the file does not yet exist locally, grab it from the remote server and add it to predefined storage
        } else {
            $temporaryFile = GeneralUtility::tempnam('external_import_upload');
            $file = GeneralUtility::getUrl($record[$index], 0, null, $report);
            // If the file could not be fetched, report and throw an exception
            if ($file === false) {
                $error = sprintf(
                        'File %s could not be fetched.',
                        $record[$index]
                );
                if (isset($report['message'])) {
                    $error .= ' ' . sprintf(
                            'Reason: %s (code: %s)',
                            $report['message'],
                            $report['error'] ?? 0
                    );
                }
                throw new \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException(
                        $error,
                        1613555057
                );
            }
            GeneralUtility::writeFileToTypo3tempDir(
                    $temporaryFile,
                    $file
            );
            $fileObject = $this->storageFolders[$parameters['storage']]->addFile(
                    $temporaryFile,
                    $fileName
            );
        }
        // Return the file's ID
        return $fileObject->getUid();
    }

    /**
     * Gets an image from a base64-encoded string and saves it into the given file storage and path.
     *
     * The parameters array is expected to contain the following information:
     *
     *      - "storage": a combined FAL identifier to a folder (e.g. "1:imported_images")
     *      - "nameField": the external data field from which the name for the file should be taken. If empty, the file's basename is used
     *      - "defaultExtension": a file extension in case it cannot be found in the URI (e.g. "jpg")
     *
     * @param array $record The full record that is being transformed
     * @param string $index The index of the field to transform
     * @param array $parameters Additional parameters from the TCA
     * @return mixed Uid of the saved sys_file record (or a message in preview mode)
     */
    public function saveImageFromBase64(array $record, string $index, array $parameters)
    {
        // If there's no value to handle, return null
        if (empty($record[$index])) {
            return null;
        }

        // In preview mode, we don't want to handle or save images and just return a dummy string
        if ($this->importer->isPreview()) {
            return self::$previewMessage;
        }

        // Get the storage folder
        $folder = $this->initializeStorageFolder($parameters['storage']);

        // Assemble a file name
        if (isset($parameters['nameField'], $record[$parameters['nameField']])) {
            $fileName = $record[$parameters['nameField']];
        } else {
            $fileName = sha1($record[$index]);
        }
        if (isset($parameters['defaultExtension'])) {
            $fileName .= '.' . $parameters['defaultExtension'];
        } else {
            throw new \InvalidArgumentException(
                    sprintf(
                            'Default extension parameter not set for file %s',
                            $fileName
                    ),
                    1603033956
            );
        }
        try {
            $fileName = $folder->getStorage()->sanitizeFileName(
                    $fileName,
                    $this->storageFolders[$parameters['storage']]
            );
        } catch (\TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException $e) {
            // A file with this name already exists, this is handled below
        }

        // Check if the file already exists
        if ($folder->hasFile($fileName)) {
            $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier(
                    $parameters['storage'] . '/' . $fileName
            );
        // If the file does not yet exist locally, grab it from the remote server and add it to predefined storage
        } else {
            $fileObject = $folder->createFile($fileName);
            $fileObject->setContents(base64_decode($record[$index]));
        }
        // Return the file's ID
        return $fileObject->getUid();
    }

    /**
     * Initializes a Folder object given a combined identifier.
     *
     * @param string $storagePath
     * @return Folder
     */
    public function initializeStorageFolder($storagePath): Folder
    {
        // Throw an exception if storage is not defined
        if (empty($storagePath)) {
            throw new \InvalidArgumentException(
                    'No storage given for importing files',
                    1602170223
            );
        }

        // Ensure the storage folder is loaded (we keep a local cache of folder objects for efficiency)
        if ($this->storageFolders[$storagePath] === null) {
            $this->storageFolders[$storagePath] = $this->resourceFactory->getFolderObjectFromCombinedIdentifier(
                    $storagePath
            );
        }
        return $this->storageFolders[$storagePath];
    }
}
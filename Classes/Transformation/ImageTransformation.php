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
     */
    public function saveImageFromUri(array $record, string $index, array $parameters)
    {
        // If there's no value to handle, return null
        if (!isset($record[$index])) {
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
        // Check if the file already exists
        if ($this->storageFolders[$parameters['storage']]->hasFile($fileName)) {
            $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier(
                    $parameters['storage'] . '/' . $fileName
            );
        // If the file does not yet exist locally, grab it from the remote server and add it to predefined storage
        } else {
            $temporaryFile = GeneralUtility::tempnam('external_import_upload');
            GeneralUtility::writeFileToTypo3tempDir(
                    $temporaryFile,
                    GeneralUtility::getUrl($record[$index])
            );
            $fileObject = $this->storageFolders[$parameters['storage']]->addFile(
                    $temporaryFile,
                    $fileName
            );
        }
        // Return the file's ID
        return $fileObject->getUid();
    }
}
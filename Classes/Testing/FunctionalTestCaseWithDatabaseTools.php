<?php

declare(strict_types=1);

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

namespace Cobweb\ExternalImport\Testing;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FunctionalTestCaseWithDatabaseTools extends FunctionalTestCase
{
    /**
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    protected function initializeBackendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Tests/Functional/Fixtures/BackendUser.csv');
        $userRow = $this->getBackendUserRecordFromDatabase(1);
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $request = new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']);
        $session = $backendUser->createUserSession($userRow);
        $request = $request->withCookieParams(['be_typo_user' => $session->getJwt()]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['BE_USER'] = $this->authenticateBackendUser($backendUser, $request);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function selectCount(string $field, string $table, ?string $condition = '', bool $includeHiddenRecords = false): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        if ($includeHiddenRecords) {
            $queryBuilder->getRestrictions()->removeByType(\TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class);
        }
        $queryBuilder->count($field)
            ->from($table);
        if (!empty($condition)) {
            $queryBuilder->where($condition);
        }
        $result = $queryBuilder->executeQuery();
        return (int)$result->fetchOne();
    }
}

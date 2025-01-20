<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Domain\Model\Dto;

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

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Data Transfer Object model for AJAX query parameters coming from DataTables.
 */
class QueryParameters
{
    /**
     * @var int Identifier of the DataTables draw request
     */
    protected int $draw = 0;

    /**
     * @var string Search string
     */
    protected string $search = '';

    /**
     * @var array List of columns in which the search is performed
     */
    protected array $searchColumns = [];

    /**
     * @var int Maximum number of records to return
     */
    protected int $limit = 0;

    /**
     * @var int Offset to apply (pagination)
     */
    protected int $offset = 0;

    /**
     * @var string Name of the column to use for ordering
     */
    protected string $order = '';

    /**
     * @var string Direction of ordering
     */
    protected string $direction = QueryInterface::ORDER_DESCENDING;

    /**
     * Constructor.
     *
     * @param array|null $parameters Query parameters from the AJAX query
     */
    public function __construct(?array $parameters = null)
    {
        if ($parameters !== null) {
            $this->setAllParameters($parameters);
        }
    }

    /**
     * Receives the raw parameters and set the various member variables after validation and sanitation.
     *
     * @param array $parameters Query parameters from the AJAX query
     */
    public function setAllParameters(array $parameters): void
    {
        // Set simple parameters
        $this->setDraw(isset($parameters['draw']) ? (int)$parameters['draw'] : 0);
        $this->setLimit(isset($parameters['length']) ? (int)$parameters['length'] : 0);
        $this->setOffset(isset($parameters['start']) ? (int)$parameters['start'] : 0);
        $this->setSearch((string)$parameters['search']['value']);
        // Assemble list of search columns
        $this->setSearchColumns($parameters['columns']);
        // Ordering column name must match existing column
        if ($parameters['order'] ?? false) {
            $column = $parameters['order'][0]['column'] ? (int)$parameters['order'][0]['column'] : null;
        } else {
            $column = null;
        }
        if (array_key_exists($column, $parameters['columns'])) {
            $columnName = $parameters['columns'][$column]['name'];
        } else {
            $columnName = '';
        }
        $this->setOrder($columnName);
        $direction = strtoupper($parameters['order'][0]['dir'] ?? 'asc');
        $this->setDirection($direction);
    }

    /**
     * @return int
     */
    public function getDraw(): int
    {
        return $this->draw;
    }

    /**
     * @param int $draw
     */
    public function setDraw(int $draw): void
    {
        $this->draw = $draw;
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search;
    }

    /**
     * @param string $search
     */
    public function setSearch(string $search): void
    {
        $this->search = $search;
    }

    /**
     * @return array
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns;
    }

    /**
     * @param array $searchColumns
     */
    public function setSearchColumns(array $searchColumns): void
    {
        $this->searchColumns = [];
        foreach ($searchColumns as $columnData) {
            if ($columnData['searchable'] === 'true') {
                $this->searchColumns[] = $columnData['name'];
            }
        }
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     */
    public function setDirection(string $direction): void
    {
        // Ordering direction is either explicitly "asc", or "desc" by default
        if ($direction !== QueryInterface::ORDER_ASCENDING) {
            $direction = QueryInterface::ORDER_DESCENDING;
        }
        $this->direction = $direction;
    }
}

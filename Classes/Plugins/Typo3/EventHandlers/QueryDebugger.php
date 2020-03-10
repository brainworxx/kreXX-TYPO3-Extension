<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Throwable;

/**
 * TYPO3 Query debugger.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe::start
 *
 * @package Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers
 */
class QueryDebugger implements EventHandlerInterface, ConstInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Getting the SQL out of a query builder and adding it to the output.
     *
     * @param AbstractCallback $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model so far.
     *
     * @return string
     *   Return an empty string.
     */
    public function handle(AbstractCallback $callback = null, Model $model = null): string
    {
        if (empty($sql = $this->retrieveSql($callback->getParameters()[static::PARAM_DATA]))  === true) {
            // Wrong object type, or problems with the SQL retrieval.
            return '';
        }

        $length = strlen($sql);
        $sqlEscaped = $this->pool->encodingService->encodeString($sql);
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setData($sqlEscaped)
            ->setHelpid('queryBuilderHelp')
            ->setName('SQL')
            ->setType(static::TYPE_STRING . $length);

        if ($length > 50) {
            $cut = $this->pool->encodingService->encodeString(
                $this->pool->encodingService->mbSubStr($sql, 0, 50)
            ) . static::UNKNOWN_VALUE;
            $model->setNormal($cut)->setHasExtra(true);
        } else {
            $model->setNormal($sqlEscaped);
        }

        // Disable source generation
        $this->pool->codegenHandler->setAllowCodegen(false);
        $result = $this->pool->render->renderExpandableChild($model);

        // Enable source generation.
        $this->pool->codegenHandler->setAllowCodegen(true);

        return $result;
    }

    /**
     * Retrieve the SQL from a query builder object.
     *
     * @param QueryBuilder|QueryInterface $query
     *   The callback with the QueryBuilder
     *
     * @return string
     *   The SQL, or an empty string in case of an error.
     */
    protected function retrieveSql($query): string
    {
        $result = '';
        try {
            // Retrieving the SQL, depending on the object class.
            if ($query instanceof QueryBuilder) {
                $sql = $query->getSQL();
                $parameters = $query->getParameters();
            } elseif ($query instanceof QueryInterface) {
                $doctrineQuery = GeneralUtility::makeInstance(ObjectManager::class)
                    ->get(Typo3DbQueryParser::class)
                    ->convertQueryToDoctrineQueryBuilder($query);
                $sql = $doctrineQuery->getSQL();
                $parameters = $doctrineQuery->getParameters();
            } else {
                return '';
            }

            // Insert the parameters into the sql
            foreach ($parameters as $key => $parameter) {
                if (is_string($parameter)) {
                    $parameter = '\'' . $parameter . '\'';
                }
                $sql = str_replace(':' . $key, $parameter, $sql);
            }

            $result = $sql;
        } catch (Throwable $e) {
            // Do nothing.
        }

        return $result;
    }
}

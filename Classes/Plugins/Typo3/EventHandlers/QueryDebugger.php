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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser\Typo3DbQueryParser;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * TYPO3 Query debugger.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe::start
 */
class QueryDebugger implements EventHandlerInterface, CallbackConstInterface, ProcessConstInterface
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
     * @param AbstractCallback|null $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model so far.
     *
     * @return string
     *   Return an empty string.
     */
    public function handle(AbstractCallback $callback = null, Model $model = null): string
    {
        if (empty($sql = $this->retrieveSql($callback->getParameters()[static::PARAM_DATA]))) {
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
        $this->pool->codegenHandler->setCodegenAllowed(false);
        $result = $this->pool->render->renderExpandableChild($model);

        // Enable source generation.
        $this->pool->codegenHandler->setCodegenAllowed(true);

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
        try {
            if ($query instanceof QueryInterface) {
                $query = GeneralUtility::makeInstance(Typo3DbQueryParser::class)
                    ->convertQueryToDoctrineQueryBuilder($query);
            }

            if (!$query instanceof QueryBuilder) {
                return '';
            }

            // Retrieving the SQL.
            return $query->getSQL();
        } catch (Throwable $e) {
            // Tell the dev, that there is an error in the sql.
            return $this->pool->messages->getHelp('TYPO3Error') . $e->getMessage();
        }
    }
}

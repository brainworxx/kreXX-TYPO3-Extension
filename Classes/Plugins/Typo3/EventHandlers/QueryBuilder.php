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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as DbQueryBuilder;
use Exception;
use Throwable;

class QueryBuilder implements EventHandlerInterface, ConstInterface
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
    public function handle(AbstractCallback $callback = null, Model $model = null)
    {
        $sql = $this->retrieveSql($callback);
        if (empty($sql)) {
            // Something went wrong here.
            // Early return.
            return '';
        }

        $length = strlen($sql);
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setData($sql)
            ->setHelpid('queryBuilderHelp')
            ->setName('SQL')
            ->setType(static::TYPE_STRING . $length);

        if ($length > 50) {
            $cut = $this->pool->encodingService->encodeString(
                $this->pool->encodingService->mbSubStr($sql, 0, 50)
            ) . static::UNKNOWN_VALUE;
            $model->setNormal($cut)->setHasExtra(true);
        } else {
            $model->setNormal($sql);
        }

        // Disable source generation
        $this->pool->codegenHandler->setAllowCodegen(false);
        $result = $this->pool->render->renderSingleChild($model);

        // Enable source generation.
        $this->pool->codegenHandler->setAllowCodegen(true);

        return $result;
    }

    /**
     * Retrieve the SQL from a query builder object.
     *
     * @param AbstractCallback $callback
     *   The callback with the QueryBuilder
     *
     * @return string
     *   The SQL, or an empty string in case of an error.
     */
    protected function retrieveSql(AbstractCallback $callback)
    {
        $result = '';

        /** @var DbQueryBuilder $queryBuilder */
        $queryBuilder = $callback->getParameters()[static::PARAM_DATA];
        if ($queryBuilder instanceof DbQueryBuilder === false) {
            // Wrong object type.
            return $result;
        }

        try {
            $sql = $queryBuilder->getSQL();
            // Insert the parameters into the sql
            foreach ($queryBuilder->getParameters() as $key => $parameter) {
                $sql = str_replace(':' . $key, (string)$parameter, $sql);
            }

            $result = $sql;
        } catch (Exception $e) {
            // Do nothing.
        } catch (Throwable $e) {
            // Do nothing.
        }

        return $result;
    }
}

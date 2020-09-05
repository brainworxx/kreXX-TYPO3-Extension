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

namespace Brainworxx\Krexx\Analyse\Model;

use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

trait ConnectorService
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The connector service, used for source generation.
     *
     * @var Connectors
     */
    protected $connectorService;

    /**
     * Inject the pool and create the connector service.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->connectorService = $pool->createClass(
            Connectors::class
        );
        $this->pool = $pool;
    }

     /**
     * Getter got connectorLeft.
     *
     * @return string
     *   The first connector.
     */
    public function getConnectorLeft(): string
    {
        return $this->connectorService->getConnectorLeft();
    }

    /**
     * Getter for connectorRight.
     *
     * @param int $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   The second connector.
     */
    public function getConnectorRight($cap = 0): string
    {
        return $this->connectorService->getConnectorRight($cap);
    }

    /**
     * Setter for the $params. It is used in case we are connection a method or
     * closure.
     *
     * @param string|int $params
     *   The parameters as a sting.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setConnectorParameters($params): Model
    {
        $this->connectorService->setParameters($params);
        return $this;
    }

    /**
     * Getter for the connection parameters.
     *
     * @return string
     *   The connection parameters.
     */
    public function getConnectorParameters(): string
    {
        return $this->connectorService->getParameters();
    }

    /**
     * Setter for the type we are rendering, using the class constants.
     *
     * @param int $type
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function setConnectorType(string $type): Model
    {
        $this->connectorService->setType($type);
        return $this;
    }

    /**
     * Sets a special and custom connectorLeft. Only used for constants code
     * generation.
     *
     * @param string $string
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setCustomConnectorLeft(string $string): Model
    {
        $this->connectorService->setCustomConnectorLeft($string);
        return $this;
    }

    /**
     * Getter for the Language of the connector service.
     *
     * @return string
     */
    public function getConnectorLanguage(): string
    {
        return $this->connectorService->getLanguage();
    }

    /**
     * Getter of the return type of a method analysis.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->connectorService->getReturnType();
    }

    /**
     * Setter for the return type.
     *
     * @param string $returnType
     *   The return type.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setReturnType(string $returnType): Model
    {
        $this->connectorService->setReturnType($returnType);
        return $this;
    }
}

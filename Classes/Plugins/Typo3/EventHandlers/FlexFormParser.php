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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;
use TYPO3\CMS\Core\Service\FlexFormService as FlexFromServiceCore;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Parsing flexforms, if possible.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Xml::callMe::end
 */
class FlexFormParser implements EventHandlerInterface, CallbackConstInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Using the TYPO3 flexform parser to get data out of the xml structure.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback|null $callback
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     * @return string
     */
    public function handle(?AbstractCallback $callback = null, ?Model $model = null): string
    {
        $parameters = $model->getParameters();

        try {
            $meta = $parameters[static::PARAM_DATA];
            $meta[$this->pool->messages->getHelp('metaDecodedXml')] =
                GeneralUtility::makeInstance(FlexFromServiceCore::class)
                    ->convertFlexFormContentToArray($parameters[static::PARAM_VALUE]);;
            $model->addParameter(static::PARAM_DATA, $meta);
        } catch (Throwable $exception) {
            // Do nothing.
            // We did a TYPO3 framework call. Which may or may not be unstable.
            // This is a debugging tool, after all.
        }

        return '';
    }
}

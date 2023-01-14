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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use Throwable;

/**
 * Adding the result from the following methods to the output:
 *   _isDirty
 *   _isClone
 *   _isNew
 * when handling an AbstractDomainModel.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse::callMe::start
 */
class DirtyModels implements EventHandlerInterface
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
     * Running three very special debug methods, on TYPO3 models.
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
        /** @var AbstractDomainObject $data */
        $data = $model->getData();

        if ($data instanceof AbstractDomainObject === false) {
            // Early return. Wrong kind of object.
            return '';
        }

        try {
            try {
                $model->addToJson('Is dirty', $this->createReadableBoolean($data->_isDirty()));
            } catch (TooDirtyException $e) {
                $model->addToJson('Is dirty', 'TRUE, even the UID was modified!');
            }

            $model->addToJson('Is a clone', $this->createReadableBoolean($data->_isClone()));
            $model->addToJson('Is a new', $this->createReadableBoolean($data->_isNew()));
        } catch (Throwable $e) {
            // Do nothing.
            // Somebody has messed with the models.
        }

        return '';
    }

    /**
     * Make a boolean human-readable.
     *
     * @param bool $bool
     *   The boolean, like the parameter name says.
     *
     * @return string
     *   'TRUE' or 'FALSE' or an empty string when we are not dealing with a boolean.
     */
    protected function createReadableBoolean(bool $bool): string
    {
        if ($bool === true) {
            return 'TRUE';
        }

        return 'FALSE';
    }
}

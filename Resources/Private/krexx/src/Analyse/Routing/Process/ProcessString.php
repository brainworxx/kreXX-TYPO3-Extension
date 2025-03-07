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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Scalar\AbstractScalar;
use Brainworxx\Krexx\Analyse\Scalar\ScalarString;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\FileinfoDummy;
use finfo;

/**
 * Processing of strings.
 */
class ProcessString extends AbstractRouting implements
    ProcessInterface,
    ProcessConstInterface,
    CallbackConstInterface,
    ConfigConstInterface
{
    /**
     * The model we are currently working on.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * The buffer info class. We use it to get the mimetype from a string.
     *
     * @var \finfo|\Brainworxx\Krexx\Service\Misc\FileinfoDummy
     */
    protected $bufferInfo;

    /**
     * The deeper string analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\Scalar\AbstractScalar;
     */
    protected AbstractScalar $scalarString;

    /**
     * Length threshold, where we do a buffer-info analysis.
     *
     * @var int
     */
    protected int $bufferInfoThreshold = 20;

    /**
     * Caching of the setting SETTING_ANALYSE_SCALAR
     *
     * @var bool
     */
    protected bool $analyseScalar;

    /**
     * Inject the pool and initialize the buffer-info class.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Init the fileinfo class.
        if (class_exists(finfo::class, false)) {
            $this->bufferInfo = new finfo(FILEINFO_MIME);
        } else {
            // Use a "polyfill" dummy, tell the dev that we have a problem.
            $this->bufferInfo = $pool->createClass(FileinfoDummy::class);
            $pool->messages->addMessage('fileinfoNotInstalled');
        }

        $this->analyseScalar = $this->pool->config->getSetting(static::SETTING_ANALYSE_SCALAR);
        if ($this->analyseScalar) {
            $this->scalarString = $pool->createClass(ScalarString::class);
        }
    }

    /**
     * Is this one a string?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this a string?
     */
    public function canHandle(Model $model): bool
    {
        $this->model = $model;
        return is_string($model->getData());
    }

    /**
     * Render a dump for a string value.
     *
     * @return string
     *   The rendered markup.
     */
    public function handle(): string
    {
        $originalData = $data = $this->model->getData();

        // Check, if we are handling large string, and if we need to use a
        // preview (which we call "extra").
        // We also need to check for linebreaks, because the preview can not
        // display those.
        $length = $this->retrieveLengthAndEncoding($data);
        if ($length > 50 || strstr($data, PHP_EOL) !== false) {
            $cut = $this->pool->encodingService->encodeString(
                $this->pool->encodingService->mbSubStr($data, 0, 50)
            ) . static::UNKNOWN_VALUE;

            $data = $this->pool->encodingService->encodeString($data);

            $this->model->setHasExtra(true)
                ->setNormal($cut)
                ->setData($data);
        } else {
            $this->model->setNormal($this->pool->encodingService->encodeString($data));
        }

        if ($this->analyseScalar) {
            return $this->handleStringScalar($originalData);
        }

        return $this->pool->render->renderExpandableChild($this->dispatchProcessEvent($this->model));
    }

    /**
     * Inject the scalar analysis callback and handle possible recursions.
     *
     * @param string $originalData
     *   The original, unprocessed and unescape string.
     *
     * @return string
     *   The generated DOM.
     */
    protected function handleStringScalar(string $originalData): string
    {
        $this->scalarString->handle($this->model, $originalData);
        $domId = $this->model->getDomid();
        if ($domId !== '' && $this->pool->recursionHandler->isInMetaHive($domId)) {
            return $this->pool->render->renderRecursion($this->model);
        }

        $this->pool->recursionHandler->addToMetaHive($domId);
        return $this->pool->render->renderExpandableChild($this->dispatchProcessEvent($this->model));
    }

    /**
     * Retrieve the length and set the encoding in the model.
     *
     * @param string $data
     *   The string of which we want ot know the length and encoding.
     *
     * @return int
     *   the length of the string.
     */
    protected function retrieveLengthAndEncoding(string $data): int
    {
        $encoding = $this->pool->encodingService->mbDetectEncoding($data);
        $messages = $this->pool->messages;

        if ($encoding === false) {
            // Looks like we have a mixed encoded string.
            $length = $this->pool->encodingService->mbStrLen($data);
        } else {
            // Normal encoding, nothing special here.
            $length = $this->pool->encodingService->mbStrLen($data, $encoding);
        }

        // Long string or with broken encoding.
        if ($length > $this->bufferInfoThreshold) {
            // Let's see, what the buffer-info can do with it.
            static $bufferCache = [];
            if (!isset($bufferCache[$data])) {
                $bufferCache[$data] = $this->bufferInfo->buffer($data);
            }
            $this->model->addToJson($messages->getHelp('metaMimeTypeString'), $bufferCache[$data]);
        } elseif ($encoding === false) {
            // Short string with broken encoding.
            $this->model->addToJson($messages->getHelp('metaEncoding'), 'broken');
        } else {
            // Short string with normal encoding.
            $this->model->addToJson($messages->getHelp('metaEncoding'), $encoding);
        }

        $this->model->setType(static::TYPE_STRING)->addToJson($messages->getHelp('metaLength'), (string)$length);

        return $length;
    }
}

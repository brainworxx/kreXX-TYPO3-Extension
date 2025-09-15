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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\ContentBlocksRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\DomainRecordRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\RawRecordRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\SettingsRetriever;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionMethod;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;

/**
 * There are dynamic getter in TYPO3 13.4. We handle some of them here.
 *
 * @see \TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData::get()
 * @see \TYPO3\CMS\Core\Domain\Record::get()
 * @see \TYPO3\CMS\Core\Domain\RawRecord::get()
 * @see \TYPO3\CMS\Core\Settings\Settings::get()
 *
 * @event \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::callMe::start
 */
class DynamicGetter implements
    EventHandlerInterface,
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * The retriever for the dynamic getters.
     *
     * @var \Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\AbstractGetterRetriever[]
     *   The retriever is an array of getter retrievers, which can handle the
     *   given object.
     */
    protected array $retriever;

    /**
     * The class names of the retrievers.
     *
     * @var string[]
     */
    protected array $processersClassNames = [
        ContentBlocksRetriever::class,
        DomainRecordRetriever::class,
        RawRecordRetriever::class,
        SettingsRetriever::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        foreach ($this->processersClassNames as $class) {
            $this->retriever[] = $pool->createClass($class);
        }
    }

    /**
     * Adding the results from the dynamic getter to the output.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback|null $callback
     *   The original callback. Or null, when coming from the processing.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   There is no model available at the start event.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(?AbstractCallback $callback = null, ?Model $model = null): string
    {
        /** @var ReflectionClass $ref */
        $ref = $callback->getParameters()[static::PARAM_REF];
        $data = $ref->getData();
        $result = '';
        $done = [];
        $routing = $this->pool->routing;

        foreach ($this->retriever as $retriever) {
            // Check if the retriever can handle the object.
            if ($retriever->canHandle($data)) {
                foreach ($retriever->handle($ref) as $key => $value) {
                    // Iterate through the analysis result, and throw everything into the frontend.
                    $done[] = 'get' . ucfirst($key);
                    $result .= $routing->analysisHub(
                        $this->pool->createClass(Model::class)
                            ->setData($value)
                            ->setName($key)
                            ->setConnectorType(static::CONNECTOR_NORMAL_PROPERTY)
                            ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC)
                            ->setHelpid('fluidMagicGetter')
                    );
                }
                $this->removeFromGetter($done, $callback);

                // Add an HR after the dynamic getter output, just because.
                return $result . $this->pool->render->renderSingeChildHr();
            }
        }

        return $result;
    }

    /**
     * We remove duplicates from the getter analysis, because we already did this one.
     *
     * @param array $done
     *   Getter that are already done by the dynamic getter.
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback $callback
     *   The callback that we are currently processing. We need to remove the
     *   duplicates from the further getter analysis.
     */
    protected function removeFromGetter(array $done, AbstractCallback $callback): void
    {
        $parameters = $callback->getParameters();
        $getter = $parameters[static::PARAM_NORMAL_GETTER] ?? [];
        /** @var ReflectionMethod $reflectionMethod */
        foreach ($getter as $key => $reflectionMethod) {
            if (in_array($reflectionMethod->getName(), $done, true)) {
                unset($getter[$key]);
            }
        }
        $parameters[static::PARAM_NORMAL_GETTER] = array_values($getter);
        $callback->setParameters($parameters);
    }
}

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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMetaReflections;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ThroughMeta::class, '__construct')]
#[CoversMethod(ThroughMeta::class, 'callMe')]
#[CoversMethod(ThroughMeta::class, 'handleNoneReflections')]
#[CoversMethod(ThroughMeta::class, 'prepareModel')]
class ThroughMetaTest extends AbstractHelper
{
    public const  RENDER_EXPANDABLE_CHILD = 'renderExpandableChild';

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMeta::callMe::start';

    /**
     * @var string
     */
    protected $noneRefEevent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMeta::handleNoneReflections';

    /**
     * @var string
     */
    protected $refEventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMeta::';

    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta
     */
    protected $throughMeta;

    /**
     * @var RenderNothing
     */
    protected $renderNothing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->throughMeta = new ThroughMeta(Krexx::$pool);
        // Mock the redner class, to prevent further processing.
        $this->renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $this->renderNothing;
        // Overwrite the callbacks, to prevent further processing.
        Krexx::$pool->rewrite[ThroughMetaReflections::class] = CallbackNothing::class;
    }

    /**
     * Test the initializing of the workflow.
     */
    public function testConstruct()
    {
        $keysWithExtra = $this->retrieveValueByReflection('keysWithExtra', $this->throughMeta);
        $stuffToProcess = $this->retrieveValueByReflection('stuffToProcess', $this->throughMeta);
        $simpleAnalysisRouting = $this->retrieveValueByReflection('stuffToProcess', $this->throughMeta);

        // We simply assuethat there is some kind of workfow in there.
        $this->assertNotEmpty($keysWithExtra);
        $this->assertNotEmpty($stuffToProcess);
        $this->assertNotEmpty($simpleAnalysisRouting);
    }

    /**
     * Test with a comment string.
     */
    public function testCallMeComment()
    {
        $this->handleNoneReflections(
            'Comment',
            'Look at me, I\'m a comment!'
        );
    }

    /**
     * Test with a classname in a string
     */
    public function testCallMeClassName()
    {
        \Krexx::$pool->rewrite[Meta::class] = CallbackCounter::class;
        $ref = new ReflectionClass(static::class);
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                'Reflection' => $ref
            ]
        ];
        $expected = [[$this->throughMeta::PARAM_REF => $ref]];

        $this->throughMeta->setParameters($fixture)->callMe();

        $this->assertSame(1, CallbackCounter::$counter);
        $params = CallbackCounter::$staticParameters;
        $this->assertSame($expected, $params);
    }

    /**
     * Test with a decoded json
     */
    public function testCallMeDecodedJson()
    {
        $this->mockEventService([$this->startEvent, $this->throughMeta]);
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                'Decoded json' => json_decode('{"Friday": "the 13\'th"}')
            ],
            $this->throughMeta::PARAM_CODE_GEN_TYPE => Codegen::CODEGEN_TYPE_JSON_DECODE
        ];

        $routeNothing = new RoutingNothing(\Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;
        $this->throughMeta->setParameters($fixture)->callMe();
        $model = $routeNothing->model[0];
        $this->assertCount(1, $routeNothing->model);
        $this->assertEquals(Codegen::CODEGEN_TYPE_JSON_DECODE, $model->getCodeGenType());
    }

    /**
     * Test with a decoded base64 string
     */
    public function testCallMeDecodedBase64()
    {
        $this->mockEventService([$this->startEvent, $this->throughMeta]);
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                'Decoded base64' => base64_decode('Base64 strings are stupid.')
            ],
            $this->throughMeta::PARAM_CODE_GEN_TYPE => Codegen::CODEGEN_TYPE_BASE64_DECODE
        ];

        $routeNothing = new RoutingNothing(\Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;
        $this->throughMeta->setParameters($fixture)->callMe();
        $model = $routeNothing->model[0];
        $this->assertCount(1, $routeNothing->model);
        $this->assertEquals(Codegen::CODEGEN_TYPE_BASE64_DECODE, $model->getCodeGenType());
    }

    /**
     * Test with a declared-in string
     */
    public function testCallMeDeclaredIn()
    {
        $this->handleNoneReflections(
            'Declared in',
            'Some file with a line number.'
        );
    }

    /**
     * Test with attached source string.
     *
     * -> testCallMeSource
     * Insert Matrix joke here.
     */
    public function testCallMeSource()
    {
        $source = '// Doing stuff.' . PHP_EOL;
        $source .= 'echo \'something\';' . PHP_EOL;
        $source .= PHP_EOL;
        $source .= 'die();';

        $this->handleNoneReflections(
            'Source',
            $source
        );
    }

    /**
     * Handle the one liner strings.
     *
     * @param string $key
     * @param string $payload
     */
    protected function handleNoneReflections(string $key, string $payload)
    {
        $this->mockEventService(
            [$this->startEvent, $this->throughMeta],
            [$this->noneRefEevent . $key . $this->throughMeta::EVENT_MARKER_END, $this->throughMeta]
        );

        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                $key => $payload
            ]
        ];

        $this->throughMeta->setParameters($fixture)->callMe();

        $this->assertCount(1, $this->renderNothing->model[static::RENDER_EXPANDABLE_CHILD]);
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $this->renderNothing->model[static::RENDER_EXPANDABLE_CHILD][0];
        $this->assertEquals($payload, $model->getData());
        $this->assertEquals($key, $model->getName());
        $this->assertEquals($this->throughMeta::TYPE_REFLECTION, $model->getType());
        $this->assertEquals($this->throughMeta::UNKNOWN_VALUE, $model->getNormal());
        $this->assertTrue($model->hasExtra());
    }

    /**
     * Test the interface processing.
     */
    public function testCallMeInterfaces()
    {
        $this->handleReflections('Interfaces');
    }

    /**
     * Test the trait processing.
     */
    public function testCallMeTraits()
    {
        $this->handleReflections('Traits');
    }

    /**
     * Test the inheritance
     */
    public function testCallMeInherited()
    {
        $this->handleReflections('Inherited class');
    }

    /**
     * Handle reflection payloads.
     *
     * @param string $key
     *
     * @throws \ReflectionException
     */
    protected function handleReflections($key)
    {
        $this->mockEventService(
            [$this->startEvent, $this->throughMeta],
            [$this->refEventPrefix . $key, $this->throughMeta]
        );

        // Actually, we just pass thei one furhter down the rabbit hole, so we
        // might as well use a StdClass. But we don't.
        $payload = new ReflectionClass($this);
        $fixture = [
            $this->throughMeta::PARAM_DATA => [
                $key => $payload
            ]
        ];

        $this->throughMeta->setParameters($fixture)->callMe();

        $this->assertCount(1, $this->renderNothing->model[static::RENDER_EXPANDABLE_CHILD]);
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $this->renderNothing->model[static::RENDER_EXPANDABLE_CHILD][0];
        $this->assertEquals($key, $model->getName());
        $this->assertEquals(Krexx::$pool->messages->getHelp('classInternals'), $model->getType());
        $parameters = $model->getParameters();
        $this->assertEquals($payload, $parameters[$this->throughMeta::PARAM_DATA]);
    }
}

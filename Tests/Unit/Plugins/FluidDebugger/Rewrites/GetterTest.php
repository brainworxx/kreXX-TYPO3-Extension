<?php

namespace Unit\Plugins\FluidDebugger\Rewrites;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ConstantsFixture71;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class GetterTest extends AbstractHelper implements CallbackConstInterface
{
    /**
     * Testing the fluid rendering with no getter at all.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter::callMe
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter::retrieveMethodList
     *
     */
    public function testCallMeEmpty()
    {
        $getter = new Getter(\Krexx::$pool);
        $fixture = [static::PARAM_REF => new ReflectionClass(ConstantsFixture71::class)];
        $getter->setParameters($fixture);

        $this->assertSame('', $getter->callMe(), 'We expect no getter, hence no output, hence empty string.');
    }

    /**
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter::callMe
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter::retrieveMethodList
     */
    public function testCallMe()
    {
        $getter = new Getter(\Krexx::$pool);
        $ref = new ReflectionClass(GetterFixture::class);
        $fixture = [static::PARAM_REF => $ref];
        $getter->setParameters($fixture);
        \Krexx::$pool->rewrite[ThroughGetter::class] = CallbackCounter::class;

        $result = $getter->callMe();
        $this->assertStringNotContainsString('<span class="kname">Getter</span>', $result);
        $this->assertSame(1, CallbackCounter::$counter, 'Only call it once!');
        $params = CallbackCounter::$staticParameters[0];

        $this->assertSame($ref, $params[static::PARAM_REF], 'The fixture must be found in the class to test.');
        $this->assertInstanceOf(\ReflectionMethod::class, $params['normalGetter'][0]);
        $this->assertInstanceOf(\ReflectionMethod::class, $params['isGetter'][0]);
        $this->assertInstanceOf(\ReflectionMethod::class, $params['hasGetter'][0]);
    }
}

<?php

namespace Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ManualIteration;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ManualIteration::class, 'callMe')]
#[CoversMethod(AbstractCallback::class, 'dispatchStartEvent')]
#[CoversMethod(AbstractCallback::class, 'dispatchEventWithModel')]
#[CoversMethod(ManualIteration::class, '__construct')]
#[CoversMethod(ManualIteration::class, 'manualIteration')]
#[CoversMethod(ManualIteration::class, 'renderResult')]
class ManualIterationTest extends AbstractHelper
{
    /**
     * Test if the __construct injects the pool.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testConstruct(): void
    {
        $object = new ManualIteration(Krexx::$pool);

        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $object));
    }

    /**
     * Test the manual iteration.
     */
    public function testCellMeNormal()
    {
        $manualIteration = new ManualIteration(Krexx::$pool);
        // We are expecting 3 events here, the start event, the foreachKeys and
        // the foreachValues event.
        $this->mockEventService(
            [ManualIteration::class . '::callMe::start', $manualIteration],
            [ManualIteration::class . '::foreachKeys', $manualIteration],
            [ManualIteration::class . '::foreachValues', $manualIteration]
        );

        // We will not render anything.
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);

        // Make sure the emergency handler is mocked, so we can test the nesting level.
        $this->mockEmergencyHandler();
        Krexx::$pool->emergencyHandler->expects($this->exactly(2))
            ->method('upOneNestingLevel');
        Krexx::$pool->emergencyHandler->expects($this->exactly(2))
            ->method('downOneNestingLevel');

        // Make sure that the codegen handler does not allow any code generation.
        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->exactly(2))
            ->method('isCodegenAllowed')
            ->willReturn(true);

        $codegenMock->expects($this->exactly(4))
            ->method('setCodegenAllowed')
            ->with(...$this->withConsecutive(
                [false],
                [true],
                [false],
                [true]
            ));

        Krexx::$pool->codegenHandler = $codegenMock;


        $fixture = new \ArrayObject(['a' => 1, 'b' => 2, 'c' => 3]);
        $manualIteration->setParameters([CallbackConstInterface::PARAM_DATA => $fixture]);
        $manualIteration->callMe();

        $this->assertEquals(2, count(Krexx::$pool->render->model['renderExpandableChild']));
    }

    /**
     * Test the manual iteration with a large array, triggering a stop of the iteration.
     *
     * @return void
     */
    public function testCallMeLarge()
    {
        // Change the limit of the manual iteration to 2, so we can test the emergency break.
        ConfigSupplier::$overwriteValues[ConfigConstInterface::SETTING_ARRAY_COUNT_LIMIT] = 2;
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        new Config(\Krexx::$pool);

        $manualIteration = new ManualIteration(Krexx::$pool);

        // We are expecting 3 events here, the start event, the foreachKeys and
        // the foreachValues event.
        $this->mockEventService(
            [ManualIteration::class . '::callMe::start', $manualIteration],
            [ManualIteration::class . '::foreachKeys', $manualIteration],
            [ManualIteration::class . '::foreachValues', $manualIteration]
        );

        // We will not render anything.
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);

        // Make sure the emergency handler is mocked, so we can test the nesting level.
        $this->mockEmergencyHandler();
        Krexx::$pool->emergencyHandler->expects($this->exactly(2))
            ->method('upOneNestingLevel');
        Krexx::$pool->emergencyHandler->expects($this->exactly(2))
            ->method('downOneNestingLevel');

        $fixture = new \ArrayObject(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4]);
        $manualIteration->setParameters([CallbackConstInterface::PARAM_DATA => $fixture]);
        $manualIteration->callMe();

        // Check if the emergency break was triggered.
        $models = Krexx::$pool->render->model['renderExpandableChild'];
        $keys = $models[0]->getParameters()[CallbackConstInterface::PARAM_DATA];
        $values = $models[1]->getParameters()[CallbackConstInterface::PARAM_DATA];

        $this->assertEquals(2, count($models));
        $this->assertEquals(['a', 'b'], $keys);
        $this->assertEquals([0, 1], $values);
    }

    /**
     * Test the manual iteration with an error, triggering the error handling.
     *
     * @return void
     */
    public function testCallMeError()
    {
        $manualIteration = new ManualIteration(Krexx::$pool);

        // We are expecting only the start event here, because the error will stop the iteration.
        $this->mockEventService(
            [ManualIteration::class . '::callMe::start', $manualIteration],
        );

        // Make sure the emergency handler is mocked, so we can test the nesting level.
        $this->mockEmergencyHandler();
        Krexx::$pool->emergencyHandler->expects($this->never())
            ->method('upOneNestingLevel');
        Krexx::$pool->emergencyHandler->expects($this->never())
            ->method('downOneNestingLevel');

        // Create a fixture that will trigger an error when iterated.
        $fixture = $this->createMock(\Traversable::class);
        $fixture->method('valid')->willThrowException(new \Exception('Test exception'));
        $manualIteration->setParameters([CallbackConstInterface::PARAM_DATA => $fixture]);

        $this->assertEmpty(
            $manualIteration->callMe(),
            'The callMe method should return an empty string when an error occurs.'
        );
    }
}

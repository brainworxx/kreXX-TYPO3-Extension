<?php

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Getter;

use Brainworxx\Krexx\Analyse\Getter\ByRegExContainer;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class AbstractGetter extends AbstractHelper
{
    protected \Brainworxx\Krexx\Analyse\Getter\AbstractGetter $testSubject;

    /**
     * @param array $fixture
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $classReflection
     * @return void
     */
    protected function validateResults(array $fixture, ReflectionClass $classReflection)
    {
        foreach ($fixture as $items) {
            $result = $this->testSubject->retrieveIt(
                $items['reflection'],
                $classReflection,
                $items['prefix']
            );

            $message = 'Check the result: ' . $items['reflection']->getName();
            $this->assertEquals($items['expectation'], $result, $message);
            if ($items['hasResult']) {
                $this->assertTrue($this->testSubject->hasResult(), $message);
                if (!($this->testSubject instanceof ByRegExContainer)) {
                    // There is no reflection in the ByRegExContainer.
                    // And no, it is not "allergic" to garlic or sunlight.
                    $this->assertEquals(
                        $items['propertyName'],
                        $this->testSubject->getReflectionProperty()->getName(),
                        $message
                    );
                }
            } else {
                $this->assertFalse($this->testSubject->hasResult(), $message);
                $this->assertNull($this->testSubject->getReflectionProperty(), $message);
            }
        }
    }
}

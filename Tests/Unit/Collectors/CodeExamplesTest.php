<?php

namespace Brainworxx\Includekrexx\Tests\Unit\Collectors;

use Brainworxx\Includekrexx\Collectors\CodeExamples;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Tests\Helpers\ModuleTemplate;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(CodeExamples::class, 'assignData')]
#[CoversMethod(CodeExamples::class, 'createModeInstructions')]
#[CoversMethod(CodeExamples::class, 'retrieveFileContent')]
class CodeExamplesTest extends AbstractHelper
{
    /**
     * Test that the assignData method assigns the correct data to the view.
     */
    public function testAssignData()
    {
        $codeExamples = new CodeExamples();
        $viewMock = $this->createMock(ModuleTemplate::class);
        $viewMock->expects($this->once())->method('assign');
        $this->simulatePackage('includekrexx', 'Resources/Private/Examples/');

        $fileGetContentsMock = $this->getFunctionMock('Brainworxx\\Includekrexx\\Collectors', 'file_get_contents');
        $fileGetContentsMock->expects($this->exactly(7))
            ->willReturn('file content');

        $codeExamples->assignData($viewMock);
    }
}

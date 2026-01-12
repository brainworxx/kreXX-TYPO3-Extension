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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\Scalar;

use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ExtFilePath::class, 'canHandle')]
class ExtFilePathTest extends AbstractHelper
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load the TYPO3 language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 't3.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();
    }

    /**
     * Test the resolving of EXT: strings for their actual files.
     */
    public function testCanHandle()
    {
        $extFilePath = new ExtFilePath(\Krexx::$pool);

        // Test the first impression with a random string.
        $model = new Model(\Krexx::$pool);
        $this->assertFalse(
            $extFilePath->canHandle('random string', $model),
            'This is not a handleable string.'
        );
        $this->assertEmpty($model->getJson());

        $fixture = 'EXT:includekrexx/Tests/Fixtures/123458.Krexx.html';
        $model = new Model(\Krexx::$pool);
        // Let's try again and throw an error inside the GeneralUtility by not
        // simulation an installed extension.
        $this->assertFalse(
            $extFilePath->canHandle($fixture, $model),
            'This should trigger a \Throwable in the GeneralUtility'
        );
        $this->assertEmpty($model->getJson());

        // The real test starts here.
        $this->simulatePackage('includekrexx', 'includekrexx/');
        if (method_exists(UnitTestPackageManager::class, 'resolvePackagePath') === true) {
            $packageManagerMock = $this->createMock(UnitTestPackageManager::class);
            $packageManagerMock->expects($this->any())
                ->method('resolvePackagePath')
                ->willReturn('includekrexx/Tests/Fixtures/123458.Krexx.html');
            $this->setValueByReflection('packageManager', $packageManagerMock, ExtensionManagementUtility::class);
        }

        // Get the underlying class to find the file.
        $isFileMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Scalar\\String',
            'is_file'
        );
        $isFileMock->expects($this->once())->willReturn(true);

        // Get the underlying class to provide some info and not throw an error.
        $finfoMock = $this->createMock(\finfo::class);
        $finfoMock->expects($this->once())
            ->method('file')
            ->willReturn('just a file');
        $this->setValueByReflection('bufferInfo', $finfoMock, $extFilePath);

        // Make sure that the resolved path gets filtered.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method('filterFilePath')
            ->willReturn('Tests/Fixtures/123458.Krexx.html');
        \Krexx::$pool->fileService = $fileServiceMock;

        $this->assertFalse($extFilePath->canHandle($fixture, $model), 'Always false. We add the stuff to the model.');
        $extFilePath->callMe();

        // Look at the model.
        $jsonData = $model->getJson();
        $expectations = [
            'Resolved EXT path' => 'Tests/Fixtures/123458.Krexx.html',
            'Mimetype file' => 'just a file',
            'Error' => 'The file or directory does not exist.'
        ];
        $this->assertEquals(
            $expectations,
            $jsonData,
            'The file does exists, we just did not mock the file_exists(). We need to test the feedback about a missing file.'
        );
    }
}

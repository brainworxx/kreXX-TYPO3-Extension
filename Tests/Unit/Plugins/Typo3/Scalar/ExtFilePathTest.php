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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\Scalar;

use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Misc\File;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class ExtFilePathTest extends AbstractTest
{
    /**
     * Test the resolving of EXT: strings for their actual files.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath::canHandle
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
                ->will($this->returnValue('includekrexx/Tests/Fixtures/123458.Krexx.html'));
            $this->setValueByReflection('packageManager', $packageManagerMock, ExtensionManagementUtility::class);
        }

        // Get the underlying class to find the file.
        $isFileMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Scalar',
            'is_file'
        );
        $isFileMock->expects($this->once())
            ->with('includekrexx/Tests/Fixtures/123458.Krexx.html')
            ->will($this->returnValue(true));

        // Get the underlying class to provide some info and not throw an error.
        $finfoMock = $this->createMock(\finfo::class);
        $finfoMock->expects($this->once())
            ->method('file')
            ->will($this->returnValue('just a file'));
        $this->setValueByReflection('bufferInfo', $finfoMock, $extFilePath);

        // Make sure that the resolved path gets filtered.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method('filterFilePath')
            ->with('includekrexx/Tests/Fixtures/123458.Krexx.html')
            ->will($this->returnValue('Tests/Fixtures/123458.Krexx.html'));
        \Krexx::$pool->fileService = $fileServiceMock;

        $this->assertFalse($extFilePath->canHandle($fixture, $model), 'Always false. We add the stuff to the model.');
        $extFilePath->callMe();

        // Look at the model.
        $jsonData = $model->getJson();
        $expectations = [
            "Resolved EXT path" => "Tests/Fixtures/123458.Krexx.html",
            "Real path" => "n/a",
            "Mimetype file" => "just a file"
        ];
        $this->assertEquals($expectations, $jsonData);
    }
}

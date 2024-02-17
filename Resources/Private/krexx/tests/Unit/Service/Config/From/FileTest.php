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

namespace Brainworxx\Krexx\Tests\Unit\Service\Config\From;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\From\File as ConfigFromFile;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class FileTest extends AbstractHelper
{
    const SETTINGS = 'settings';

    /**
     * Fixture for injection in the configuration class.
     *
     * @var array
     */
    protected $fixture = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = [
            ConfigFromFile::SECTION_FE_EDITING => [
                ConfigFromFile::SETTING_SKIN => ConfigFromFile::RENDER_TYPE_CONFIG_NONE,
                ConfigFromFile::SETTING_DETECT_AJAX => ConfigFromFile::RENDER_TYPE_CONFIG_DISPLAY,
                ConfigFromFile::SETTING_NESTING_LEVEL => ConfigFromFile::RENDER_TYPE_CONFIG_FULL,
                ConfigFromFile::SETTING_DEBUG_METHODS => ConfigFromFile::RENDER_TYPE_CONFIG_FULL,
                ConfigFromFile::SETTING_ANALYSE_PRIVATE => 'garbage'
            ]
        ];
    }

    /**
     * Testing the setting of the validation class.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\File::__construct
     */
    public function testConstruct()
    {
        $config = new ConfigFromFile(Krexx::$pool);
        $this->assertSame(Krexx::$pool->config->validation, $this->retrieveValueByReflection('validation', $config));
    }

    /**
     * Test the loading of an ini file into the settings.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\File::loadFile
     */
    public function testLoadFileIni()
    {
        $this->fixture = ';' . PHP_EOL .
            '; kreXX CONFIGURATION FILE' . PHP_EOL .
            ';' . PHP_EOL .
            '; ------------------------------------------------------------------------------' . PHP_EOL .
            '[output]' . PHP_EOL .
            '' . PHP_EOL .
            '; Is kreXX actually active?' . PHP_EOL .
            '; Here you can disable kreXX on a global level without uninstalling it.' . PHP_EOL .
            'disabled = "false"' . PHP_EOL .
            ';disabled = "true"' . PHP_EOL;
        $somePathIni = 'some path.ini';
        $somePathJson = 'some path.json';
        $garbageFileIni = 'garbage file.ini';
        $garbageFileJson = 'garbage file.json';
        $notExistingFileIni = 'not existing file.ini';
        $notExistingFileJson = 'not existing file.json';
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(1))
            ->method('getFileContents')
            ->with($somePathIni, false)
            ->will($this->returnValue($this->fixture));

        $fileServiceMock->expects($this->any())
            ->method('fileIsReadable')
            ->will(
                $this->returnValueMap(
                    [
                        [$somePathIni, true],
                        [$somePathJson, false],
                        [$garbageFileIni, false],
                        [$garbageFileJson, false],
                        [$notExistingFileIni, false],
                        [$notExistingFileJson, false]
                    ]
                )
            );

        Krexx::$pool->fileService = $fileServiceMock;
        $config = new ConfigFromFile(Krexx::$pool);

        $config->loadFile('some path.');
        $this->assertEquals(
            [
                'output' => [
                    'disabled' => 'false'
                ]
            ],
            $this->retrieveValueByReflection(static::SETTINGS, $config)
        );

        $config->loadFile('garbage file.');
        $this->assertEquals([], $this->retrieveValueByReflection(static::SETTINGS, $config));

        $config->loadFile('not existing file.');
        $this->assertEquals([], $this->retrieveValueByReflection(static::SETTINGS, $config));
    }

    /**
     * Test the loading of an json file into the settings.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\File::loadFile
     */
    public function testLoadFileJson()
    {
        $setting = ['output' => ['disabled' => false]];
        $this->fixture = json_encode($setting);
        $somePathIni = 'some path.ini';
        $somePathJson = 'some path.json';

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(2))
            ->method('fileIsReadable')
            ->with(...$this->withConsecutive([$somePathIni], [$somePathJson]))
            ->will($this->returnValueMap([[$somePathIni, false], [$somePathJson, true]]));

        $fileServiceMock->expects($this->once())
            ->method('getFileContents')
            ->with($somePathJson)
            ->will($this->returnValue($this->fixture));


        Krexx::$pool->fileService = $fileServiceMock;
        $config = new ConfigFromFile(Krexx::$pool);
        $config->loadFile('some path.');
        $this->assertEquals(
            $setting,
            $this->retrieveValueByReflection(static::SETTINGS, $config)
        );
    }

    /**
     * Test the translating from the more human readable into the stuff for
     * the skin "engine".
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\File::getFeConfigFromFile
     */
    public function testGetFeConfigFromFile()
    {
        // Test without any file data.
        $config = new ConfigFromFile(Krexx::$pool);
        $this->assertNull($config->getFeConfigFromFile($config::SETTING_SKIN));

        // Test with some fixtures.
        $this->setValueByReflection(static::SETTINGS, $this->fixture, $config);
        $none = [
            ConfigFromFile::RENDER_TYPE => ConfigFromFile::RENDER_TYPE_NONE,
            ConfigFromFile::RENDER_EDITABLE => ConfigFromFile::VALUE_FALSE
        ];

        $this->assertEquals($none, $config->getFeConfigFromFile($config::SETTING_SKIN));
        $this->assertEquals(
            [
                ConfigFromFile::RENDER_TYPE => ConfigFromFile::RENDER_TYPE_SELECT,
                ConfigFromFile::RENDER_EDITABLE => ConfigFromFile::VALUE_FALSE
            ],
            $config->getFeConfigFromFile($config::SETTING_DETECT_AJAX)
        );
        $this->assertEquals(
            [
                ConfigFromFile::RENDER_TYPE => ConfigFromFile::RENDER_TYPE_INPUT,
                ConfigFromFile::RENDER_EDITABLE => ConfigFromFile::VALUE_TRUE
            ],
            $config->getFeConfigFromFile($config::SETTING_NESTING_LEVEL)
        );
        $this->assertNull(
            $config->getFeConfigFromFile($config::SETTING_DEBUG_METHODS),
            'Never! We ignore the setting completely.'
        );
        $this->assertEquals($none, $config->getFeConfigFromFile($config::SETTING_ANALYSE_PRIVATE), 'Fallback to do-not-edit');
    }

    /**
     * Testing the retrival and validation from the settings array.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\File::getConfigFromFile
     */
    public function testGetConfigFromFile()
    {
        $anotherGroup = 'another group';
        $knownSetting = 'known setting';
        $whatever = 'whatever';
        $groupy = 'groupy';
        $wrongSetting = 'wrong setting';
        $wrongValue = 'wrong value';

        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(2))
            ->method('evaluateSetting')
            ->with(...$this->withConsecutive(
                [$anotherGroup, $knownSetting, $whatever],
                [$groupy, $wrongSetting, $wrongValue]
            ))->will($this->returnValueMap([
                [$anotherGroup, $knownSetting, $whatever, true],
                [$groupy, $wrongSetting, $wrongValue, false]
            ]));
        Krexx::$pool->config->validation = $validationMock;

        $this->fixture = [
            $anotherGroup => [
                $knownSetting => $whatever
            ],
            $groupy => [
                $wrongSetting => $wrongValue
            ]
        ];
        $config = new ConfigFromFile(Krexx::$pool);
        $this->setValueByReflection(static::SETTINGS, $this->fixture, $config);

        $this->assertNull($config->getConfigFromFile('some group', 'unknown setting'));
        $this->assertEquals($whatever, $config->getConfigFromFile($anotherGroup, $knownSetting));
        $this->assertNull($config->getConfigFromFile($groupy, $wrongSetting));
    }
}

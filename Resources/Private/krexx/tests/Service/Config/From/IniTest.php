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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Service\Config\From;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class IniTest extends AbstractTest
{
    /**
     * Fixture for injection in the ini class.
     *
     * @var array
     */
    protected $fixture = [];

    protected function setUp()
    {
        parent::setUp();
        $this->fixture = [
            Ini::SECTION_FE_EDITING => [
                Ini::SETTING_SKIN => Ini::RENDER_TYPE_INI_NONE,
                Ini::SETTING_DETECT_AJAX => Ini::RENDER_TYPE_INI_DISPLAY,
                Ini::SETTING_NESTING_LEVEL => Ini::RENDER_TYPE_INI_FULL,
                Ini::SETTING_DEBUG_METHODS => Ini::RENDER_TYPE_INI_FULL,
                Ini::SETTING_ANALYSE_PRIVATE => 'garbage'
            ]
        ];
    }

    /**
     * Testing the setting of the validation class.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Ini::__construct
     */
    public function testConstruct()
    {
        $ini = new Ini(Krexx::$pool);
        $this->assertAttributeSame(Krexx::$pool->config->validation, 'validation', $ini);
    }

    /**
     * Test the loading of an ini file into the settings.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Ini::loadIniFile
     */
    public function testLoadIniFile()
    {
        $fixture = ';' . PHP_EOL .
                    '; kreXX CONFIGURATION FILE' . PHP_EOL .
                    ';' . PHP_EOL .
                    '; ------------------------------------------------------------------------------' . PHP_EOL .
                    '[output]' . PHP_EOL .
                    '' . PHP_EOL .
                    '; Is kreXX actually active?' . PHP_EOL .
                    '; Here you can disable kreXX on a global level without uninstalling it.' . PHP_EOL .
                    'disabled = "false"' . PHP_EOL .
                    ';disabled = "true"' . PHP_EOL;

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->withConsecutive(
                ['some path', false],
                ['garbage file', false],
                ['not existing file', false]
            )
            ->will(
                $this->returnValueMap(
                    [
                        ['some path', false, $fixture],
                        ['garbage file', false, 'Blargh'],
                        ['not existing file', false, '']
                    ]
                )
            );

        Krexx::$pool->fileService = $fileServiceMock;
        $ini = new Ini(Krexx::$pool);

        $ini->loadIniFile('some path');
        $this->assertAttributeEquals(
            [
                'output' => [
                    'disabled' => 'false'
                ]
            ],
            'iniSettings',
            $ini
        );

        $ini->loadIniFile('garbage file');
        $this->assertAttributeEquals([], 'iniSettings', $ini);

        $ini->loadIniFile('not existing file');
        $this->assertAttributeEquals([], 'iniSettings', $ini);
    }

    /**
     * Test the filtering of the configuration, if a value is editable.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Ini::getFeIsEditable
     */
    public function testGetFeIsEditable()
    {
        // Test with the normal fallback values, 'skin' vs. 'debugMethods'.
        $ini = new Ini(Krexx::$pool);
        $this->assertTrue($ini->getFeIsEditable($ini::SETTING_SKIN));
        $this->assertFalse($ini->getFeIsEditable($ini::SETTING_DEBUG_METHODS));

        // Test with some prepared settings.
        $this->setValueByReflection('iniSettings', $this->fixture, $ini);

        $this->assertFalse($ini->getFeIsEditable($ini::SETTING_SKIN));
        $this->assertFalse($ini->getFeIsEditable($ini::SETTING_DETECT_AJAX));
        $this->assertTrue($ini->getFeIsEditable($ini::SETTING_NESTING_LEVEL));
        $this->assertFalse($ini->getFeIsEditable($ini::SETTING_DEBUG_METHODS), 'Never!');
        $this->assertFalse($ini->getFeIsEditable($ini::SETTING_ANALYSE_PRIVATE), 'Fallback to do-not-edit');
    }

    /**
     * Test the translating from the more human readable into the stuff for
     * the skin "engine".
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Ini::getFeConfigFromFile
     */
    public function testGetFeConfigFromFile()
    {
        // Test without any file data.
        $ini = new Ini(Krexx::$pool);
        $this->assertNull($ini->getFeConfigFromFile($ini::SETTING_SKIN));

        // Test with some fixtures.
        $this->setValueByReflection('iniSettings', $this->fixture, $ini);
        $none = [
            Ini::RENDER_TYPE => Ini::RENDER_TYPE_NONE,
            Ini::RENDER_EDITABLE => Ini::VALUE_FALSE
        ];

        $this->assertEquals($none, $ini->getFeConfigFromFile($ini::SETTING_SKIN));
        $this->assertEquals(
            [
                Ini::RENDER_TYPE => Ini::RENDER_TYPE_SELECT,
                Ini::RENDER_EDITABLE => Ini::VALUE_FALSE
            ],
            $ini->getFeConfigFromFile($ini::SETTING_DETECT_AJAX)
        );
        $this->assertEquals(
            [
                Ini::RENDER_TYPE => Ini::RENDER_TYPE_INPUT,
                Ini::RENDER_EDITABLE => Ini::VALUE_TRUE
            ],
            $ini->getFeConfigFromFile($ini::SETTING_NESTING_LEVEL)
        );
        $this->assertNull(
            $ini->getFeConfigFromFile($ini::SETTING_DEBUG_METHODS),
            'Never! We ignore the setting completely.'
        );
        $this->assertEquals($none, $ini->getFeConfigFromFile($ini::SETTING_ANALYSE_PRIVATE), 'Fallback to do-not-edit');
    }

    /**
     * Testing the retrival and validation from the settings array.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Ini::getConfigFromFile
     */
    public function testGetConfigFromFile()
    {
        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(2))
            ->method('evaluateSetting')
            ->withConsecutive(
                ['another group', 'known setting', 'whatever'],
                ['groupy', 'wrong setting', 'wrong value']
            )
            ->will($this->returnValueMap([
                ['another group', 'known setting', 'whatever', true],
                ['groupy', 'wrong setting', 'wrong value', false]
            ]));
        Krexx::$pool->config->validation = $validationMock;

        $fixture = [
            'another group' => [
                'known setting' => 'whatever'
            ],
            'groupy' => [
                'wrong setting' => 'wrong value'
            ]
        ];
        $ini = new Ini(Krexx::$pool);
        $this->setValueByReflection('iniSettings', $fixture, $ini);

        $this->assertNull($ini->getConfigFromFile('some groupe', 'unknown setting'));
        $this->assertEquals('whatever', $ini->getConfigFromFile('another group', 'known setting'));
        $this->assertNull($ini->getConfigFromFile('groupy', 'wrong setting'));
    }
}

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

namespace Brainworxx\Krexx\Tests\Unit\Service\Config;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Config::class, 'getLanguageList')]
#[CoversMethod(Config::class, 'setPathToConfigFile')]
#[CoversMethod(Config::class, 'getPathToConfigFile')]
#[CoversMethod(Config::class, 'getSkinClass')]
#[CoversMethod(Config::class, 'getSkinDirectory')]
#[CoversMethod(Config::class, 'getSkinList')]
#[CoversMethod(Config::class, 'loadConfigValue')]
#[CoversMethod(Config::class, 'prepareModelWithFeSettings')]
#[CoversMethod(Config::class, 'isCookieValueAllowed')]
#[CoversMethod(Config::class, 'getSetting')]
#[CoversMethod(Config::class, 'setDisabled')]
#[CoversMethod(Config::class, '__construct')]
#[CoversMethod(Config::class, 'getChunkDir')]
#[CoversMethod(Config::class, 'getLogDir')]
#[CoversMethod(Config::class, 'getPathToConfigFile')]
#[CoversMethod(Config::class, 'checkEnabledStatus')]
class ConfigTest extends AbstractHelper
{
    public const  NOT_CLI = 'not cli';
    public const  FILE_CONFIG = 'fileConfig';
    public const  COOKIE_CONFIG = 'cookieConfig';
    public const  GET_CONFIG_FROM_COOKIES = 'getConfigFromCookies';
    public const  GET_CONFIG_FROM_FILE = 'getConfigFromFile';
    public const  KREXX_CONFIG_SETTINGS = 'Configuration file settings';
    public const  FALSE_STRING = 'false';

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockSapi()
    {
        return $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'php_sapi_name');
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        Pool::createPool();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test the initialisation of the configuration class.
     */
    public function testConstructNormal()
    {
        // Setup some fixtures.
        $chunkPath = 'chunks' . DIRECTORY_SEPARATOR . 'path';
        $configPath = 'config' . DIRECTORY_SEPARATOR . 'path.';
        $logPath = 'log path';
        $evilClassOne = 'some classname';
        $evilClassTwo = 'another classname';

        // Assign them
        Registration::setChunksFolder($chunkPath);
        Registration::setConfigFile($configPath);
        Registration::setLogFolder($logPath);
        Registration::addClassToDebugBlacklist($evilClassOne);
        Registration::addClassToDebugBlacklist($evilClassTwo);
        Registration::addClassToDebugBlacklist($evilClassOne);

        // Simulate a normal call (not cli or ajax).
        $this->mockSapi()
            ->expects($this->exactly(2))
            ->willReturn(static::NOT_CLI);

        // Create the test subject.
        $config = new Config(Krexx::$pool);

        // Setting of the pool
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $config));

        // Setting of the three folders
        $this->assertEquals($chunkPath, $config->getChunkDir());
        $this->assertEquals($configPath . 'json', $config->getPathToConfigFile());
        $this->assertEquals($logPath, $config->getLogDir());

        // Creation of the security class.
        $this->assertInstanceOf(Validation::class, $config->validation);

        // Assigning itself to the pool.
        $this->assertSame($config, Krexx::$pool->config);

        // Creation of the ini and cookie loader.
        $this->assertInstanceOf(File::class, $this->retrieveValueByReflection(static::FILE_CONFIG, $config));
        $this->assertInstanceOf(Cookie::class, $this->retrieveValueByReflection(static::COOKIE_CONFIG, $config));

        // kreXX should not be disabled.
        $this->assertEquals(false, $config->getSetting($config::SETTING_DISABLED));

        // Test the selected language. Should be the fallback,
        $this->assertEquals('en', $config->getSetting($config::SETTING_LANGUAGE_KEY));
    }

    /**
     * Test the browser output on cli.
     */
    public function testConstructCliBrowser()
    {
        $this->mockSapi()
            ->expects($this->exactly(2))
            ->willReturn('cli');
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with CLI and browser'
        );
    }

    /**
     * Test the browser output on ajax.
     */
    public function testConstructAjaxBrowser()
    {
        $this->mockSapi()
            ->expects($this->exactly(1))
            ->willReturn(static::NOT_CLI);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with ajax and browser'
        );
    }

    /**
     * Test the file output on cli.
     */
    public function testConstructCliFile()
    {
        $this->mockSapi()
            ->expects($this->exactly(1))
            ->willReturn('cli');
        ConfigSupplier::$overwriteValues = [
            Config::SETTING_DESTINATION => 'file'
        ];
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            false,
            $config->getSetting($config::SETTING_DISABLED),
            'Testing with CLI and file'
        );
    }

    /**
     * Test the access from different ips.
     */
    public function testConstructIpRange()
    {
        ConfigSupplier::$overwriteValues = [
            Config::SETTING_IP_RANGE => '1.2.3.4.5, 127.0.0.1'
        ];
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        $this->mockSapi()
            ->expects($this->exactly(4))
            ->willReturn(static::NOT_CLI);

        // Testing coming from the wrong ip
        $_SERVER['REMOTE_ADDR'] = '5.4.3.2.1';

        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            true,
            $config->getSetting(Config::SETTING_DISABLED),
            'Testing coming from the wrong ip'
        );

        // Testing coming from the right ip
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4.5';

        $config = new Config(Krexx::$pool);
        $this->assertEquals(
            false,
            $config->getSetting(Config::SETTING_DISABLED),
            'Testing coming from the right ip'
        );
    }

    /**
     * Test the disabling, directly in the configuration.
     */
    public function testSetDisabled()
    {
        $config = new Config(Krexx::$pool);
        $config->setDisabled(true);

        $setting = $config->settings[$config::SETTING_DISABLED];
        $this->assertEquals(true, $setting->getValue());
        $this->assertEquals('Internal flow', $setting->getSource());
    }

    /**
     * Test setting getter.
     */
    public function testGetSetting()
    {
        $config = new Config(Krexx::$pool);
        $config->settings[$config::SETTING_DESTINATION]->setValue('nowhere');
        $this->assertEquals('nowhere', $config->getSetting($config::SETTING_DESTINATION));
    }

    /**
     * Test the loading of a config value from fallback.
     */
    public function testLoadConfigValueFromFallback()
    {
        $config = new Config(Krexx::$pool);
        $iniMock = $this->createMock(File::class);
        $iniMock->expects($this->once())
            ->method(static::GET_CONFIG_FROM_FILE)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER)
            ->willReturn(null);
        // No values from the ini file.
        $iniMock->expects($this->once())
            ->method('getFeConfigFromFile')
            ->with($config::SETTING_ANALYSE_GETTER)
            ->willReturn(null);

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method(static::GET_CONFIG_FROM_COOKIES)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER)
            ->willReturn(null);

        // Inject them.
        $this->setValueByReflection(static::FILE_CONFIG, $iniMock, $config);
        $this->setValueByReflection(static::COOKIE_CONFIG, $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_ANALYSE_GETTER));
        $model = $config->settings[$config::SETTING_ANALYSE_GETTER];
        $this->assertEquals('Factory settings', $model->getSource());
        $this->assertEquals(true, $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_SELECT, $model->getType());
    }

    /**
     * Test the loading of a config value from ini.
     */
    public function testLoadConfigValueFromIni()
    {
        $config = new Config(Krexx::$pool);
        $someMethods = static::FALSE_STRING;

        $iniMock = $this->createMock(File::class);
        $iniMock->expects($this->exactly(2))
            ->method(static::GET_CONFIG_FROM_FILE)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER)
            ->willReturn($someMethods);

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method(static::GET_CONFIG_FROM_COOKIES)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER)
            ->willReturn(null);

        // Inject them.
        $this->setValueByReflection(static::FILE_CONFIG, $iniMock, $config);
        $this->setValueByReflection(static::COOKIE_CONFIG, $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_ANALYSE_GETTER));
        $model = $config->settings[$config::SETTING_ANALYSE_GETTER];
        $this->assertEquals(static::KREXX_CONFIG_SETTINGS, $model->getSource());
        $this->assertEquals(false, $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_SELECT, $model->getType());
    }

    /**
     * Test the loading of a config value from cookies.
     */
    public function testLoadConfigValueFromCookies()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(File::class);
        $iniMock->expects($this->never())
            ->method(static::GET_CONFIG_FROM_FILE)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER);

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method(static::GET_CONFIG_FROM_COOKIES)
            ->with($config::SECTION_METHODS, $config::SETTING_ANALYSE_GETTER)
            ->willReturn(static::FALSE_STRING);

        // Inject them.
        $this->setValueByReflection(static::FILE_CONFIG, $iniMock, $config);
        $this->setValueByReflection(static::COOKIE_CONFIG, $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_ANALYSE_GETTER));
        $model = $config->settings[$config::SETTING_ANALYSE_GETTER];
        $this->assertEquals('Local cookie settings', $model->getSource());
        $this->assertEquals(false, $model->getValue());
        $this->assertEquals($config::SECTION_METHODS, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_SELECT, $model->getType());
    }

    /**
     * Ignoring the cookie config, because the demanded value is uneditable.
     */
    public function testLoadConfigValueUneditable()
    {
        $config = new Config(Krexx::$pool);
        $someMethods = 'some methods';

        $iniMock = $this->createMock(File::class);
        $iniMock->expects($this->exactly(2))
            ->method(static::GET_CONFIG_FROM_FILE)
            ->with($config::SECTION_METHODS, $config::SETTING_DEBUG_METHODS)
            ->willReturn($someMethods);

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->exactly(1))
            ->method(static::GET_CONFIG_FROM_COOKIES)
            ->willReturn('read mail, real fast');

        // Inject them.
        $this->setValueByReflection(static::FILE_CONFIG, $iniMock, $config);
        $this->setValueByReflection(static::COOKIE_CONFIG, $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DEBUG_METHODS), 'Do we return $this?');
        $model = $config->settings[$config::SETTING_DEBUG_METHODS];
        $this->assertEquals(static::KREXX_CONFIG_SETTINGS, $model->getSource(), 'Correct source for the setting?');
        $this->assertEquals($someMethods, $model->getValue(), 'Correct values from the file?');
        $this->assertEquals($config::SECTION_METHODS, $model->getSection(), 'Correct section?');
        $this->assertEquals($config::RENDER_TYPE_INPUT, $model->getType(), 'Correct render type?');
    }

    /**
     * Testing that re-enabling kreXX with cookies does not work.
     */
    public function testLoadConfigValueReEnableWithCookies()
    {
        $config = new Config(Krexx::$pool);

        $iniMock = $this->createMock(File::class);
        $iniMock->expects($this->exactly(2))
            ->method(static::GET_CONFIG_FROM_FILE)
            ->with($config::SECTION_OUTPUT, $config::SETTING_DISABLED)
            ->willReturn('true');

        $cookieMock = $this->createMock(Cookie::class);
        $cookieMock->expects($this->once())
            ->method(static::GET_CONFIG_FROM_COOKIES)
            ->with($config::SECTION_OUTPUT, $config::SETTING_DISABLED)
            ->willReturn(static::FALSE_STRING);

        // Inject them.
        $this->setValueByReflection(static::FILE_CONFIG, $iniMock, $config);
        $this->setValueByReflection(static::COOKIE_CONFIG, $cookieMock, $config);

        $this->assertSame($config, $config->loadConfigValue($config::SETTING_DISABLED));
        $model = $config->settings[$config::SETTING_DISABLED];
        $this->assertEquals(static::KREXX_CONFIG_SETTINGS, $model->getSource());
        $this->assertEquals(true, $model->getValue(), 'It is still disabled!');
        $this->assertEquals($config::SECTION_OUTPUT, $model->getSection());
        $this->assertEquals($config::RENDER_TYPE_SELECT, $model->getType());
    }

    /**
     * Testing the overwriting of factory settings.
     */
    public function testLoadConfigValueWithPluginOverwrite()
    {
        $config = new Config(Krexx::$pool);
        Registration::addNewFallbackValue($config::SETTING_SKIN, 'noskin');
        $model = $config->loadConfigValue($config::SETTING_SKIN)
            ->settings[$config::SETTING_SKIN];
        $this->assertEquals('noskin', $model->getValue());
    }

    /**
     * Testing all the skin related getters.
     */
    public function testSkinStuff()
    {
        $skinName = 'some skin';
        $skinRenderClass = 'some class';
        $skinDirectory = 'some directory';

        // Create the fixture.
        Registration::registerAdditionalskin($skinName, $skinRenderClass, $skinDirectory);
        $config = new Config(Krexx::$pool);
        $config->settings[$config::SETTING_SKIN]->setValue($skinName);

         $expectations = [
             'smokygrey' => 'smokygrey',
             'hans' => 'hans',
             $skinName => $skinName
        ];

        $this->assertEquals($skinRenderClass, $config->getSkinClass());
        $this->assertEquals($skinDirectory, $config->getSkinDirectory());
        $this->assertEquals($expectations, $config->getSkinList());
    }

    /**
     * Test the feedback setter from the file loader.
     */
    public function testSetPathToConfigFile()
    {
        $fixture = 'whatever';
        $config = new Config(Krexx::$pool);
        $config->setPathToConfigFile($fixture);
        $this->assertEquals($fixture, $config->getPathToConfigFile());
    }

    /**
     * Test the retrieval of the language listst
     */
    public function testGetLanguageList()
    {
        $expectations = [
            'en' => 'English',
            'de' => 'Deutsch'
        ];
        $config = new Config(Krexx::$pool);
        $this->assertEquals($expectations, $config->getLanguageList());
    }
}

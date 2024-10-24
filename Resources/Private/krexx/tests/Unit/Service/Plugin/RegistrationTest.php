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

namespace Brainworxx\Krexx\Tests\Unit\Service\Plugin;

use Brainworxx\Krexx\Service\Plugin\NewSetting;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\View\Messages;

/**
 * Testing a static class . So. Much. Fun.
 *
 * @package Brainworxx\Krexx\Tests\Service\Plugin
 */
class RegistrationTest extends AbstractRegistration
{

    /**
     * Test the setting of a specific configuration file.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::setConfigFile
     */
    public function testSetConfigFile()
    {
        $path = 'some' . DIRECTORY_SEPARATOR . 'file.ini';
        Registration::setConfigFile($path);
        $this->assertEquals($path, SettingsGetter::getConfigFile());
    }

    /**
     * Test the setting of the chunks folder.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::setChunksFolder
     */
    public function testSetChunksFolder()
    {
        $path = 'extra chunky';
        Registration::setChunksFolder($path);
        $this->assertEquals($path, SettingsGetter::getChunkFolder());
    }

    /**
     * Test the setting of the log folder.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::setLogFolder
     */
    public function testSetLogFolder()
    {
        $path = 'logging';
        Registration::setLogFolder($path);
        $this->assertEquals($path, SettingsGetter::getLogFolder());
    }

    /**
     * Test the adding of blacklisted class / debug method combinations.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addMethodToDebugBlacklist
     */
    public function testAddMethodToDebugBlacklist()
    {
        $class = 'MyClass';
        $methodOne = 'doingStuff';
        $methodTwo = 'moreStuff';
        Registration::addMethodToDebugBlacklist($class, $methodOne);
        Registration::addMethodToDebugBlacklist($class, $methodTwo);
        Registration::addMethodToDebugBlacklist($class, $methodOne);

        $this->assertEquals([$class => [
            $methodOne,
            $methodTwo
        ]], SettingsGetter::getBlacklistDebugMethods());
    }

    /**
     * Test the adding of class names to the blacklisted debug class list.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addClassToDebugBlacklist
     */
    public function testAddClassToDebugBlacklist()
    {
        $classOne = 'SomeClass';
        $classTwo = 'AnotherClass';
        Registration::addClassToDebugBlacklist($classOne);
        Registration::addClassToDebugBlacklist($classTwo);
        Registration::addClassToDebugBlacklist($classOne);

        $this->assertEquals([$classOne, $classTwo], SettingsGetter::getBlacklistDebugClass());
    }

    /**
     * Test the adding of class rewrites.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addRewrite
     */
    public function testAddRewrite()
    {
        $classOne = 'OrgClass';
        $classTwo = 'NewClass';
        $classThree = 'MoreClasses';
        Registration::addRewrite($classOne, $classTwo);
        Registration::addRewrite($classOne, $classThree);

        $this->assertEquals([$classOne => $classThree], SettingsGetter::getRewriteList());
    }

    /**
     * Test the registering of the event handlers
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::registerEvent
     */
    public function testRegisterEvent()
    {
        $eventOne = 'some event';
        $eventTwo = 'another event';
        $classOne = 'EventClass1';
        $classTwo = 'EventClass2';
        $classThree = 'MoreClasses';
        Registration::registerEvent($eventOne, $classOne);
        Registration::registerEvent($eventOne, $classTwo);
        Registration::registerEvent($eventTwo, $classThree);

        $this->assertEquals([
            $eventOne => [$classOne => $classOne, $classTwo => $classTwo],
            $eventTwo => [$classThree => $classThree]
        ], SettingsGetter::getEventList());
    }

    /**
     * Test the registering of help files.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::registerAdditionalHelpFile
     */
    public function testRegisterAdditionalHelpFile()
    {
        $fileOne = 'help.ini';
        $fileTwo = 'lang.ini';
        Registration::registerAdditionalHelpFile($fileOne);
        Registration::registerAdditionalHelpFile($fileTwo);

        $this->assertEquals([$fileOne, $fileTwo], SettingsGetter::getAdditionalHelpFiles());
    }

    /**
     * Test the registering of an additional skin.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::registerAdditionalskin
     */
    public function testRegisterAdditionalskin()
    {
        $skinName = 'Dev skin';
        $renderClass = 'My\\Render\\Class';
        $pathToHtmlFiles = 'some path';
        Registration::registerAdditionalskin($skinName, $renderClass, $pathToHtmlFiles);

        $this->assertEquals([$skinName => [
            Registration::SKIN_CLASS => $renderClass,
            Registration::SKIN_DIRECTORY => $pathToHtmlFiles
        ]], SettingsGetter::getAdditionalSkinList());
    }

    /**
     * Test the registration of additional string processors.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addScalarStringAnalyser
     */
    public function testRegisterAdditionalScalarString()
    {
        Registration::addScalarStringAnalyser(static::class);
        $this->assertEquals([static::class], SettingsGetter::getAdditionalScalarString());
    }

    /**
     * Test the adding of new configuration definitions
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addNewSettings
     */
    public function testAddNewSetting()
    {
        $setting = new NewSetting();
        Registration::addNewSettings($setting);
        $this->assertEquals($setting, SettingsGetter::getNewSettings()[0]);
    }

    /**
     * Test the adding of a new fallback value
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addNewFallbackValue
     */
    public function testAddNewFallbackValue()
    {
        Registration::addNewFallbackValue('justa', 'value');
        $this->assertEquals('value', SettingsGetter::getNewFallbackValues()['justa']);
    }

    /**
     * Test the adding of a language.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addLanguage
     * @covers \Brainworxx\Krexx\Service\Config\Config::getLanguageList
     */
    public function testAddLanguage()
    {
        Registration::addLanguage('fr', 'français');
        $this->assertEquals(['fr' => 'français'], SettingsGetter::getAdditionalLanguages());

        // Test the French is available in the config.
        $expectation = [
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'français'
        ];

        $this->assertEquals($expectation, \Krexx::$pool->config->getLanguageList());
    }

    /**
     * Create a plugin mock.
     *
     * @return PluginConfigInterface
     */
    protected function createMockPlugin()
    {
        $pluginMock = $this->createMock(PluginConfigInterface::class);
        $pluginMock->expects($this->once())
            ->method('exec');

        /** @var PluginConfigInterface $pluginMock */
        return $pluginMock;
    }
    /**
     * Test the registering of a plugin.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::register
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::activatePlugin
     */
    public function testRegisterAndActivatePlugin()
    {
        $pluginMock = $this->createMockPlugin();
        Registration::register($pluginMock);
        $expectation = [
            get_class($pluginMock) => [
                Registration::CONFIG_CLASS => $pluginMock,
                Registration::IS_ACTIVE => false,
            ]
        ];
        $this->assertEquals($expectation, SettingsGetter::getPlugins());

        Registration::activatePlugin(get_class($pluginMock));
        $expectation[get_class($pluginMock)][Registration::IS_ACTIVE] = true;
        $this->assertEquals($expectation, SettingsGetter::getPlugins());
    }

    /**
     * Test the early return when deactivating an alredy deactivated plugin.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::deactivatePlugin
     */
    public function testDeactivatePluginDeactivated()
    {
        $this->setValueByReflection(static::LOG_FOLDER, 'whatever', $this->registration);
        Registration::deactivatePlugin('Test Plugin');
        $this->assertEquals('whatever', SettingsGetter::getLogFolder());
    }

    /**
     * Test the normal deactivation of a plugin.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::deactivatePlugin
     */
    public function testDeactivatePluginNormal()
    {
        // Register a plugin with a configuration class
        $pluginMock = $this->createMockPlugin();
        Registration::register($pluginMock);
        $pluginMockClassName = get_class($pluginMock);
        Registration::activatePlugin($pluginMockClassName);

        // Set some values and test if they got purged.
        $this->setValueByReflection(static::CHUNK_FOLDER, 'xxx', $this->registration);
        $this->setValueByReflection(static::LOG_FOLDER, 'yyy', $this->registration);
        $this->setValueByReflection(static::CONFIG_FILE, 'zzz', $this->registration);
        $this->setValueByReflection(static::BLACK_LIST_METHODS, [123], $this->registration);
        $this->setValueByReflection(static::BLACK_LIST_CLASS, [345], $this->registration);
        $this->setValueByReflection(static::ADD_HELP_FILES, [678], $this->registration);
        $this->setValueByReflection(static::REWRITE_LIST, [900], $this->registration);
        $this->setValueByReflection(static::EVENT_LIST, [987], $this->registration);
        $this->setValueByReflection(static::ADD_SKIN_LIST, [654], $this->registration);
        $this->setValueByReflection(static::ADD_SCALAR_STRING, [654], $this->registration);
        $this->setValueByReflection(static::ADD_SCALAR_STRING, ['barf' => 'digfood'], $this->registration);

        // Make sure we can test the changes to the pool.
        $oldConfig = Krexx::$pool->config;
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('readHelpTexts');
        Krexx::$pool->messages = $messageMock;

        // Deactivate it, and test the purging of the values.
        Registration::deactivatePlugin($pluginMockClassName);
        $pluginList = $this->retrieveValueByReflection('plugins', $this->registration);
        $this->assertFalse($pluginList[$pluginMockClassName][Registration::IS_ACTIVE]);
        // The configuration class writes fallback values into these variables,
        // hence they have only changed.
        $this->assertNotEquals('xxx', SettingsGetter::getChunkFolder());
        $this->assertNotEquals('yyy', SettingsGetter::getLogFolder());
        $this->assertNotEquals('zzz', SettingsGetter::getConfigFile());
        $this->assertEmpty(SettingsGetter::getBlacklistDebugClass());
        $this->assertEmpty(SettingsGetter::getBlacklistDebugClass());
        $this->assertEmpty(SettingsGetter::getAdditionalHelpFiles());
        $this->assertEmpty(SettingsGetter::getRewriteList());
        $this->assertEmpty(SettingsGetter::getEventList());
        $this->assertEmpty(SettingsGetter::getAdditionalSkinList());
        $this->assertEmpty(SettingsGetter::getAdditionalScalarString());
        $this->assertEmpty(SettingsGetter::getNewFallbackValues());

        // 3. Test the changes made to the pool
        $this->assertEmpty(Krexx::$pool->rewrite);
        $this->assertEmpty(Krexx::$pool->eventService->register);
        $this->assertNotSame($oldConfig, Krexx::$pool->config);
    }
}

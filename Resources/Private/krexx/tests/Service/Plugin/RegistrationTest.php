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

namespace Brainworxx\Krexx\Tests\Service\Plugin;

use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

/**
 * Testing a static class . So. Much. Fun.
 *
 * @package Brainworxx\Krexx\Tests\Service\Plugin
 */
class RegistrationTest extends AbstractTest
{
    /**
     * @var Registration
     */
    protected $registration;

    public function setUp()
    {
        parent::setUp();
        $this->registration = new Registration();
    }

    public function tearDown()
    {
        parent::tearDown();

        // Reset everything.
        $this->setValueByReflection('plugins', [], $this->registration);
        $this->setValueByReflection('chunkFolder', '', $this->registration);
        $this->setValueByReflection('logFolder', '', $this->registration);
        $this->setValueByReflection('configFile', '', $this->registration);
        $this->setValueByReflection('blacklistDebugMethods', [], $this->registration);
        $this->setValueByReflection('blacklistDebugClass', [], $this->registration);
        $this->setValueByReflection('additionalHelpFiles', [], $this->registration);
        $this->setValueByReflection('rewriteList', [], $this->registration);
        $this->setValueByReflection('eventList', [], $this->registration);
        $this->setValueByReflection('additionalSkinList', [], $this->registration);
    }

    /**
     * Test the setting of a specific configuration file.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::setConfigFile
     */
    public function testSetConfigFile()
    {
        $path = 'some file.ini';
        Registration::setConfigFile($path);
        $this->assertAttributeEquals($path, 'configFile', $this->registration);
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
        $this->assertAttributeEquals($path, 'chunkFolder', $this->registration);
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
        $this->assertAttributeEquals($path, 'logFolder', $this->registration);
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

        $this->assertAttributeEquals([$class => [
            $methodOne,
            $methodTwo
        ]], 'blacklistDebugMethods', $this->registration);
    }

    /**
     * Test the adding of class names to the blacklisted debug class list.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addClassToDebugBlacklist
     */
    public function testAddClassToDebugBlacklist()
    {
        $classOne = 'SomClass';
        $classTwo = 'AnotherClass';
        Registration::addClassToDebugBlacklist($classOne);
        Registration::addClassToDebugBlacklist($classTwo);
        Registration::addClassToDebugBlacklist($classOne);

        $this->assertAttributeEquals([$classOne, $classTwo], 'blacklistDebugClass', $this->registration);
    }

    /**
     * Test the adding of class rewrites.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::addRewrite
     */
    public function testAddRewrite()
    {
        $classOne = 'SomClass';
        $classTwo = 'AnotherClass';
        $classThree = 'MoreClasses';
        Registration::addRewrite($classOne, $classTwo);
        Registration::addRewrite($classOne, $classThree);

        $this->assertAttributeEquals([$classOne => $classThree], 'rewriteList', $this->registration);
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
        $classOne = 'SomClass';
        $classTwo = 'AnotherClass';
        $classThree = 'MoreClasses';
        Registration::registerEvent($eventOne, $classOne);
        Registration::registerEvent($eventOne, $classTwo);
        Registration::registerEvent($eventTwo, $classThree);

        $this->assertAttributeEquals([
            $eventOne => [$classOne => $classOne, $classTwo => $classTwo],
            $eventTwo => [$classThree => $classThree]
        ], 'eventList', $this->registration);
    }

    /**
     * Test the early return when deactivating an alredy deactivated plugin.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\Registration::deactivatePlugin
     */
    public function testDeactivatePluginDeactivated()
    {
        $this->setValueByReflection('logFolder', 'whatever', $this->registration);
        Registration::deactivatePlugin('Test Plugin');
        $this->assertAttributeEquals('whatever', 'logFolder', $this->registration);
    }
}

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

namespace Brainworxx\Krexx\Tests\Unit\Service\Plugin;

use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(SettingsGetter::class, 'getNewFallbackValues')]
#[CoversMethod(SettingsGetter::class, 'getAdditionalLanguages')]
#[CoversMethod(SettingsGetter::class, 'getNewSettings')]
#[CoversMethod(SettingsGetter::class, 'getAdditionalScalarString')]
#[CoversMethod(SettingsGetter::class, 'getAdditionalSkinList')]
#[CoversMethod(SettingsGetter::class, 'getPlugins')]
#[CoversMethod(SettingsGetter::class, 'getAdditionalHelpFiles')]
#[CoversMethod(SettingsGetter::class, 'getEventList')]
#[CoversMethod(SettingsGetter::class, 'getRewriteList')]
#[CoversMethod(SettingsGetter::class, 'getBlacklistDebugClass')]
#[CoversMethod(SettingsGetter::class, 'getBlacklistDebugMethods')]
#[CoversMethod(SettingsGetter::class, 'getLogFolder')]
#[CoversMethod(SettingsGetter::class, 'getChunkFolder')]
#[CoversMethod(SettingsGetter::class, 'getConfigFile')]
class SettingsGetterTest extends AbstractRegistration
{
    public const  TEST_THE_FALLBACK = 'Test the fallback value.';
    public const  TEST_THE_NEW_VALUE = 'Test the new value.';

    /**
     * Test the getting of the getter for the configuration file, and it's fallback.
     */
    public function testGetConfigFile()
    {
        $this->assertEquals(
            KREXX_DIR . 'config' . DIRECTORY_SEPARATOR . 'Krexx.',
            SettingsGetter::getConfigFile(),
            static::TEST_THE_FALLBACK
        );

        $this->setValueByReflection(static::CONFIG_FILE, 'filepath', $this->registration);
        $this->assertEquals(
            'filepath',
            SettingsGetter::getConfigFile(),
            static::TEST_THE_NEW_VALUE
        );
    }

    /**
     * Test the getting of the getter for the chunk folder, and it's fallback.
     */
    public function testGetChunkFolder()
    {
        $this->assertEquals(
            KREXX_DIR . 'chunks' . DIRECTORY_SEPARATOR,
            SettingsGetter::getChunkFolder(),
            static::TEST_THE_FALLBACK
        );

        $this->setValueByReflection(static::CHUNK_FOLDER, 'some/folder', $this->registration);
        $this->assertEquals(
            'some/folder',
            SettingsGetter::getChunkFolder(),
            static::TEST_THE_NEW_VALUE
        );
    }

    /**
     * Test the getting of the getter for the log folder, and it's fallback.
     */
    public function testGetLogFolder()
    {
        $this->assertEquals(
            KREXX_DIR . 'log' . DIRECTORY_SEPARATOR,
            SettingsGetter::getLogFolder(),
            static::TEST_THE_FALLBACK
        );

        $this->setValueByReflection(static::LOG_FOLDER, 'some/logging', $this->registration);
        $this->assertEquals(
            'some/logging',
            SettingsGetter::getLogFolder(),
            static::TEST_THE_NEW_VALUE
        );
    }

    /**
     * What the method name says.
     */
    public function testGetBlacklistDebugMethods()
    {
        $this->setValueByReflection(static::BLACK_LIST_METHODS, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getBlacklistDebugMethods());
    }

    /**
     * What the method name says.
     */
    public function testGetBlacklistDebugClass()
    {
        $this->setValueByReflection(static::BLACK_LIST_CLASS, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getBlacklistDebugClass());
    }

    /**
     * What the method name says.
     */
    public function testGetRewriteList()
    {
        $this->setValueByReflection(static::REWRITE_LIST, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getRewriteList());
    }

    /**
     * What the method name says.
     */
    public function testGetEventList()
    {
        $this->setValueByReflection(static::EVENT_LIST, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getEventList());
    }

    /**
     * What the method name says.
     */
    public function testGetAdditionalHelpFiles()
    {
        $this->setValueByReflection(static::ADD_HELP_FILES, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getAdditionalHelpFiles());
    }

    /**
     * What the method name says.
     */
    public function testGetPlugins()
    {
        $this->setValueByReflection(static::PLUGINS, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getPlugins());
    }

    /**
     * What the method name says.
     */
    public function testGetAdditionalSkinList()
    {
        $this->setValueByReflection(static::ADD_SKIN_LIST, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getAdditionalSkinList());
    }

    /**
     * Testing the getting of the scalar string analysis classes.
     */
    public function testGetAdditionalScalarString()
    {
         $this->setValueByReflection(static::ADD_SCALAR_STRING, [static::class], $this->registration);
         $this->assertEquals([static::class], SettingsGetter::getAdditionalScalarString());
    }

    /**
     * Testing the getting of new registered settings definitions.
     */
    public function testGetNewSettings()
    {
        $this->setValueByReflection(static::NEW_SETTINGS, [static::class], $this->registration);
        $this->assertEquals([static::class], SettingsGetter::getNewSettings());
    }

    /**
     * Testing the getting of additional languages.
     */
    public function testGetAdditionalLanguages()
    {
        $this->setValueByReflection(static::ADDITIONAL_LANGUAGES, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getAdditionalLanguages());
    }

    /**
     * Testing the getting of additional languages.
     */
    public function testGetNewFallbackValues()
    {
        $this->setValueByReflection(static::NEW_FALLBACK_VALUES, [1, 2, 3], $this->registration);
        $this->assertEquals([1, 2, 3], SettingsGetter::getNewFallbackValues());
    }
}

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

use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class AbstractRegistration extends AbstractHelper
{
    public const PLUGINS = 'plugins';
    public const CHUNK_FOLDER = 'chunkFolder';
    public const LOG_FOLDER = 'logFolder';
    public const CONFIG_FILE = 'configFile';
    public const BLACK_LIST_METHODS = 'blacklistDebugMethods';
    public const BLACK_LIST_CLASS = 'blacklistDebugClass';
    public const ADD_HELP_FILES = 'additionalHelpFiles';
    public const REWRITE_LIST = 'rewriteList';
    public const EVENT_LIST = 'eventList';
    public const ADD_SKIN_LIST = 'additionalSkinList';
    public const ADD_SCALAR_STRING = 'additionalScalarString';
    public const NEW_SETTINGS = 'newSettings';
    public const ADD_LANGUAGES = 'additionalLanguages';
    public const NEW_FALLBACK_VALUE = 'newFallbackValues';
    public const ADDITIONAL_LANGUAGES = 'additionalLanguages';
    public const NEW_FALLBACK_VALUES = 'newFallbackValues';

    /**
     * @var Registration
     */
    protected $registration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registration = new Registration();
    }

    /**
     * {@inheritDoc}
     *
     * Also reset the static values in the plugin registration.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset everything.
        $this->setValueByReflection(static::PLUGINS, [], $this->registration);
        $this->setValueByReflection(static::CHUNK_FOLDER, '', $this->registration);
        $this->setValueByReflection(static::LOG_FOLDER, '', $this->registration);
        $this->setValueByReflection(static::CONFIG_FILE, '', $this->registration);
        $this->setValueByReflection(static::BLACK_LIST_METHODS, [], $this->registration);
        $this->setValueByReflection(static::BLACK_LIST_CLASS, [], $this->registration);
        $this->setValueByReflection(static::ADD_HELP_FILES, [], $this->registration);
        $this->setValueByReflection(static::REWRITE_LIST, [], $this->registration);
        $this->setValueByReflection(static::EVENT_LIST, [], $this->registration);
        $this->setValueByReflection(static::ADD_SKIN_LIST, [], $this->registration);
        $this->setValueByReflection(static::ADD_SCALAR_STRING, [], $this->registration);
        $this->setValueByReflection(static::NEW_SETTINGS, [], $this->registration);
        $this->setValueByReflection(static::ADD_LANGUAGES, [], $this->registration);
        $this->setValueByReflection(static::NEW_FALLBACK_VALUE, [], $this->registration);
    }
}

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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

/**
 * Compatibility wrapper for unit tests.
 *
 * Meh, it's better than a reflection based implementation.
 */
if (version_compare(Version::id(), '6.99', '<')) {

    /**
     * Unit tests 6
     *
     * @package Brainworxx\Krexx\Tests\Helpers
     */
    abstract class TestCompatibility extends TestCase
    {
        protected function setUp()
        {
            $this->krexxUp();
            parent::setUp();
        }

        protected function tearDown()
        {
            $this->krexxDown();
            parent::tearDown();
        }

        protected function assertPreConditions()
        {
            $this->krexxertPreConditions();
        }

        protected function assertPostConditions()
        {
            $this->krexxertPostConditions();
        }

        public function assertStringContainsString(string $needle, string $haystack, string $message = '')
        {
            $this->assertContains($needle, $haystack, $message);
        }

        public function assertStringNotContainsString(string $needle, string $haystack, string $message = '')
        {
            $this->assertNotContains($needle, $haystack, $message);
        }

        abstract protected function krexxUp();

        abstract protected function krexxDown();

        abstract protected function krexxertPostConditions();

        abstract protected function krexxertPreConditions();
    }

} elseif (version_compare(Version::id(), '7.99', '<')) {
    /**
     * Unit tests 7
     *
     * @package Brainworxx\Krexx\Tests\Helpers
     */
    abstract class TestCompatibility extends TestCase
    {
        protected function setUp()
        {
            $this->krexxUp();
            parent::setUp();
        }

        protected function tearDown()
        {
            $this->krexxDown();
            parent::tearDown();
        }

        protected function assertPreConditions()
        {
            $this->krexxertPreConditions();
        }

        protected function assertPostConditions()
        {
            $this->krexxertPostConditions();
        }

        abstract protected function krexxUp();
        abstract protected function krexxDown();
        abstract protected function krexxertPostConditions();
        abstract protected function krexxertPreConditions();
    }
} else {

    /**
     * Unit Tests 8 and 9
     *
     * @package Brainworxx\Krexx\Tests\Helpers
     */
    abstract class TestCompatibility extends TestCase
    {
        protected function setUp(): void
        {
            $this->krexxUp();
            parent::setUp();
        }

        protected function tearDown(): void
        {
            $this->krexxDown();
            parent::tearDown();
        }

        protected function assertPreConditions(): void
        {
            $this->krexxertPreConditions();
        }

        protected function assertPostConditions(): void
        {
            $this->krexxertPostConditions();
        }

        abstract protected function krexxUp();
        abstract protected function krexxDown();
        abstract protected function krexxertPostConditions();
        abstract protected function krexxertPreConditions();
    }
}



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

namespace Brainworxx\Krexx\Tests\Fixtures;

class ContainerFixture
{
    protected array $container = ['value' => 1, 'stuff' => 'more stuff', 'barf' => 'borf'];

    protected string $nottaContaina;

    public function getValue()
    {
        return $this->container['value'];
    }

    public function getSomething()
    {
        return $this->container['stuff'];
    }

    /**
     * @return string
     */
    public function getStatic()
    {
        return 'static noise';
    }

    public function getWrongContainer()
    {
        return $this->noTainer['stuff'];
    }

    /**
     * It is terribly formatted!
     *
     * @return mixed
     */
    public function getBadFormatting()
    {
        return $this->container['barf'
        ]->someValue;
    }

    public function getAnError(): string
    {
        return $this->nottaContaina['barf'];
    }

    public function getSomethingElse()
    {
        if (isset($this->container['nutting'])) {
            return $this->container['something'];
        }

        return 'else';
    }

    /**
     * The parser should fail with this one.
     *
     * Also known as the comment section in Social Media!
     *
     * @return mixed
     */
    public function getBadComments()
    {
        return $this->container['stuff']->substuff['Twitch prime is not a crime'];
    }
}

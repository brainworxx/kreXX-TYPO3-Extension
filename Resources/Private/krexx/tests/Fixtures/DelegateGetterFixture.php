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

/**
 * This class is a wrapper around the DeepGetterFixture.
 */
class DelegateGetterFixture
{
    protected DeepGetterFixture $deepGetterFixture;

    protected bool $false = true;

    protected bool $analysisTrap = false;

    public function __construct()
    {
        $this->deepGetterFixture = new DeepGetterFixture();
    }

    /**
     * @return string
     */
    public function getMyPropertyOne(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }

        return $this->deepGetterFixture->getMyPropertyOne();
    }

    /**
     * @return string
     */
    public function getMyPropertyTwo(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->deepGetterFixture->getMyPropertyTwo();
    }

    /**
     * @return string
     */
    public function getMyPropertyThree(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->deepGetterFixture->getMyPropertyThree();
    }

    /**
     * @return string
     */
    public function getMyPropertyFour(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->deepGetterFixture->getMyPropertyFour();
    }

    /**
     * @return string
     */
    public function getMyPropertyFive(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->deepGetterFixture->getMyPropertyFive();
    }

    /**
     * @return string
     */
    public function getMyPropertySix(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->deepGetterFixture->getMyPropertySix();
    }

    /**
     * @return string
     */
    public function getMyPropertySeven(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return (float)$this->deepGetterFixture->getMyPropertySeven();
    }

    /**
     * @return string
     */
    public function getMyPropertyEight(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return (int) $this->deepGetterFixture->getMyPropertyEight();
    }

    /**
     * Test rudimentary source code parsing.
     *
     * @return string
     */
    public function getMyPropertyNine(): string
    {
        return $this->deepGetterFixture->getMyPropertyNine();
    }

    /**
     * @return null
     */
    public function getNull()
    {
        return null;
    }

    public function getAnError()
    {
        return $this->deepGetterFixture->getMyPropertyNine()->error();
    }

    public function getAnotherError()
    {
        return $this->stuff->error();
    }

    public function getLiterallyNoting()
    {
        return $this->deepGetterFixture->getLiterallyNoting();
    }

    public function getSomethingFromFalse()
    {
        return $this->false->barf();
    }

    public function getWrongMethod()
    {
        return $this->deepGetterFixture->foo();
    }
}

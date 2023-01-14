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

namespace Brainworxx\Krexx\Tests\Fixtures;

/**
 * A fixture for the getter analysis, with some "interesting" variation.
 *
 * @package Brainworxx\Krexx\Tests\Fixtures
 */
class DeepGetterFixture
{
    /**
     * Lower camel case variable.
     *
     * @var string
     */
    protected $myPropertyOne = 'one';

    /**
     * Lower camel case with a leading underscore.
     *
     * @var string
     */
    protected $_myPropertyTwo = 'two';

    /**
     * Upper camel case variable.
     *
     * @var string
     */
    protected $MyPropertyThree = 'three';

    /**
     * Upper camel case with a leading underscore.
     *
     * @var string
     */
    protected $_MyPropertyFour = 'four';

    /**
     * Everything is lover case.
     *
     * @var string
     */
    protected $mypropertyfive = 'five';

    /**
     * Lower case with a leading underscore.
     *
     * @var string
     */
    protected $_mypropertysix = 'six';

    /**
     * Snake case.
     *
     * @var string
     */
    protected $my_property_seven = 'seven';

    /**
     * Snakecase with a leading underscore.
     *
     * @var string
     */
    protected $_my_property_eight = 'eight';

    /**
     * . . . and now to something complete different.
     *
     * @var string
     */
    protected $somethingDifferent = 'nine';

    /**
     * Using the underscore before the getter for whatever reason.
     *
     * @var string
     */
    protected $myPropertyTen = 'ten';

    /**
     * Using a static value with a static getter.
     *
     * @var string
     */
    protected static $myStatic = 'eleven';

    /**
     * Property for the 'is'er.
     *
     * @var bool
     */
    protected $myPropertyTwelve = true;

    /**
     * Prperty for the 'has'er.
     *
     * @var bool
     */
    protected $myPropertyThirteen = false;

    /**
     * Just Null, and nothing more.
     *
     * @var null
     */
    protected $null;

    /**
     * A trap for the source code parsing.
     *
     * @var bool
     */
    protected $analysisTrap = false;

    /**
     * It's false, duh!
     *
     * @var bool
     */
    protected $false = false;

    /**
     * @return string
     */
    public function getMyPropertyOne(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }

        return $this->myPropertyOne;
    }

    /**
     * @return string
     */
    public function getMyPropertyTwo(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->_myPropertyTwo;
    }

    /**
     * @return string
     */
    public function getMyPropertyThree(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->MyPropertyThree;
    }

    /**
     * @return string
     */
    public function getMyPropertyFour(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->_MyPropertyFour;
    }

    /**
     * @return string
     */
    public function getMyPropertyFive(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->mypropertyfive;
    }

    /**
     * @return string
     */
    public function getMyPropertySix(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->_mypropertysix;
    }

    /**
     * @return string
     */
    public function getMyPropertySeven(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->my_property_seven;
    }

    /**
     * @return string
     */
    public function getMyPropertyEight(): string
    {
        if ($this->false) {
            return $this->analysisTrap;
        }
        return $this->_my_property_eight;
    }

    /**
     * Test rudimentary source code parsing.
     *
     * @return string
     */
    public function getMyPropertyNine(): string
    {
        return $this->somethingDifferent;
    }

    /**
     * Retrieve stuff from an underscored getter.
     *
     * @return string
     */
    public function _getMyPropertyTen(): string
    {
        return $this->myPropertyTen;
    }

    /**
     * Static getter in a class instance are just esoteric.
     *
     * @return string
     */
    static public function getMyStatic(): string
    {
        return static::$myStatic;
    }

    /**
     * Get null.
     *
     * @return null
     */
    public function getNull()
    {
        return $this->null;
    }

    /**
     * A getter that is calling another getter.
     *
     * @return string
     */
    public function getAnotherGetter():string
    {
        return $this->getMyPropertyEight();
    }

    /**
     * We should not be able to retrieve this one.
     */
    public function getLiterallyNoting()
    {
    }

    /**
     * Is it 12?
     *
     * @return bool
     */
    public function isMyPropertyTwelve(): bool
    {
        return $this->myPropertyTwelve;
    }

    /**
     * Has it 13? Probably not.
     *
     * @return bool
     */
    public function hasMyPropertyThirteen(): bool
    {
        return $this->myPropertyThirteen;
    }
}

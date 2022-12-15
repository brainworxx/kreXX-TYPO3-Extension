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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use stdClass;

#[\AllowDynamicProperties]
class ComplexPropertiesFixture extends ComplexPropertiesInheritanceFixture
{
    use MultitraitFixture;

    /**
     * Public string property.
     *
     * @var string
     */
    public $publicStringProperty = 'public property value';

    /**
     * Public integer property.
     *
     * @var int
     */
    public $publicIntProperty = 123;

    /**
     * Unset property is unsettling.
     *
     * @var string
     */
    public $unsetProperty = 'unset me';

    /**
     * Protected property
     *
     * @var string
     */
    protected $protectedProperty = 'pro tected';

    /**
     * Re-Declaration of a 'inherited' private property
     *
     * @var string
     */
    private $myProperty;

    /**
     * A rather long string.
     *
     * @var string
     */
    public $longString = 'gdgdfgonidoidsfogidfo idfsoigdofgoiudsfgoü dsfo goühisdfg ohisdfghioü sdoiühfg hoisdghoi sdfghiosdg sdfg dsg sdgsdf gdsg dsg';

    public static $publicStatic = 1;

    /**
     * Unset a property and dynamically declare a new one.
     */
    public function __construct()
    {
        unset($this->unsetProperty);
        $this->undeclaredProperty = 'dynamically declaration';
        $this->myProperty = 'asdf';
        $this->inheritedProtected = new stdClass();
    }
}

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

namespace Brainworxx\Krexx\Tests\Unit\Service\Plugin;

use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Service\Plugin\NewSetting;

class NewSettingTest extends AbstractTest
{
    /**
     * Test the setter and getter.
     *
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setSection
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setIsFeProtected
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setDefaultValue
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setIsEditable
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setRenderType
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setValidation
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::setName
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::isFeProtected
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::getFeSettings
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::getName
     * @covers \Brainworxx\Krexx\Service\Plugin\NewSetting::getSection
     */
    public function testSetterGetter()
    {
        $section = 'some section';
        $default = 'Default Value';
        $renderType = 'Render type';
        $validation = 'method name';
        $name = 'just a name';

        $newSetting = new NewSetting();
        $newSetting->setName($name)
            ->setValidation($validation)
            ->setRenderType($renderType)
            ->setIsEditable(true)
            ->setDefaultValue($default)
            ->setIsFeProtected(false)
            ->setSection($section);

        $this->assertFalse($newSetting->isFeProtected());
        $this->assertSame($name, $newSetting->getName());
        $this->assertSame($section, $newSetting->getSection());

        $expectation = [
            NewSetting::VALUE => $default,
            NewSetting::RENDER => [
                NewSetting::RENDER_TYPE => $renderType,
                NewSetting::RENDER_EDITABLE => NewSetting::VALUE_TRUE,
            ],
            NewSetting::EVALUATE => $validation,
            NewSetting::SECTION => $section
        ];
        $this->assertSame($expectation, $newSetting->getFeSettings());
    }
}

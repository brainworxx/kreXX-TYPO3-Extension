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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\EventHandlers;

use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\FlexFormParser;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use TYPO3\CMS\Core\Service\FlexFormService as FlexFromServiceCore;
use TYPO3\CMS\Extbase\Service\FlexFormService as FlexFromServiceExtbase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FlexFormParser::class, 'handle')]
#[CoversMethod(FlexFormParser::class, 'handle')]
#[CoversMethod(FlexFormParser::class, '__construct')]
class FlexFormParserTest extends AbstractHelper
{
    /**
     * Test the assigning of the pool
     */
    public function testConstruct()
    {
        $flexFormParser = new FlexFormParser(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $flexFormParser));
    }

    /**
     * Test the flex form parsing with a thrown error.
     */
    public function testHandleError()
    {
        $flexFormParser = new FlexFormParser(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $meta = [];
        $callback = new CallbackNothing(Krexx::$pool);
        $fixture = '';
        $model->addParameter(CallbackConstInterface::PARAM_VALUE, $fixture)
            ->addParameter(CallbackConstInterface::PARAM_DATA, $meta);
        $flexFormServiceMock = $this->createMock(FlexFromServiceCore::class);
        $flexFormServiceMock->expects($this->once())
            ->method('convertFlexFormContentToArray')
            ->willThrowException(new \Exception());
        $this->injectIntoGeneralUtility(FlexFromServiceCore::class, $flexFormServiceMock);
        $this->assertEquals(
            '',
            $flexFormParser->handle($callback, $model),
            'When throwing an error, we expect no results.'
        );
    }

    /**
     * Test the flex form parsing.
     */
    public function testHandle()
    {
        $flexFormParser = new FlexFormParser(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $meta = [];
        $callback = new CallbackNothing(Krexx::$pool);
        $fixture = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<T3FlexForms>
  <data>
    <sheet index="sDEF">
      <language index="lDEF">
        <field index="basePath">
          <value index="vDEF">fileadmin/</value>
        </field>
        <field index="pathType">
          <value index="vDEF">relative</value>
        </field>
        <field index="caseSensitive">
          <value index="vDEF">1</value>
        </field>
      </language>
    </sheet>
  </data>
</T3FlexForms>';
        $model->addParameter(CallbackConstInterface::PARAM_VALUE, $fixture)
            ->addParameter(CallbackConstInterface::PARAM_DATA, $meta);

        $flexFormServiceMock = $this->createMock(FlexFromServiceCore::class);

        $expectation = [
            'basePath' => 'fileadmin/',
            'pathType' => 'relative',
            'caseSensitive' => '1'
        ];
        $flexFormServiceMock->expects($this->once())
            ->method('convertFlexFormContentToArray')
            ->with($fixture)
            ->willReturn($expectation);
        $this->injectIntoGeneralUtility(FlexFromServiceCore::class, $flexFormServiceMock);

        // Run the test.
        $flexFormParser->handle($callback, $model);
        $result = $model->getParameters()[CallbackConstInterface::PARAM_DATA];


        $this->assertEquals($result['Decoded xml'], $expectation, 'Should look the same');

    }
}

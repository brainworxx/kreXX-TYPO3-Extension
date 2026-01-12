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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\EventHandlers;

use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException;
use StdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(DirtyModels::class, 'handle')]
#[CoversMethod(DirtyModels::class, 'createReadableBoolean')]
#[CoversMethod(DirtyModels::class, '__construct')]
class DirtyModelsTest extends AbstractHelper
{
    /**
     * Subscribing our class to test to the right event.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Subscribing.
        Registration::registerEvent(
            ProcessObject::class . PluginConfigInterface::START_PROCESS,
            DirtyModels::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Load the TYPO3 language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 't3.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();
    }

    /**
     * Test the assigning of the pool.
     */
    public function testConstruct()
    {
        $debugMethod = new DirtyModels(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $debugMethod));
    }

    /**
     * Test the additional stuff.
     */
    public function testHandle()
    {
        $this->mockEmergencyHandler();
        $modelMock = $this->createMock(AbstractDomainObject::class);
        $modelMock->expects($this->once())
            ->method('_isDirty')
            ->will($this->throwException(new TooDirtyException()));
        $modelMock->expects($this->once())
            ->method('_isClone')
            ->willReturn(false);
        $modelMock->expects($this->once())
            ->method('_isNew')
            ->willReturn(true);

        $model = new Model(Krexx::$pool);
        $model->setData($modelMock);

        Krexx::$pool->routing->analysisHub($model);
        $this->assertEquals(
            [
                'Is dirty' => 'TRUE, even the UID was modified!',
                'Is a clone' => 'FALSE',
                'Is a new' => 'TRUE'
            ],
            $model->getJson()
        );

        $fixture = new StdClass();

        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        Krexx::$pool->routing->analysisHub($model);
        $this->assertEquals([], $model->getJson());
    }

    /**
     * Test the exception handling
     */
    public function testHandleException()
    {
        $this->mockEmergencyHandler();
        $modelMock = $this->createMock(AbstractDomainObject::class);
        $modelMock->expects($this->once())
            ->method('_isDirty')
            ->will($this->throwException(new \Exception()));
        $modelMock->expects($this->never())
            ->method('_isClone');
        $modelMock->expects($this->never())
            ->method('_isNew');
        $model = new Model(Krexx::$pool);
        $model->setData($modelMock);

        Krexx::$pool->routing->analysisHub($model);
        $this->assertEquals([], $model->getJson(), 'It si empty, because the exception was thrown.');
    }
}

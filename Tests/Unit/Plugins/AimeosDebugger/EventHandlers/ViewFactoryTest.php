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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods as AnalyseMethods;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Aimeos\MW\View\Standard as StandardView;
use Aimeos\MW\View\Helper\Csrf\Standard as CsrfHelper;


class ViewFactoryTest extends AbstractTest
{
    /**
     * Test the handling of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory::__construct
     */
    public function testConstruct()
    {
        $properties = new ViewFactory(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $properties));
    }

    /**
     * Test the analysis of the view factory helper classes.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory::handle
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory::retrieveHelperList
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory::retrieveHelpers
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory::retrievePossibleOtherHelpers
     */
    public function testHandle()
    {
        // Subscribing.
        Registration::registerEvent(
            AnalyseMethods::class . PluginConfigInterface::START_EVENT,
            ViewFactory::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Inject the render nothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Create the fixture.
        $aimeosView = new StandardView();
        $csrfHelper = new CsrfHelper($aimeosView);
        $aimeosView->addHelper('Csrf', $csrfHelper);

        $fixture = [
            ViewFactory::PARAM_DATA => $aimeosView,
            ViewFactory::PARAM_NAME => 'viewFactory',
            ViewFactory::PARAM_REF => new ReflectionClass($aimeosView)
        ];

        $analyseMethods = new AnalyseMethods(Krexx::$pool);
        $this->triggerStartEvent($analyseMethods->setParameters($fixture));

        // Assert the result.
        /** @var \Brainworxx\Krexx\Analyse\Model $instantiatedViewHelpers */
        $instantiatedViewHelpers = $renderNothing->model['renderExpandableChild'][0];
        $this->assertSame($csrfHelper, $instantiatedViewHelpers->getParameters()[ViewFactory::PARAM_DATA]['Csrf']);

        /** @var \Brainworxx\Krexx\Analyse\Model $viewFactory */
        $viewFactory = $renderNothing->model['renderExpandableChild'][1];
        $this->assertGreaterThan(10, count($viewFactory->getParameters()[ViewFactory::PARAM_DATA]));
        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($viewFactory->getParameters()[ViewFactory::PARAM_DATA] as $reflectionMethod) {
            $this->assertInstanceOf(\ReflectionMethod::class, $reflectionMethod);
        }
        $this->assertTrue($viewFactory->getParameters()[ViewFactory::PARAM_IS_FACTORY_METHOD]);
    }
}
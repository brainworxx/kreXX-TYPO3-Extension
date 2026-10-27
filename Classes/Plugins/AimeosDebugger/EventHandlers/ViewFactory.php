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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Aimeos\Base\View\Iface as BaseViewInterface;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughClassList;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughMethods;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionClass;
use ReflectionException;

/**
 * Resolving the Aimoes view helper factory. Not to be confused with fluid
 * viewhelpers.
 *
 * The Aimeos view has a factory method in the Aimeos\MW\View\Standard.
 * When calling $view->someNamespace() it does the following:
 * - trying to instantiate \Aimeos\MWView\Helper\SomeNamespace\Standard
 * - cache the class instance in this->helper[]
 * - call the class method transform() on this instance
 * It is also possible to inject classes into the view from the outside.
 *
 * We need to analyse the already available helper classes, and give hints
 * about the other classes that can be called this way.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe::start
 *
 * @uses object $data
 *   The object we ara currently analysing.
 */
class ViewFactory extends AbstractEventHandler implements CallbackConstInterface
{
    /**
     * @var string
     */
    protected const METHOD = 'transform';

    /**
     * List of all retrieved helper classes from the view.
     *
     * @var object[]|null
     */
    protected ?array $helpers = [];

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Analysing the magical factory of the Aimeos view.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback|null $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(?AbstractCallback $callback = null, ?Model $model = null): string
    {
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $callback->getParameters()[static::PARAM_REF];
        /** @var BaseViewInterface $data */
        $data = $ref->getData();

        // Test if we are facing an Aimeos view.
        if (!($data instanceof BaseViewInterface)) {
            // This is not the view we are looking for.
            // Early return.
            return '';
        }

        // Analyse the transform method of all possible view helpers.
        // Analyse the already existing view helpers.
        try {
            return $this->retrieveHelpers(data: $data, ref: $ref) .
                $this->retrievePossibleOtherHelpers();
        } catch (ReflectionException) {
            // Do nothing. We skip this step.
        }

        return '';
    }

    /**
     * Retrieve the already instantiated helper classes from the view.
     *
     * @param BaseViewInterface $data
     *   The class we are analysing.
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     *
     * @return string
     *   The generated html.
     */
    protected function retrieveHelpers(BaseViewInterface $data, ReflectionClass $ref): string
    {
        $result = '';
        if ($ref->hasProperty(name:'helper')) {
            // Got our helpers right here.
            // Let's hope that other implementations use the same variable name.
            // We store the helpers here, because we need them later for the
            // actual method analysis. It is possible to inject helpers.
            // This means that the helper may be someone else as it should be,
            // according to the key.
            $this->helpers = $this->retrieveProperty(reflectionClass: $ref, objectName: 'helper', object: $data);
            if (is_array(value: $this->helpers) && !empty($this->helpers)) {
                // We got ourselves some classes to analyse.
                $this->pool->codegenHandler->setCodegenAllowed(bool:false);
                $result = $this->pool->render->renderExpandableChild(
                    model: $this->pool->createClass(classname: Model::class)
                        ->setName(name: $this->pool->messages->getHelp('aimeosViewHelpers'))
                        ->setType(type: 'class internals magical factory')
                        ->addParameter(name: static::PARAM_DATA, value: $this->helpers)
                        ->setHelpid(helpId: 'aimeosViewExisting')
                        ->injectCallback(object: $this->pool->createClass(classname: ThroughClassList::class))
                );
                $this->pool->codegenHandler->setCodegenAllowed(bool: true);
            }
        }

        return $result;
    }

    /**
     * Get a list of all possible other helpers, and analyse their transform()
     * class method
     *
     * @throws \ReflectionException
     *
     * @return string
     *   The generated html.
     */
    protected function retrievePossibleOtherHelpers(): string
    {
        // The main problem here is, that we can not simply get all known
        // classes, filter them for their namespace and analyse them.
        // Thanks to composer, these may or may not be loaded.
        // Normally getting the composer autoload path would be a good
        // idea, but we may face several composer instances.
        // But, we can do the following:
        // 1.) Retrieve the directory of the Base view helper.
        // 2.) Get all known view helper classes via directory listing.
        // 3.) Replace the list with the ones from the already existing helpers
        // 4.) Analyse them all!

        // Replace the ones in there with the already instantiated ones from the
        // helper array.
        foreach ($this->helpers as $key => $helperObject) {
            $ref = new ReflectionClass(objectOrClass: $helperObject);
            $reflectionList[$key] = $ref->getMethod(name: static::METHOD);
        }

        if (empty($reflectionList)) {
            return '';
        }

        // Dump them, like there is no tomorrow.
        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass(classname: Model::class)
                ->setName(name: $this->pool->messages->getHelp('aimeosViewFactory'))
                ->setType(type: 'class internals view magic')
                ->addParameter(name: static::PARAM_DATA, value: $reflectionList)
                // Tell the callback to pass on the factory name.
                // Thanks to the magic factory, we must use this one.
                ->addParameter(name: static::PARAM_IS_FACTORY_METHOD, value: true)
                ->setHelpid(helpId: 'aimeosViewInfo')
                ->injectCallback(object: $this->pool->createClass(classname: ThroughMethods::class))
        );
    }
}

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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Aimeos\Base\View\Helper\Base as BaseHelperBase;
use Aimeos\Base\View\Iface as BaseViewInterface;
use Aimeos\MW\View\Helper\Base as HelperBase;
use Aimeos\MW\View\Iface as ViewInterface;
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
 * @uses object $data
 *   The object we ara currently analysing.
 */
class ViewFactory extends AbstractEventHandler implements CallbackConstInterface
{
    /**
     * The namespace of the view helpers.
     *
     * @var string
     */
    protected const AI_NAMESPACE = 'Aimeos\\MW\\View\\Helper\\';

    /**
     * @var string
     */
    protected const METHOD = 'transform';

    /**
     * @var string
     */
    protected const STANDARD = '\\Standard';

    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected Pool $pool;

    /**
     * List of all retrieved helper classes from the view.
     *
     * @var object[]
     */
    protected array $helpers = [];

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Analysing the magical factory of the Aimeos view.
     *
     * @param AbstractCallback $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, ?Model $model = null): string
    {
        $params = $callback->getParameters();
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $params[static::PARAM_REF];
        /** @var ViewInterface $data */
        $data = $ref->getData();

        // Test if we are facing an Aimeos view.
        if (!($data instanceof ViewInterface) && !($data instanceof BaseViewInterface)) {
            // This is not the view we are looking for.
            // Early return.
            return '';
        }

        // Analyse the transform method of all possible view helpers.
        // Analyse the already existing view helpers.
        $result = '';
        try {
            $result = $this->retrieveHelpers($data, $ref);
            $result .= $this->retrievePossibleOtherHelpers();
        } catch (ReflectionException $e) {
            // Do nothing. We skip this step.
        }

        return $result;
    }

    /**
     * Retrieve the already instantiated helper classes from the view.
     *
     * @param ViewInterface|BaseViewInterface $data
     *   The class we are analysing.
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     *
     * @return string
     *   The generated html.
     */
    protected function retrieveHelpers($data, ReflectionClass $ref): string
    {
        $result = '';

        if ($ref->hasProperty('helper')) {
            // Got our helpers right here.
            // Let's hope that other implementations use the same variable name.
            // We store the helpers here, because we need them later for the
            // actual method analysis. It is possible to inject helpers.
            // This means that the helper may be someone else as it should be,
            // according to the key.
            $this->helpers = $this->retrieveProperty($ref, 'helper', $data);
            if (is_array($this->helpers) && !empty($this->helpers)) {
                // We got ourselves some classes to analyse.
                $this->pool->codegenHandler->setCodegenAllowed(false);
                $result .= $this->pool->render->renderExpandableChild(
                    $this->pool->createClass(Model::class)
                        ->setName($this->pool->messages->getHelp('aimeosViewHelpers'))
                        ->setType('class internals magical factory')
                        ->addParameter(static::PARAM_DATA, $this->helpers)
                        ->setHelpid('aimeosViewExisting')
                        ->injectCallback($this->pool->createClass(ThroughClassList::class))
                );
                $this->pool->codegenHandler->setCodegenAllowed(true);
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

        // Get a list of the core view helpers
        // Get the core view helpers directory

        if (class_exists(HelperBase::class)) {
            $ref = new ReflectionClass(HelperBase::class);
        } else {
            $ref = new ReflectionClass(BaseHelperBase::class);
        }


        // Scan the main view helpers, to get a first impression.
        $reflectionList = $this->retrieveHelperList(dirname($ref->getFileName()));

        // Replace the ones in there with the already instantiated ones from the
        // helper array.
        foreach ($this->helpers as $key => $helperObject) {
            $ref = new ReflectionClass($helperObject);
            $reflectionList[$key] = $ref->getMethod(static::METHOD);
        }

        if (empty($reflectionList)) {
            return '';
        }

        // Dump them, like there is no tomorrow.
        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass(Model::class)
                ->setName($this->pool->messages->getHelp('aimeosViewFactory'))
                ->setType('class internals view magic')
                ->addParameter(static::PARAM_DATA, $reflectionList)
                // Tell the callback to pass on the factory name.
                // Thanks to the magic factory, we must use this one.
                ->addParameter(static::PARAM_IS_FACTORY_METHOD, true)
                ->setHelpid('aimeosViewInfo')
                ->injectCallback($this->pool->createClass(ThroughMethods::class))
        );
    }

    /**
     * Retrieve all helper class reflections from a directory.
     *
     * @param string $directory
     *   The directory we re processing.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionMethod[]
     *   The list with the reflections.
     */
    protected function retrieveHelperList(string $directory): array
    {
        $iface = static::AI_NAMESPACE . 'Iface';
        if (!class_exists($iface)) {
            // Since 2022, all view helpers are present in the view, so we do not need
            // to scan for them anymore.
            // Early return.
            return [];
        }

        $reflectionList = [];
        foreach (scandir($directory) as $dir) {
            $className = static::AI_NAMESPACE . $dir . static::STANDARD;
            if (empty($this->helpers[$dir]) && is_a($className, $iface, true)) {
                // We did not process this one before.
                $reflectionList[lcfirst($dir)] = (new ReflectionClass($className))
                    ->getMethod(static::METHOD);
            }
        }

        return $reflectionList;
    }
}

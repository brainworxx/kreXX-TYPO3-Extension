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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Aimeos\MW\View\Iface;

/**
 * Resolving the Aimoes viewhelper factory. Not to be confused with fluid
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
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 */
class ViewFactory implements EventHandlerInterface
{
    /**
     * The namespace of the view helpers.
     */
    const NAMESPACE = 'Aimeos\\MW\\View\\Helper\\';
    const METHOD = 'transform';

    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * List of all retreived helper classes from the view.
     *
     * @var array
     */
    protected $helpers = array();

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
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $result = '';
        $params = $callback->getParameters();
        $data = $params['ref']->getData();

        /** @var \ReflectionClass $ref */
        $ref = $params['ref'];

        // Test if we are facing an Aimeos view.
        if (is_a($data, 'Aimeos\\MW\\View\\Iface') === false) {
            // This is not he view we are looking for.
            // Early return.
            return $result;
        }

        // Analyse the already existing view helpers
        $result .= $this->retrieveHelpers($data, $ref);

        // Analyse the transform method of all possible view helpers.
        return $this->retreivePossibleOtherHelpers() . $result;
    }

    /**
     * Retrieve the already instanciated helper classes from the view.
     *
     * @param \Aimeos\MW\View\Iface $data
     *   The class we are analysing.
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     * @return string
     *   The generated html.
     */
    protected function retrieveHelpers(Iface $data, \ReflectionClass $ref)
    {
        $result = '';

        if ($ref->hasProperty('helper')) {
            // Got our helpers right here.
            // Lets hope that other implementations use the same variable name.
            $propertyReflection = $ref->getProperty('helper');
            $propertyReflection->setAccessible(true);
            // We store the helpers here, because we need them later fot the
            // actual methos analysis. It is possible to inject helpers.
            // Thismeans that the helper may be someone else as it should be,
            // according to the key.
            $this->helpers = $propertyReflection->getValue($data);
            if (is_array($this->helpers) && empty($this->helpers) === false) {
                // We got ourselfes some classes to analyse.
                $this->pool->codegenHandler->setAllowCodegen(false);
                $result .= $this->pool->render->renderExpandableChild(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setName('Instantiated view helpers')
                        ->setType('class internals magical factory')
                        ->addParameter('data', $this->helpers)
                        ->setHelpid('aimeosViewExisting')
                        ->injectCallback(
                            $this->pool->createClass(
                                'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\Callbacks\\ThroughClassList'
                            )
                        )
                );
                $this->pool->codegenHandler->setAllowCodegen(true);
            }
        }

        return $result;
    }

    /**
     * Get a list of all possible other helpers, and analyse their transform()
     * class method
     *
     * @return string
     *   The generated html.
     */
    protected function retreivePossibleOtherHelpers()
    {
        $result = '';

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

        if (class_exists('Aimeos\\MW\\View\\Helper\\Base') === false) {
            // This sould not have happened.
            // No main class, no view helpers.
            // Early return
            return $result;
        }

        // Get a list of the core view helpers
        // Get the core view helpers directory
        try {
            $ref = new \ReflectionClass('Aimeos\\MW\\View\\Helper\\Base');
        } catch (\ReflectionException $e) {
            // Do nothing.
            return $result;
        }

        // Scan the main view helpers, to get a first impression.
        $reflectionList = $this->retrieveHelperList(dirname($ref->getFileName()));

        // Replace the ones in there with the already instanciated ones from the
        // helper array.
        foreach ($this->helpers as $key => $helperObject) {
            try {
                $ref = new \ReflectionClass($helperObject);
                $reflectionList[$key] = $ref->getMethod(static::METHOD);
            } catch (\ReflectionException $e) {
                // We ignore this one.
                continue;
            }
        }

        // Dump them, like there is no tomorrow.
        if (empty($reflectionList) === true) {
            return $result;
        }

        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName('Aimeos view factory')
                ->setType('class internals view magic')
                ->addParameter('data', $reflectionList)
                // Tell the callback to pass on the factory name.
                // Thanks to the magic factory, we must use this one.
                ->addParameter('isFactoryMethod', $result)
                ->setHelpid('aimeosViewInfo')
                ->injectCallback(
                    $this->pool->createClass(
                        'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\Callbacks\\ThroughMethods'
                    )
                )
        );
    }

    /**
     * Retrieve all helper class reflections from a directory.
     *
     * @return array
     *   The list with the reflections.
     */
    protected function retrieveHelperList($directory)
    {
        static $doneSoFar = array();
        $reflectionList = array();

        // Do some static caching.
        if (isset($doneSoFar[$directory])) {
            return $reflectionList;
        }
        $doneSoFar[$directory] = true;

        $iface = static::NAMESPACE . 'Iface';
        $standard = '\\Standard';
        $subDirs = scandir($directory);

        foreach ($subDirs as $dir) {
            if ($dir === '.' or $dir === '..') {
                // Not a class.
                continue;
            }

            if (isset($this->helpers[$dir])) {
                // We will add these later.
                continue;
            }

            if (is_dir($directory . '/' . $dir)) {
                if (class_exists(static::NAMESPACE . $dir . $standard)) {
                    try {
                        $ref = new \ReflectionClass(static::NAMESPACE . $dir . $standard);
                    } catch (\ReflectionException $e) {
                        // Move to the next target.
                        continue;
                    }

                    // Test for the view helper interface.
                    if ($ref->implementsInterface($iface)) {
                        $reflectionList[lcfirst($dir)] = $ref->getMethod(static::METHOD);
                    }
                }
            }
        }

        return $reflectionList;
    }
}
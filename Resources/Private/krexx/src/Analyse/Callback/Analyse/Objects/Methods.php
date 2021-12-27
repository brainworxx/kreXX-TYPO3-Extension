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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Method analysis for objects.
 *
 * @uses object data
 *   The object we are currently analysing.
 * @uses string name
 *   The name of the object we are analysing.
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Methods extends AbstractObjectAnalysis implements CallbackConstInterface, ConfigConstInterface
{

    /**
     * Decides which methods we want to analyse and then starts the dump.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        /** @var \ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];

        $doProtected = $this->pool->config->getSetting(static::SETTING_ANALYSE_PROTECTED_METHODS) ||
            $this->pool->scope->isInScope();
        $doPrivate = $this->pool->config->getSetting(static::SETTING_ANALYSE_PRIVATE_METHODS) ||
            $this->pool->scope->isInScope();
        $domId = $this->generateDomIdFromClassname($ref->getName(), $doProtected, $doPrivate);

        // We need to check, if we have a meta recursion here.
        if ($this->pool->recursionHandler->isInMetaHive($domId) === true) {
            // We have been here before.
            // We skip this one, and leave it to the js recursion handler!
            $metaMethods = $this->pool->messages->getHelp('metaMethods');
            return $output .
                $this->pool->render->renderRecursion(
                    $this->dispatchEventWithModel(
                        static::EVENT_MARKER_RECURSION,
                        $this->pool->createClass(Model::class)
                            ->setDomid($domId)
                            ->setNormal($metaMethods)
                            ->setName($metaMethods)
                            ->setType(static::TYPE_INTERNALS)
                    )
                );
        }

        return $output . $this->analyseMethods($ref, $domId, $doProtected, $doPrivate);
    }

    /**
     * Dumping all methods but only if we have any.
     *
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing
     * @param string $domId
     *   The already generated dom id.
     * @param bool $doProtected
     *   Are we analysing the protected methods here?
     * @param bool $doPrivate
     *   Are we analysing private methods here?
     *
     * @return string
     *   The generated markup.
     */
    protected function analyseMethods(ReflectionClass $ref, string $domId, bool $doProtected, bool $doPrivate): string
    {
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        if ($doProtected === true) {
            $methods = array_merge($methods, $ref->getMethods(ReflectionMethod::IS_PROTECTED));
        }
        if ($doPrivate === true) {
            $methods = array_merge($methods, $ref->getMethods(ReflectionMethod::IS_PRIVATE));
        }

        // Is there anything to analyse?
        if (empty($methods) === true) {
            return '';
        }

        // Now that we have something to analyse, register the DOM ID.
        $this->pool->recursionHandler->addToMetaHive($domId);

        // We need to sort these alphabetically.
        usort($methods, [$this, 'reflectionSorting']);

        return $this->pool->render->renderExpandableChild(
            $this->dispatchEventWithModel(
                static::EVENT_MARKER_ANALYSES_END,
                $this->pool->createClass(Model::class)
                    ->setName($this->pool->messages->getHelp('metaMethods'))
                    ->setType(static::TYPE_INTERNALS)
                    ->addParameter(static::PARAM_DATA, $methods)
                    ->addParameter(static::PARAM_REF, $ref)
                    ->setDomId($domId)
                    ->injectCallback($this->pool->createClass(ThroughMethods::class))
            )
        );
    }

    /**
     * Generates an id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is simply the md5 hash of the classname with the namespace.
     *
     * @param string $data
     *   The object name from which we want the ID.
     * @param bool $doProtected
     *   Are we analysing the protected methods here?
     * @param bool $doPrivate
     *   Are we analysing private methods here?
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromClassname(string $data, bool $doProtected, bool $doPrivate): string
    {
        $string = 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_m_';
        if ($doProtected === true) {
            $string .= 'pro_';
        }

        if ($doPrivate === true) {
            $string .= 'pri_';
        }

        return $string . md5($data);
    }
}

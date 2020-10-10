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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Comment\Classes;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\View\ViewConstInterface;

/**
 * Class Meta
 *
 * @uses ref \Brainworxx\Krexx\Service\Reflection\ReflectionClass
 *   Here we get all out data.
 * @uses metaname string
 *   The name of the meta data, if available.
 *   Fallback to static::META_CLASS_DATA
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 */
class Meta extends AbstractObjectAnalysis implements CallbackConstInterface, ViewConstInterface
{

    /**
     * Dump the Meta stuff from a class.
     *
     * - Fully qualified class name
     * - Class comment
     * - Filename and line from/to
     * - Implemented interfaces
     * - Class list from where the objects inherits stuff from
     * - Used traits
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        $this->pool->codegenHandler->setAllowCodegen(false);

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        if (isset($this->parameters[static::PARAM_META_NAME])) {
            $name = $this->parameters[static::PARAM_META_NAME];
        } else {
            $name = static::META_CLASS_DATA;
        }

        // We need to check, if we have a meta recursion here.
        $domId = $this->generateDomIdFromClassname($ref->getName());
        if ($this->pool->recursionHandler->isInMetaHive($domId) === true) {
            // We have been here before.
            // We skip this one, and leave it to the js recursion handler!
            $this->pool->codegenHandler->setAllowCodegen(true);
            return $output .
                $this->pool->render->renderRecursion(
                    $this->dispatchEventWithModel(
                        static::EVENT_MARKER_RECURSION,
                        $this->pool->createClass(Model::class)
                            ->setDomid($domId)
                            ->setNormal($name)
                            ->setName($name)
                            ->setType(static::TYPE_INTERNALS)
                    )
                );
        }
        $this->pool->codegenHandler->setAllowCodegen(true);
        return $output . $this->analyseMeta($domId, $ref, $name);
    }

    /**
     * Do the actual analysis.
     *
     * @param string $domId
     *   The dom id for the recursion handler.
     * @param ReflectionClass $ref
     *   The reflection class, the main source of information.
     * @param string $name
     *   The name of the property.
     *
     * @return string
     *   The generated DOM.
     */
    protected function analyseMeta(string $domId, ReflectionClass $ref, string $name): string
    {
        $this->pool->recursionHandler->addToMetaHive($domId);

        $data = [];
        // Get the naming on the way.
        $data[static::META_CLASS_NAME] = $this->generateName($ref);
        $data[static::META_COMMENT] = $this->pool->createClass(Classes::class)->getComment($ref);

        if ($ref->isInternal()) {
            $data[static::META_DECLARED_IN] = static::META_PREDECLARED;
        } else {
            $data[static::META_DECLARED_IN] = $this->pool->fileService
                    ->filterFilePath($ref->getFileName()) . ', line ' . $ref->getStartLine();
        }

        // Now to collect the inheritance stuff.
        // Each of them will get analysed by the ThroughMeta callback.
        $interfaces = $ref->getInterfaces();
        if (empty($interfaces) === false) {
            $data[static::META_INTERFACES] = $interfaces;
        }
        $traitList = $ref->getTraits();
        if (empty($traitList) === false) {
            $data[static::META_TRAITS] = $traitList;
        }

        /** @var ReflectionClass $previousClass */
        $previousClass = $ref->getParentClass();
        if (empty($previousClass) === false) {
            // We add it via array, because the other inheritance getters
            // are also supplying one.
            $data[static::META_INHERITED_CLASS] = [$previousClass->getName() => $previousClass];
        }

        return $this->pool->render->renderExpandableChild($this->dispatchEventWithModel(
            static::EVENT_MARKER_ANALYSES_END,
            $this->pool->createClass(Model::class)
                ->setName($name)
                ->setDomid($domId)
                ->setType(static::TYPE_INTERNALS)
                ->addParameter(static::PARAM_DATA, $data)
                ->injectCallback($this->pool->createClass(ThroughMeta::class))
        ));
    }

    /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is simply the md5 hash of the classname with the namespace.
     *
     * @param string $data
     *   The object name from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromClassname(string $data): string
    {
        return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_c_' . md5($data);
    }

    /**
     * Generate the class name with all "attributes" (abstract final whatever).
     *
     * @param ReflectionClass $ref
     *   Reflection of the class we are analysing.
     *
     * @return string
     *   The generated class name
     */
    protected function generateName(ReflectionClass $ref): string
    {
        $result = '';
        if ($ref->isFinal() === true) {
            $result .= 'final ';
        }
        if ($ref->isAbstract() === true && $ref->isTrait() === false) {
            // Huh, traits are abstract, but you do not declare them as such.
            $result .= 'abstract ';
        }
        if ($ref->isInternal() === true) {
            $result .= 'internal ';
        }
        if ($ref->isInterface() === true) {
            $result .= 'interface ';
        } elseif ($ref->isTrait() === true) {
            $result .= 'trait ';
        } else {
            $result .= 'class ';
        }

        return $result . $ref->getName();
    }
}

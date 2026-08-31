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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Comment\Attributes;
use Brainworxx\Krexx\Analyse\Comment\Comment;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;

/**
 * Analysis of the metadata of a class
 *
 * @uses ref \Brainworxx\Krexx\Service\Reflection\ReflectionClass
 *   Here we get all out data.
 * @uses metaname string
 *   The name of the metadata, if available.
 *   Fallback to static::META_CLASS_DATA
 * @uses data object
 *   This one may or may not be present.
 */
class Meta extends AbstractObjectAnalysis
{
    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Dump the Meta stuff from a class.
     *
     * - Fully qualified class name
     * - Class comment
     * - Filename and line from/to
     * - Implemented interfaces
     * - Class list from where the objects inherit its stuff from
     * - Used traits
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        $this->pool->codegenHandler->setCodegenAllowed(bool: false);

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        $name = $this->parameters[static::PARAM_META_NAME] ?? $this->pool->messages->getHelp(key: 'metaClassData');

        // We need to check, if we have a meta recursion here.
        $domId = $this->generateDomIdFromClassname(data: $ref->getName());
        if ($this->pool->recursionHandler->isInMetaHive(domId: $domId)) {
            // We have been here before.
            // We skip this one, and leave it to the js recursion handler!
            $this->pool->codegenHandler->setCodegenAllowed(bool: true);
            return $output .
                $this->pool->render->renderRecursion(
                    model: $this->dispatchEventWithModel(
                        name: static::EVENT_MARKER_RECURSION,
                        model: $this->pool->createClass(classname: Model::class)
                            ->setDomid(domid: $domId)
                            ->setNormal(normal: $name)
                            ->setName(name: $name)
                            ->setType(type: $this->pool->messages->getHelp(key: 'classInternals'))
                    )
                );
        }
        $this->pool->codegenHandler->setCodegenAllowed(bool: true);
        return $output . $this->analyseMeta(domId: $domId, ref: $ref, name: $name);
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
        $this->pool->recursionHandler->addToMetaHive(domId: $domId);

        return $this->pool->render->renderExpandableChild(model: $this->dispatchEventWithModel(
            name: static::EVENT_MARKER_ANALYSES_END,
            model: $this->pool->createClass(classname: Model::class)
                ->setName(name: $name)
                ->setDomid(domid: $domId)
                ->setType(type: $this->pool->messages->getHelp(key: 'classInternals'))
                ->addParameter(name: static::PARAM_DATA, value: $this->generateMetaData($ref))
                ->injectCallback(object: $this->pool->createClass(classname: ThroughMeta::class))
        ));
    }

    /**
     * Generate the metadata.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     *   The reflection class, the main source of information.
     *
     * @return array
     *   The generated metadata.
     */
    protected function generateMetaData(ReflectionClass $ref): array
    {
        $messages = $this->pool->messages;
        // Get the naming on the way.
        $data = [
            $messages->getHelp(key: 'metaClassName') => $this->generateName(ref: $ref),
            $messages->getHelp(key: 'metaComment') => $this->pool
                ->createClass(classname: Comment::class)->getComment(reflection: $ref),
            $messages->getHelp(key: 'metaAttributes') => $this->pool
                ->createClass(classname: Attributes::class)->getAttributes(reflection: $ref),
            $messages->getHelp(key: 'metaDeclaredIn') => $ref->isInternal() ?
                $messages->getHelp(key: 'metaPredeclared') :
                $ref->getFileName() . ' ' .
                $messages->getHelp(key: 'metaInLine') . $ref->getStartLine(),
        ];

        // Now to collect the inheritance stuff.
        // Each of them will get analysed by the ThroughMeta callback.
        if (!empty($interfaces = $ref->getInterfaces())) {
            $data[$messages->getHelp(key: 'metaInterfaces')] = $interfaces;
        }
        if (!empty($traitList = $ref->getTraits())) {
            $data[$messages->getHelp(key: 'metaTraits')] = $traitList;
        }

        /** @var ReflectionClass $previousClass */
        if (!empty($previousClass = $ref->getParentClass())) {
            // We add it via array, because the other inheritance getters
            // are also supplying one.
            $data[$messages->getHelp(key: 'metaInheritedClass')] = [$previousClass->getName() => $previousClass];
        }

        return $data;
    }

    /**
     * Generates an id for the DOM.
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
        $messages = $this->pool->messages;

        if ($ref->isFinal()) {
            $result .= $messages->getHelp(key: 'final') . ' ';
        }
        if ($ref->isInternal()) {
            $result .= $messages->getHelp(key: 'internal') . ' ';
        }
        if ($ref->isInterface()) {
            $result .= $messages->getHelp(key: 'interface') . ' ';
        } elseif ($ref->isTrait()) {
            $result .= $messages->getHelp(key: 'trait') . ' ';
        } elseif ($ref->isAbstract()) {
            // Huh, traits and interfaces are abstract,
            // but you do not declare them as such.
            $result .= $messages->getHelp(key: 'abstract') . ' ' . $messages->getHelp(key: 'class') . ' ';
        } else {
            $result .= $messages->getHelp(key: 'class') . ' ';
        }

        return $result . $ref->getName();
    }
}

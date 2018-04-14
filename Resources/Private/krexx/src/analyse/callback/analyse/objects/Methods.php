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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Method analysis for objects.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Methods extends AbstractObjectAnalysis
{
    /**
     * Decides which methods we want to analyse and then starts the dump.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        /** @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];

        // We need to check, if we have a meta recursion here.

        $doProtected = $this->pool->config->getSetting(Fallback::SETTING_ANALYSE_PROTECTED_METHODS) ||
            $this->pool->scope->isInScope();
        $doPrivate = $this->pool->config->getSetting(Fallback::SETTING_ANALYSE_PRIVATE_METHODS) ||
            $this->pool->scope->isInScope();
        $domId = $this->generateDomIdFromClassname($ref->getName(), $doProtected, $doPrivate);

        if ($this->pool->recursionHandler->isInMetaHive($domId) === true) {
            // We have been here before.
            // We skip this one, and leave it to the js recursion handler!
            return $this->pool->render->renderRecursion(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setDomid($domId)
                    ->setNormal('Methods')
                    ->setName('Methods')
                    ->setType('class internals')
            );
        }

        return $this->analyseMethods($ref, $domId, $doProtected, $doPrivate);
    }

    /**
     * Do the real analysis.
     */
    /**
     * @param \ReflectionClass $ref
     *   The reflection of t he class we are analysing
     * @param $domId
     *   The alredy generated dom id.
     * @param $doProtected
     *   Are we analysing the protected methods here?
     * @param $doPrivate
     *   Are we analysing private methods here?
     * @return string
     *   The generated markup.
     */
    protected function analyseMethods(\ReflectionClass $ref, $domId, $doProtected, $doPrivate)
    {
        // Dumping all methods but only if we have any.
        $protected = array();
        $private = array();
        $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        if ($doProtected === true) {
            $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
        }

        if ($doPrivate === true) {
            $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
        }

        // Is there anything to analyse?
        $methods = array_merge($public, $protected, $private);
        if (empty($methods) === true) {
            return '';
        }

        // Now that we have something to analyse, register the DOM ID.
        $this->pool->recursionHandler->addToMetaHive($domId);

        // We need to sort these alphabetically.
        usort($methods, array($this, 'reflectionSorting'));

        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName('Methods')
                ->setType('class internals')
                ->addParameter('data', $methods)
                ->addParameter('ref', $ref)
                ->setDomId($domId)
                ->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods')
                )
        );
    }

    /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is simply the md5 hash of the classname with thenamspace.
     *
     * @param string $data
     *   The object from which we want the ID.
     * @param boolean $doProtected
     *   Are we analysing the protected methods here?
     * @param boolean $doPrivate
     *   Are we analysing private methods here?
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromClassname($data, $doProtected, $doPrivate)
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

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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * "Routing" for kreXX
 *
 * The analysisHub decides what to do next with the model.
 * The other method ara also used, in case it is known how
 * to proceed next.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class Routing
{

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
         $this->pool = $pool;
    }

    /**
     * Dump information about a variable.
     *
     * This function decides what functions analyse the data
     * and acts as a hub.
     *
     * @param Model $model
     *   The variable we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    public function analysisHub(Model $model)
    {
        // Check memory and runtime.
        if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }
        $data = $model->getData();

        // Check nesting level
        $this->pool->emergencyHandler->upOneNestingLevel();
        if (is_array($data) || is_object($data)) {
            if ($this->pool->emergencyHandler->checkNesting()) {
                $this->pool->emergencyHandler->downOneNestingLevel();
                $text = gettype($data) . ' => ' . $this->pool->messages->getHelp('maximumLevelReached');
                $model->setData($text);
                return $this->analyseString($model);
            }
        }

        // Check for recursion.
        if (is_object($data) || is_array($data)) {
            if ($this->pool->recursionHandler->isInHive($data)) {
                // Render recursion.
                if (is_object($data)) {
                    $type = get_class($data);
                } else {
                    // Must be the globals array.
                    $type = '$GLOBALS';
                }
                $model->setDomid($this->generateDomIdFromObject($data))
                    ->setType($type);
                $result = $this->pool->render->renderRecursion($model);
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            }
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);
        }


        // Object?
        // Closures are analysed separately.
        if (is_object($data) && !is_a($data, '\\Closure')) {
            $result = $this->analyseObject($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Closure?
        if (is_object($data) && is_a($data, '\\Closure')) {
            $result = $this->analyseClosure($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Array?
        if (is_array($data)) {
            $result = $this->analyseArray($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Resource?
        if (is_resource($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseResource($model);
        }

        // String?
        if (is_string($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseString($model);
        }

        // Float?
        if (is_float($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseFloat($model);
        }

        // Integer?
        if (is_int($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseInteger($model);
        }

        // Boolean?
        if (is_bool($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseBoolean($model);
        }

        // Null ?
        if (is_null($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->analyseNull($model);
        }

        // Still here? This should not happen. Return empty string, just in case.
        $this->pool->emergencyHandler->downOneNestingLevel();
        return '';
    }

    /**
     * Render a 'dump' for a NULL value.
     *
     * @param Model $model
     *   The model with the data for the output.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseNull(Model $model)
    {
        $data = 'NULL';
        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'null')
            ->addToJson('type', 'NULL');

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Render a dump for an array.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseArray(Model $model)
    {
        $multiline = false;
        $count = (string)count($model->getData());

        // Dumping all Properties.
        $model->setType($model->getAdditional() . 'array')
            ->setAdditional($count . ' elements')
            ->addToJson('type', 'array')
            ->addToJson('count', $count)
            ->addParameter('data', $model->getData())
            ->addParameter('multiline', $multiline)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray')
            );

        return $this->pool->render->renderExpandableChild($model);
    }

    /**
     * Analyses a resource.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseResource(Model $model)
    {
        $data = get_resource_type($model->getData());
        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'resource')
            ->addToJson('type', 'resource');

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Render a dump for a bool value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseBoolean(Model $model)
    {
        $data = $model->getData() ? 'TRUE' : 'FALSE';
        $model->setData($data)
            ->setNormal($data)
            ->setType($model->getAdditional() . 'boolean')
            ->addToJson('type', 'boolean');

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Render a dump for a integer value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseInteger(Model $model)
    {
        $model->setNormal($model->getData())
            ->setType($model->getAdditional() . 'integer')
            ->addToJson('type', 'integer');

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Render a dump for a float value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseFloat(Model $model)
    {
        $model->setNormal($model->getData())
            ->setType($model->getAdditional() . 'float')
            ->addToJson('type', 'float');

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Render a dump for a string value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function analyseString(Model $model)
    {
        $data = $model->getData();

        // Extra ?
        if (strlen($data) > 50) {
            $cut = substr($this->pool->encodeString($data), 0, 50) . '. . .';
            $model->hasExtras();
        } else {
            $cut = $this->pool->encodeString($data);
        }

        // We need to take care for mixed encodings here.
        $encoding = @mb_detect_encoding($data);
        $length = $strlen = @mb_strlen($data, $encoding);
        if ($strlen === false) {
            // Looks like we have a mixed encoded string.
            $length = '~ ' . strlen($data);
            $strlen = ' broken encoding ' . $length;
            $encoding = 'broken';
        }

        $data = $this->pool->encodeString($data);

        $model->setData($data)
            ->setNormal($cut)
            ->setType($model->getAdditional() . 'string' . ' ' . $strlen)
            ->addToJson('encoding', $encoding)
            ->addToJson('length', $length)
            ->addToJson('type', 'string');

        // Check if this is a possible callback.
        // We are not going to analyse this further, because modern systems
        // do not use these anymore.
        if (is_callable($data)) {
            $model->setIsCallback(true);
        }

        return $this->pool->render->renderSingleChild($model);
    }

    /**
     * Analyses a closure.
     *
     * @param Model $model
     *   The closure we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public function analyseClosure(Model $model)
    {
        $ref = new \ReflectionFunction($model->getData());

        $result = array();

        // Adding comments from the file.
        $result['comments'] =  $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Functions')
            ->getComment($ref);

        // Adding the sourcecode
        $highlight = $ref->getStartLine() -1;
        $from = $highlight - 3;
        $to = $ref->getEndLine() -1;
        $file = $ref->getFileName();
        $result['source'] = $this->pool->file->readSourcecode($file, $highlight, $from, $to);

        // Adding the place where it was declared.
        $result['declared in'] = $ref->getFileName() . "\n";
        $result['declared in'] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (!empty($namespace)) {
            $result['namespace'] = $namespace;
        }

        // Adding the parameters.
        $parameters = $ref->getParameters();
        $paramList = '';
        foreach ($parameters as $parameter) {
            preg_match('/(.*)(?= \[ )/', $parameter, $key);
            $parameter = str_replace($key[0], '', $parameter);
            $result[$key[0]] = trim($parameter, ' []');
            $paramList .= trim($result[$key[0]]) . ', ';
        }

        $paramList = str_replace(
            array('&lt;required&gt; ', '&lt;optional&gt; '),
            '',
            $this->pool->encodeString($paramList)
        );
        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';
        $model->setType($model->getAdditional() . ' closure')
            ->setAdditional('. . .')
            ->setConnector2($model->getConnector2() . '(' . $paramList . ')')
            ->setDomid($this->generateDomIdFromObject($model->getData()))
            ->addParameter('data', $result)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis')
            );

        return $this->pool->render->renderExpandableChild($model);

    }

    /**
     * Render a dump for an object.
     *
     * @param Model $model
     *   The object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public function analyseObject(Model $model)
    {
        $output = '';
        $model->setType($model->getAdditional() . 'class')
            ->addParameter('data', $model->getData())
            ->addParameter('name', $model->getName())
            ->setAdditional(get_class($model->getData()))
            ->setDomid($this->generateDomIdFromObject($model->getData()))
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects')
            );

        // Output data from the class.
        $output .= $this->pool->render->renderExpandableChild($model);
        return $output;
    }

    /**
     * Analysis a backtrace.
     *
     * We need to format this one a little bit different than a
     * normal array.
     *
     * @param array $backtrace
     *   The backtrace.
     * @param int $offset
     *   For some reason, we have an offset of -1 for fatal error backtrace
     *   line number.
     *
     * @return string
     *   The rendered backtrace.
     */
    public function analysisBacktrace(array &$backtrace, $offset = 0)
    {
        $output = '';

        foreach ($backtrace as $step => $stepData) {
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($step)
                ->setType('Stack Frame')
                ->addParameter('data', $stepData)
                ->addParameter('offset', $offset)
                ->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep')
                );

            $output .= $this->pool->render->renderExpandableChild($model);
        }

        return $output;
    }

        /**
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is the object hash as well as the kruXX call number, to avoid
     * collisions (even if they are unlikely).
     *
     * @param mixed $data
     *   The object from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromObject($data)
    {
        if (is_object($data)) {
            return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_' . spl_object_hash($data);
        } else {
            // Do nothing.
            return '';
        }
    }
}

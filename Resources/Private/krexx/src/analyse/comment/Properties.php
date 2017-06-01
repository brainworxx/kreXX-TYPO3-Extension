<?php
/**
 * Created by PhpStorm.
 * User: guelzow
 * Date: 31.05.2017
 * Time: 09:23
 */

namespace Brainworxx\Krexx\Analyse\Comment;

/**
 * Retrieving the comment of a property.
 *
 * @package Brainworxx\Krexx\Analyse\Comment
 */
class Properties extends AbstractComment
{
    /**
     * Get the comment from a property.
     *
     * @param \Reflector $reflectionProperty
     * @param \ReflectionClass|null $reflectionClass
     * @return mixed
     */
    public function getComment(
        \Reflector $reflectionProperty,
        \ReflectionClass $reflectionClass = null
    ) {
        // Do some static caching. The comment will not change during a run.
        static $cache = array();
        /** @var \ReflectionProperty $reflectionProperty */
        $cachingKey = $reflectionProperty->getDeclaringClass()->getName() . '::' . $reflectionProperty->getName();
        if (isset($cache[$cachingKey])) {
            return $cache[$cachingKey];
        }

        // Cache not found. We need to generate this one.
        $cache[$cachingKey] = trim(nl2br($this->pool->encodeString(
            $this->prettifyComment($reflectionProperty->getDocComment())
        )));

        return $cache[$cachingKey];
    }
}
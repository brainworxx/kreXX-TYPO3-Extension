<?php


namespace Brainworxx\Krexx\Analyse\Code;


interface ConnectorsConstInterface
{
    /**
     * connectorLeft = ''
     * connectorRight = ''
     * or
     * connectorRight = $params
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NOTHING = 0;

    /**
     * connectorLeft = '->'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const METHOD = 1;

    /**
     * connectorLeft = '::'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const STATIC_METHOD = 2;

    /**
     * connectorLeft = '['
     * connectorRight = ']'
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NORMAL_ARRAY = 3;

    /**
     * connectorLeft = '[\''
     * connectorRight = '\']'
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const ASSOCIATIVE_ARRAY = 4;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const CONSTANT = 5;

    /**
     * connectorLeft = '->'
     * connectorRight = ''
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NORMAL_PROPERTY = 6;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const STATIC_PROPERTY = 7;

    /**
     * connectorLeft = '->{\''
     * connectorRight = '\'}'
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const SPECIAL_CHARS_PROP = 8;

    /**
     * connectorLeft = ''
     * connectorRight = ''
     * or
     * connectorRight = $params
     */
    const CONNECTOR_NOTHING = 0;

    /**
     * connectorLeft = '->'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     */
    const CONNECTOR_METHOD = 1;

    /**
     * connectorLeft = '::'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     */
    const CONNECTOR_STATIC_METHOD = 2;

    /**
     * connectorLeft = '['
     * connectorRight = ']'
     */
    const CONNECTOR_NORMAL_ARRAY = 3;

    /**
     * connectorLeft = '[\''
     * connectorRight = '\']'
     */
    const CONNECTOR_ASSOCIATIVE_ARRAY = 4;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     */
    const CONNECTOR_CONSTANT = 5;

    /**
     * connectorLeft = '->'
     * connectorRight = ''
     */
    const CONNECTOR_NORMAL_PROPERTY = 6;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     */
    const CONNECTOR_STATIC_PROPERTY = 7;

    /**
     * connectorLeft = '->{\''
     * connectorRight = '\'}'
     */
    const CONNECTOR_SPECIAL_CHARS_PROP = 8;
}
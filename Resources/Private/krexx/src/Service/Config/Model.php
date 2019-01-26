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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Config;

/**
 * Model where we store our configuration.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Model
{
    /**
     * The value of this setting.
     *
     * @var string
     */
    protected $value;

    /**
     * The section of this setting.
     *
     * @var string
     */
    protected $section;

    /**
     * The type of this setting.
     *
     * @var string
     */
    protected $type;

    /**
     * Whether or not his setting is editable
     *
     * @var boolean
     */
    protected $editable;

    /**
     * Source of this setting.
     *
     * @var string
     */
    protected $source;

    /**
     * Setter for the editable value.
     *
     * @param boolean $editable
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
        return $this;
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Setter for the value.
     *
     * @param string $value
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setValue($value)
    {
        if ($value === Fallback::VALUE_TRUE) {
            $value = true;
        }

        if ($value === Fallback::VALUE_FALSE) {
            $value = false;
        }

        $this->value = $value;
        return $this;
    }

    /**
     * Getter for the editable value.
     *
     * @return boolean
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * Getter for the section.
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Getter for the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for the value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Setter for the section.
     *
     * @param string $section
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * Getter for the source value.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Setter for the source value.
     *
     * @param string $source
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}

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

namespace Brainworxx\Krexx\Service\Config;

/**
 * Model where we store our configuration.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Model implements ConfigConstInterface
{
    /**
     * The value of this setting.
     *
     * @var int|string|bool|null
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
     * @var bool
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
     * @param bool $editable
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setEditable(bool $editable): Model
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
    public function setType(string $type): Model
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Setter for the value.
     *
     * @param string|int|null $value
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setValue($value): Model
    {
        if ($value === static::VALUE_TRUE) {
            $value = true;
        } elseif ($value === static::VALUE_FALSE) {
            $value = false;
        }

        $this->value = $value;
        return $this;
    }

    /**
     * Getter for the editable value.
     *
     * @return bool
     */
    public function getEditable(): bool
    {
        return $this->editable;
    }

    /**
     * Getter for the section.
     *
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Getter for the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Getter for the value.
     *
     * @return int|string|bool|null
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
    public function setSection(string $section): Model
    {
        $this->section = $section;
        return $this;
    }

    /**
     * Getter for the source value.
     *
     * @return string
     */
    public function getSource(): string
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
    public function setSource(string $source): Model
    {
        $this->source = $source;

        return $this;
    }
}

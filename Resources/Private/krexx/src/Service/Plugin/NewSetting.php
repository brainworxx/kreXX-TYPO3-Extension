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

namespace Brainworxx\Krexx\Service\Plugin;

use Brainworxx\Krexx\Service\Config\ConfigConstInterface;

/**
 * Use this call to register a plugin specific new setting.
 *
 * @package Brainworxx\Krexx\Service\Plugin
 */
class NewSetting implements ConfigConstInterface
{
    /**
     * The name that this setting uses.
     *
     * @var string
     */
    protected $name = '';

    /**
     * The default value.
     *
     * @var string
     */
    protected $defaultValue = '';

    /**
     * The name of the section, where the setting will be stored.
     *
     * @var string
     */
    protected $section = '';

    /**
     * Method name of the validation class that is used to validate this setting.
     *
     * @var string|Closure
     */
    protected $validation = '';

    /**
     * Render type of this setting.
     *
     * @var string
     */
    protected $renderType = '';

    /**
     * Is this setting editable?
     *
     * @var bool
     */
    protected $isEditable = true;

    /**
     * Is it protected on the frontend from changes?
     *
     * @var bool
     */
    protected $isFeProtected = true;

    /**
     * Can this setting ever get edited on the frontend?
     *
     * @return bool
     *   Well? Can it?
     */
    public function isFeProtected(): bool
    {
        return $this->isFeProtected;
    }

    /**
     * Can this setting ever get edited on the frontend?
     *
     * @api
     *
     * @param bool $isFeProtected
     *   Is it protected against editing?
     *
     * @return $this
     *   $this for chaining.
     */
    public function setIsFeProtected(bool $isFeProtected): NewSetting
    {
        $this->isFeProtected = $isFeProtected;
        return $this;
    }

    /**
     * Setter for the method in the validation class, that validated this setting.
     *
     * Possible values:
     *   NewSetting::EVAL_BOOL
     *   NewSetting::EVAL_INT
     *   NewSetting::EVAL_MAX_RUNTIME
     *   NewSetting::EVAL_DESTINATION
     *   NewSetting::EVAL_SKIN
     *   NewSetting::EVAL_IP_RANGE
     *   NewSetting::EVAL_DEBUG_METHODS
     *   or
     *   closure
     *
     * @api
     *
     * @param string|\Closure $validation
     *   Name of the method in the validation class, that validated this setting.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setValidation($validation): NewSetting
    {
        $this->validation = $validation;
        return $this;
    }

    /**
     * Setter for the section, where tis setting is stored.
     *
     * Possible values:
     *   NewSetting::SECTION_OUTPUT
     *   NewSetting::SECTION_BEHAVIOR
     *   NewSetting::SECTION_PRUNE
     *   NewSetting::SECTION_PROPERTIES
     *   NewSetting::SECTION_METHODS
     *   NewSetting::SECTION_EMERGENCY
     *   Or anything you like, except for NewSetting::SECTION_FE_EDITING
     *
     * @api
     *
     * @param string $section
     *   The name of the section.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setSection(string $section): NewSetting
    {
        $this->section = $section;
        return $this;
    }

    /**
     * Setter for the render type.
     *
     * Possible values:
     *   NewSetting::RENDER_TYPE_SELECT
     *   NewSetting::RENDER_TYPE_INPUT
     *   NewSetting::RENDER_TYPE_NONE
     *
     * @api
     *
     * @param string $renderType
     *   The render type.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setRenderType(string $renderType): NewSetting
    {
        $this->renderType = $renderType;
        return $this;
    }

    /**
     * Setter for the name of this new setting.
     *
     * Possible values:
     *   Anything you like, except already existing values.
     *
     * @api
     *
     * @param string $name
     *   The name.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setName(string $name): NewSetting
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Is this value editable by default on the frontend?
     * Of cause, this value can be overwritten by a frontend setting in the
     * configuration file.
     *
     * @api
     *
     * @param bool $isEditable
     *   Is it editable?
     *
     * @return $this
     *   $this for chaining.
     */
    public function setIsEditable(bool $isEditable): NewSetting
    {
        $this->isEditable = $isEditable;
        return $this;
    }

    /**
     * Setter for the default value of this setting.
     *
     * @api
     *
     * @param string $defaultValue
     *   The default value.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setDefaultValue(string $defaultValue): NewSetting
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Getter of the name of this setting.
     *
     * @internal
     *
     * @return string
     *   The name og the setting.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Getter for the section name, where this setting will be stored.
     *
     * @internal
     *
     * @return string
     *   The section name.
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Return the rendering settings for the Fallback class.
     *
     * @internal
     *
     * @return array
     *   The configuration in a way that the Config class can understand.
     */
    public function getFeSettings(): array
    {
        return [
            static::VALUE => $this->defaultValue,
            static::RENDER => [
                self::RENDER_TYPE => $this->renderType,
                self::RENDER_EDITABLE => $this->isEditable ? self::VALUE_TRUE : self::VALUE_FALSE,
            ],
            static::EVALUATE => $this->validation,
            static::SECTION => $this->section
        ];
    }
}

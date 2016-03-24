<?php
/**
 * @file
 *   Mockup class of reflection properties for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analysis;

/**
 * This class is a mockup class for the original reflection property class
 *
 * When a property of a class is set, but not explicitly declared, there is
 * no chance to get a reflection of this property. This class simulates ths
 * reflection, so I can reuse the analysis methods which are based on reflection
 * properties.
 *
 * @package Krexx
 */
class Flection {

  /**
   * The name of the property.
   *
   * @var string
   */
  public $name;

  /**
   * The value of the property.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Constructor for the Flection class.
   *
   * Sets the name and the value of the property.
   *
   * @param mixed $value
   *   The value of the attribute.
   * @param string $name
   *   The name of the attribute.
   */
  public function __construct($value, $name) {
    $this->value = $value;
    $this->name = $name;
  }

  /**
   * Getter for the value of the property.
   *
   * It's stored in the value property :-).
   *
   * @return mixed
   *   The value itself.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Mockup for the isDefault.
   *
   * Undeclared properties do not have a default value.
   *
   * @return bool
   *   It's always FALSE.
   */
  public function isDefault() {
    return FALSE;
  }

  /**
   * Mockup for the setAccessible.
   *
   * Undeclared properties are always accessible, no need to do anything.
   *
   * @param bool $bool
   *   Does nothing. At all.
   */
  public function setAccessible($bool) {
    // Do nothing.
  }

  /**
   * Mockup for the isPublic.
   *
   * Undeclared properties are always public.
   *
   * @return bool
   *   Always returns TRUE.
   */
  public function isPublic() {
    return TRUE;
  }

  /**
   * Mockup for the isPrivate.
   *
   * Undeclared properties are never private.
   *
   * @return bool
   *   Always returns FALSE
   */
  public function isPrivate() {
    return FALSE;
  }

  /**
   * Mockup for the isProtected.
   *
   * Undeclared properties are never protected.
   *
   * @return bool
   *   Always returns FALSE
   */
  public function isProtected() {
    return FALSE;
  }

  /**
   * Mockup for the isStatic.
   *
   * Undeclared properties are never static.
   *
   * @return bool
   *   Always returns FALSE
   */
  public function isStatic() {
    return FALSE;
  }

  /**
   * Mockup for the getDefaultProperties.
   *
   * Undeclared properties are never have default properties.
   *
   * @return bool
   *   Always returns an empty array.
   */
  public function getDefaultProperties() {
    return array();
  }

  /**
   * Tells the analysis function, that this property was not declared.
   *
   * @return string
   *   Tell the analysis function that I'm undeclared
   */
  public function getWhatAmI() {
    return 'dynamic property ';
  }
}

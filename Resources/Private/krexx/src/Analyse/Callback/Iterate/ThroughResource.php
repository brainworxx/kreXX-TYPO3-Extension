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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Iterate through the ressource analysis and generate an output.
 *
 * @uses array data
 *   The metadata from the stream.
 */
class ThroughResource extends AbstractCallback implements CallbackConstInterface
{
    /**
     * Renders the info of a resource.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        // Allow the start event to change the provided metadata.
        $output = $this->dispatchStartEvent();

        // Temporarily disable code gen.
        $isAllowedCodeGen = $this->pool->codegenHandler->isCodegenAllowed();
        $this->pool->codegenHandler->setCodegenAllowed(false);

        foreach ($this->parameters[static::PARAM_DATA] as $name => $data) {
            $output .= $this->pool->routing->analysisHub(
                $this->pool->createClass(Model::class)
                    ->setData($data)
                    ->setName(str_replace('_', ' ', $name))
                    ->setNormal($data)
            );
        }

        // Reset code generation.
        $this->pool->codegenHandler->setCodegenAllowed($isAllowedCodeGen);

        return $output;
    }
}

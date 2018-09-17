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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

/**
 * Class ThroughResource
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses resource data
 *   The resource we want to analyse.
 */
class ThroughResourceStream extends AbstractCallback
{
    /**
     * Renders the info of a resource.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();
        /** @var resource $resource */
        $resource = $this->parameters['data'];
        $meta = stream_get_meta_data($resource);



        // Temporatily disable code gen.
        $isAllowedCodeGen = $this->pool->codegenHandler->getAllowCodegen();
        $this->pool->codegenHandler->setAllowCodegen(false);

        foreach ($meta as $name => $data) {
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($data)
                ->setName(str_replace('_', ' ', $name))
                ->setNormal($data);

            $output .= $this->pool->routing->analysisHub($model);
        }

        // Reset code generation.
        $this->pool->codegenHandler->setAllowCodegen($isAllowedCodeGen);

        return $output;
    }
}
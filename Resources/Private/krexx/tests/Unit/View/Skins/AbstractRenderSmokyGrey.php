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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

abstract class AbstractRenderSmokyGrey extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';
    const GET_NAME = 'getName';
    const GET_DOMID = 'getDomid';
    const GET_NORMAL = 'getNormal';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const GET_JSON = 'getJson';
    const GET_TYPE = 'getType';
    const RENDER_ME = 'renderMe';
    const GET_CONNECTOR_LANGUAGE = 'getConnectorLanguage';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderSmokyGrey
     */
    protected $renderSmokyGrey;

    /**
     * {@inheritDoc}
     */
    protected function krexxUp()
    {
        parent::krexxUp();
        $this->renderSmokyGrey = new RenderSmokyGrey(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderSmokyGrey);
        $this->mockTemplate();
    }

     /**
     * Short circuiting the existence of a specific template file.
     * We only simulate the differences in the smoky grey skin.
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . 'sourcebutton' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSourceButton())
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . 'nest' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerNest())
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . 'expandableChildNormal' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerExpandableChild())
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerConnectorRight())
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . 'recursion' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerRecursion())
                ],
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . 'singleEditableChild' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSingleEditableChild())
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . 'singleButton' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSingleButton())
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . 'header' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerHeader())
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . 'footer' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerFooter())
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . 'fatalMain' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerFatalMain())
                ],
                // search.html
                [
                    static::PATH_TO_SKIN . 'search' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSearch())
                ],
                // singlePlugin.html
                [
                    static::PATH_TO_SKIN . 'singlePlugin' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSinglePlugin())
                ],
                // connectorLeft.html
                [
                    static::PATH_TO_SKIN . 'connectorLeft' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerConnectorLeft())
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerConnectorRight())
                ],
                // singleSelectOption.html
                [
                    static::PATH_TO_SKIN . 'singleSelectOptions' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerSelectOption())
                ],
                // single.html
                // Meh, whatever. Rendering of a 'single' undefined editable child.
                [
                    static::PATH_TO_SKIN . 'single' . $fileSuffix,
                    true,
                    implode('', [])
                ],
                // message.html
                [
                    static::PATH_TO_SKIN . 'message' . $fileSuffix,
                    true,
                    implode('', $this->renderSmokyGrey->getMarkerMessages())
                ],
            ]));

        Krexx::$pool->fileService = $this->fileServiceMock;
    }

    /**
     * The great Moddelmock is not a wizard from Harry Potter.
     *
     * @param $methodName
     * @param $returnValue
     */
    protected function mockModel($methodName, $returnValue)
    {
        if (empty($this->modelMock)) {
            $this->modelMock = $this->createMock(Model::class);
        }
        $this->modelMock->expects($this->once())
            ->method($methodName)
            ->will($this->returnValue($returnValue));
    }
}

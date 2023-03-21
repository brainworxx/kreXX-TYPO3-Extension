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
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\Fallback;
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
        $this->mockTemplate();
        $this->renderSmokyGrey = new RenderSmokyGrey(Krexx::$pool);
    }

     /**
     * Short circuiting the existence of a specific template file.
     * We only simulate the differences in the smoky grey skin.
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $smoky = new RenderSmokyGrey(Krexx::$pool);
        $this->fileServiceMock = $this->createMock(File::class);
        $pathToSkin = Krexx::$pool->config->getSkinDirectory();
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // sourceButton.html
                [
                    $pathToSkin . 'sourcebutton' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSourceButton())
                ],
                // nest.html
                [
                    $pathToSkin . 'nest' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerNest())
                ],
                // expandableChildNormal.html
                [
                    $pathToSkin . 'expandableChildNormal' . $fileSuffix,
                    true,
                    implode('',$smoky->getMarkerExpandableChild())
                ],
                // connectorRight.html
                [
                    $pathToSkin . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerConnectorRight())
                ],
                // recursion.html
                [
                    $pathToSkin . 'recursion' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerRecursion())
                ],
                // singleEditableChild.html
                [
                    $pathToSkin . 'singleEditableChild' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSingleEditableChild())
                ],
                // singleButton.html
                [
                    $pathToSkin . 'singleButton' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSingleButton())
                ],
                // header.html
                [
                    $pathToSkin . 'header' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerHeader())
                ],
                // footer.html
                [
                    $pathToSkin . 'footer' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerFooter())
                ],
                // fatalMain.html
                [
                    $pathToSkin . 'fatalMain' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerFatalMain())
                ],
                // search.html
                [
                    $pathToSkin . 'search' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSearch())
                ],
                // singlePlugin.html
                [
                    $pathToSkin . 'singlePlugin' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSinglePlugin())
                ],
                // connectorLeft.html
                [
                    $pathToSkin . 'connectorLeft' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerConnectorLeft())
                ],
                // connectorRight.html
                [
                    $pathToSkin . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerConnectorRight())
                ],
                // singleSelectOption.html
                [
                    $pathToSkin . 'singleSelectOptions' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSelectOption())
                ],
                // single.html
                // Meh, whatever. Rendering of a 'single' undefined editable child.
                [
                    $pathToSkin . 'single' . $fileSuffix,
                    true,
                    implode('', [])
                ],
                // message.html
                [
                    $pathToSkin . 'message' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerMessages())
                ],
                // backtraceSourceLine.html
                [
                    $pathToSkin . 'backtraceSourceLine' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerBacktraceSourceLine())
                ],
                // br.html
                [
                    $pathToSkin . 'br' . $fileSuffix,
                    true,
                    implode('', [])
                ],
                // caller.html
                [
                    $pathToSkin . 'caller' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerCaller())
                ],
                // cssJs.html
                [
                    $pathToSkin . 'cssJs' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerCssJs())
                ],
                // fatalHeader.html
                [
                    $pathToSkin . 'fatalHeader' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerFatalHeader())
                ],
                // help.html
                [
                    $pathToSkin . 'help' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerHelp())
                ],
                // singleChildExtra.html
                [
                  $pathToSkin . 'singleChildExtra' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSingleChildExtra())
                ],
                // singleChildHr.html
                [
                    $pathToSkin . 'singleChildHr' . $fileSuffix,
                    true,
                    'HR does not mean human resources'
                ],
                // singleInput.html
                [
                    $pathToSkin . 'singleInput' . $fileSuffix,
                    true,
                    implode('', $smoky->getMarkerSingleInput()) . '<input'
                ],
                // singleSelect.html
                [
                    $pathToSkin . 'single' . ConfigConstInterface::RENDER_TYPE_SELECT . $fileSuffix,
                    true,
                    '{id}' .
                    implode('', $smoky->getMarkerDropdownOptions())
                ],
                // helprow.html
                [
                    $pathToSkin . 'helprow' . $fileSuffix,
                    true,
                    'Unhelpful stuff.'
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

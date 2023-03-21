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
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Skins\RenderHans;

abstract class AbstractRenderHans extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';
    const GET_NAME = 'getName';
    const GET_DOMID = 'getDomid';
    const GET_NORMAL = 'getNormal';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const GET_JSON = 'getJson';
    const GET_HAS_EXTRAS = 'hasExtra';
    const GET_DATA = 'getData';
    const GET_TYPE = 'getType';
    const RENDER_ME = 'renderMe';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderHans
     */
    protected $renderHans;

    /**
     * {@inheritDoc}
     */
    protected function krexxUp()
    {
        parent::krexxUp();
        $this->mockTemplate();
        $this->renderHans = new RenderHans(Krexx::$pool);
        $this->mockEmergencyHandler();
    }

    /**
     * Short circuiting the existence of a specific template file.
     * Nice, huh?
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $hans = new RenderHans(Krexx::$pool);
        $pathToSkin = Krexx::$pool->config->getSkinDirectory();
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())->method('getFileContents')
            ->will($this->returnValueMap([
                // connectorLeft.html
                [
                    $pathToSkin . 'connectorLeft' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerConnectorLeft())
                ],
                // connectorRight.html
                [
                    $pathToSkin . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerConnectorRight())
                ],
                // helprow.html
                [
                    $pathToSkin . 'helprow' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerHelpRow())
                ],
                // help.html
                [
                    $pathToSkin . 'help' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerHelp())
                ],
                // recursion.html
                [
                    $pathToSkin . 'recursion' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerRecursion())
                ],
                // header.html
                [
                    $pathToSkin . 'header' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerHeader())
                ],
                // search.html
                [
                    $pathToSkin . 'search' . $fileSuffix,
                    true,
                    ''
                ],
                // footer.html
                [
                    $pathToSkin . 'footer' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerFooter())
                ],
                // caller.html
                [
                    $pathToSkin . 'caller' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerCaller())
                ],
                // singlePlugin.html
                [
                    $pathToSkin . 'singlePlugin' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSinglePlugin())
                ],
                // cssJs.html
                [
                    $pathToSkin . 'cssJs' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerCssJs())
                ],
                // singleChildExtra.html
                [
                  $pathToSkin . 'singleChildExtra' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSingleChildExtra())
                ],
                // sourceButton.html
                [
                    $pathToSkin . 'sourcebutton' . $fileSuffix,
                    true,
                    'sourcebutton'
                ],
                // expandableChildNormal.html
                [
                    $pathToSkin . 'expandableChildNormal' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerExpandableChild())
                ],
                // nest.html
                [
                    $pathToSkin . 'nest' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerNest())
                ],
                // singleEditableChild.html
                [
                    $pathToSkin . 'singleEditableChild' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSingleEditableChild())
                ],
                // singleInput.html
                [
                    $pathToSkin . 'singleInput' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSingleInput()) . '<input'
                ],
                // singleSelect.html
                [
                    $pathToSkin . 'single' . Fallback::RENDER_TYPE_SELECT . $fileSuffix,
                    true,
                    '{id}' .
                    implode('', $hans->getMarkerDropdownOptions())
                ],
                // singleSelectOption.html
                [
                    $pathToSkin . 'singleSelectOptions' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSelectOption())
                ],
                // singleButton.html
                [
                    $pathToSkin . 'singleButton' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerSingleButton())
                ],
                // fatalMain.html
                [
                    $pathToSkin . 'fatalMain' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerFatalMain())
                ],
                // fatalHeader.html
                [
                    $pathToSkin . 'fatalHeader' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerFatalHeader())
                ],
                // messages.html
                [
                    $pathToSkin . 'message' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerMessages())
                ],
                // backtraceSourceLine
                [
                    $pathToSkin . 'backtraceSourceLine' . $fileSuffix,
                    true,
                    implode('', $hans->getMarkerBacktraceSourceLine())
                ],
                // singleChildHr.html
                [
                    $pathToSkin . 'singleChildHr' . $fileSuffix,
                    true,
                    'HR does not mean human resources'
                ],
                // br.html
                [
                    $pathToSkin . 'br' . $fileSuffix,
                    true,
                    'Breaking the line! Breaking the line!'
                ]
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

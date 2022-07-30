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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
        $this->renderHans = new RenderHans(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderHans);
        $this->mockTemplate();
        $this->mockEmergencyHandler();
    }

    /**
     * Short circuiting the existence of a specific template file.
     * Nice, huh?
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())->method('getFileContents')
            ->will($this->returnValueMap([
                // connectorLeft.html
                [
                    static::PATH_TO_SKIN . 'connectorLeft' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerConnectorLeft())
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . 'connectorRight' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerConnectorRight())
                ],
                // helprow.html
                [
                    static::PATH_TO_SKIN . 'helprow' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerHelpRow())
                ],
                // help.html
                [
                    static::PATH_TO_SKIN . 'help' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerHelp())
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . 'recursion' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerRecursion())
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . 'header' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerHeader())
                ],
                // search.html
                [
                    static::PATH_TO_SKIN . 'search' . $fileSuffix,
                    true,
                    ''
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . 'footer' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerFooter())
                ],
                // caller.html
                [
                    static::PATH_TO_SKIN . 'caller' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerCaller())
                ],
                // singlePlugin.html
                [
                    static::PATH_TO_SKIN . 'singlePlugin' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSinglePlugin())
                ],
                // cssJs.html
                [
                    static::PATH_TO_SKIN . 'cssJs' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerCssJs())
                ],
                // singleChildExtra.html
                [
                  static::PATH_TO_SKIN . 'singleChildExtra' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSingleChildExtra())
                ],
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . 'sourcebutton' . $fileSuffix,
                    true,
                    'sourcebutton'
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . 'expandableChildNormal' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerExpandableChild())
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . 'nest' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerNest())
                ],
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . 'singleEditableChild' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSingleEditableChild())
                ],
                // singleInput.html
                [
                    static::PATH_TO_SKIN . 'singleInput' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSingleInput()) . '<input'
                ],
                // singleSelect.html
                [
                    static::PATH_TO_SKIN . 'single' . Fallback::RENDER_TYPE_SELECT . $fileSuffix,
                    true,
                    '{id}' .
                    implode('', $this->renderHans->getMarkerDropdownOptions())
                ],
                // singleSelectOption.html
                [
                    static::PATH_TO_SKIN . 'singleSelectOptions' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSelectOption())
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . 'singleButton' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerSingleButton())
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . 'fatalMain' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerFatalMain())
                ],
                // fatalHeader.html
                [
                    static::PATH_TO_SKIN . 'fatalHeader' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerFatalHeader())
                ],
                // messages.html
                [
                    static::PATH_TO_SKIN . 'message' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerMessages())
                ],
                // backtraceSourceLine
                [
                    static::PATH_TO_SKIN . 'backtraceSourceLine' . $fileSuffix,
                    true,
                    implode('', $this->renderHans->getMarkerBacktraceSourceLine())
                ],
                // singleChildHr.html
                [
                    static::PATH_TO_SKIN . 'singleChildHr' . $fileSuffix,
                    true,
                    'HR does not mean human resources'
                ],
                // br.html
                [
                    static::PATH_TO_SKIN . 'br' . $fileSuffix,
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

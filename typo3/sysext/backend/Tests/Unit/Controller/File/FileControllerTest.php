<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller\File;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for \TYPO3\CMS\Backend\Tests\Unit\Controller\File\FileController
 */
class FileControllerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\File|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileResourceMock;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $folderResourceMock;

    /**
     * @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockFileProcessor;

    /**
     * @var ServerRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->fileResourceMock = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\File::class)
            ->setMethods(['toArray', 'getModificationTime', 'getExtension'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->folderResourceMock = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\Folder::class)
            ->setMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockFileProcessor = $this->getMockBuilder(\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::class)
            ->setMethods(['getErrorMessages'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileResourceMock->expects($this->any())->method('toArray')->will($this->returnValue(['id' => 'foo']));
        $this->fileResourceMock->expects($this->any())->method('getModificationTime')->will($this->returnValue(123456789));
        $this->fileResourceMock->expects($this->any())->method('getExtension')->will($this->returnValue('html'));

        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest->reveal();

        $this->request = new ServerRequest();
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function flattenResultDataValueReturnsAnythingElseAsIs()
    {
        $subject = $this->getAccessibleMock(FileController::class, ['dummy']);
        $this->assertTrue($subject->_call('flattenResultDataValue', true));
        $this->assertSame([], $subject->_call('flattenResultDataValue', []));
    }

    /**
     * @test
     */
    public function flattenResultDataValueFlattensFile()
    {
        $subject = $this->getAccessibleMock(FileController::class, ['dummy']);

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render()->shouldBeCalled()->willReturn('');
        $iconFactoryProphecy->getIconForFileExtension(Argument::cetera())->willReturn($iconProphecy->reveal());

        $result = $subject->_call('flattenResultDataValue', $this->fileResourceMock);
        $this->assertSame(
            [
                'id' => 'foo',
                'date' => '29-11-73',
                'icon' => '',
                'thumbUrl' => '',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestDeleteProcessActuallyDoesNotChangeFileData()
    {
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['delete' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);

        $subject->expects($this->once())->method('main');

        $subject->processAjaxRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function processAjaxRequestEditFileProcessActuallyDoesNotChangeFileData()
    {
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['editfile' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);

        $subject->expects($this->once())->method('main');

        $subject->processAjaxRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus200IfNoErrorOccures()
    {
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['editfile' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);

        $result = $subject->processAjaxRequest($this->request, $this->response);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus500IfErrorOccurs()
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main']);
        $this->mockFileProcessor->expects($this->any())->method('getErrorMessages')->will($this->returnValue(['error occured']));
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $result = $subject->processAjaxRequest($this->request, $this->response);
        $this->assertEquals(500, $result->getStatusCode());
    }
}

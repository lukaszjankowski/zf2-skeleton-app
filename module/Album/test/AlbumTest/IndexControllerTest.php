<?php

namespace AlbumTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Http\Request;
use Zend\Http\Response;

class AlbumControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    protected function commonControllerAssert(
        $expectedController,
        $expectedAction,
        $expectedRoute,
        $expectedStatusCode = Response::STATUS_CODE_200
    ) {
        $this->assertResponseStatusCode($expectedStatusCode);
        $this->assertControllerName($expectedController);
        $this->assertActionName($expectedAction);
        $this->assertMatchedRouteName($expectedRoute);
    }

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/application.config.php'
        );
        parent::setUp();
        $this->getApplicationServiceLocator()->setAllowOverride(true);
    }

    public function testIndexAction()
    {
        $albumTableMock = $this->getMockBuilder('Album\Model\AlbumTable')
            ->disableOriginalConstructor()
            ->getMock();

        $resultSetMock = $this->getMockBuilder('\Zend\Db\ResultSet\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $albumTableMock->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($resultSetMock));
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $albumTableMock);

        $this->dispatch('/album');

        $this->commonControllerAssert('album\controller\album', 'index', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('albums', $variables);
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSet', $variables['albums']);
    }

    public function testAddWhenNoPost()
    {
        $this->dispatch('/album/add');

        $this->commonControllerAssert('album\controller\album', 'add', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('form', $variables);
        $this->assertInstanceOf('Album\Form\AlbumForm', $variables['form']);
        $this->assertQueryContentContains('div.container h1', 'Add new album');
    }

    public function testAddInvalidData()
    {
        $params = array('id' => '', 'title' => '', 'artist' => '');
        $this->dispatch('/album/add', Request::METHOD_POST, $params);

        $this->commonControllerAssert('album\controller\album', 'add', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('form', $variables);
        $this->assertInstanceOf('Album\Form\AlbumForm', $variables['form']);
        $this->assertQueryContentContains('div.container h1', 'Add new album');
        $this->assertQueryCount('form#album input.input-error', 2);
    }

    public function testAddSuccess()
    {
        $albumTableMock = $this->getMockBuilder('Album\Model\AlbumTable')
            ->disableOriginalConstructor()
            ->getMock();

        $albumTableMock->expects($this->once())
            ->method('saveAlbum');
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $albumTableMock);

        $params = array('id' => '', 'title' => 'Album from test', 'artist' => 'Artist from test');
        $this->dispatch('/album/add', Request::METHOD_POST, $params);

        $this->commonControllerAssert('album\controller\album', 'add', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }
}

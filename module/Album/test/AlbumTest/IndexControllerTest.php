<?php

namespace AlbumTest\Controller;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class AlbumControllerTest extends AbstractHttpControllerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $albumTableMock;

    protected $traceError = true;

    protected function commonControllerAssert(
        $expectedController,
        $expectedAction,
        $expectedRoute,
        $expectedStatusCode = Response::STATUS_CODE_200
    )
    {
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
        $this->albumTableMock = $this->getMockBuilder('Album\Model\AlbumTable')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIndexAction()
    {
        $resultSetMock = $this->getMockBuilder('\Zend\Db\ResultSet\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $this->albumTableMock->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($resultSetMock));
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $this->albumTableMock);

        $this->dispatch('/album');

        $this->commonControllerAssert('Album\Controller\Album', 'index', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('albums', $variables);
        $this->assertInstanceOf('Zend\Db\ResultSet\ResultSet', $variables['albums']);
    }

    public function testAddWhenNoPost()
    {
        $this->dispatch('/album/add');

        $this->commonControllerAssert('Album\Controller\Album', 'add', 'album');
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

        $this->commonControllerAssert('Album\Controller\Album', 'add', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('form', $variables);
        $this->assertInstanceOf('Album\Form\AlbumForm', $variables['form']);
        $this->assertQueryContentContains('div.container h1', 'Add new album');
        $this->assertQueryCount('form#album input.input-error', 2);
    }

    public function testAddSuccess()
    {
        $this->albumTableMock->expects($this->once())
            ->method('saveAlbum');
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $this->albumTableMock);

        $params = array('id' => '', 'title' => 'Album from test', 'artist' => 'Artist from test');
        $this->dispatch('/album/add', Request::METHOD_POST, $params);

        $this->commonControllerAssert('Album\Controller\Album', 'add', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }

    public function testEditWithInvalidId()
    {
        $this->dispatch('/album/edit/0');
        $this->commonControllerAssert('Album\Controller\Album', 'edit', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/add');

        $this->reset();
        $this->dispatch('/album/edit/99999');
        $this->commonControllerAssert('Album\Controller\Album', 'edit', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }

    public function testEditExistingAlbumNoPost()
    {
        $this->dispatch('/album/edit/2');

        $this->commonControllerAssert('Album\Controller\Album', 'edit', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('form', $variables);
        $this->assertInstanceOf('Album\Form\AlbumForm', $variables['form']);
        $this->assertArrayHasKey('id', $variables);
        $this->assertEquals(2, $variables['id']);
        $this->assertQueryContentContains('div.container h1', 'Edit album');
    }

    public function testEditExistingAlbumWhenPostInvalidData()
    {
        $params = array();

        $this->dispatch('/album/edit/2', Request::METHOD_POST, $params);

        $this->commonControllerAssert('Album\Controller\Album', 'edit', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('form', $variables);
        $this->assertInstanceOf('Album\Form\AlbumForm', $variables['form']);
        $this->assertArrayHasKey('id', $variables);
        $this->assertEquals(2, $variables['id']);
        $this->assertQueryContentContains('div.container h1', 'Edit album');
        $this->assertQueryCount('form#album input.input-error', 2);
    }

    public function testEditExistingAlbumWhenPostSuccess()
    {
        $album = new \Album\Model\Album();
        $testTitle = 'Album from test - ' . time();
        $testArtist = 'Artist from test - ' . time();
        $params = array('id' => 2, 'title' => $testTitle, 'artist' => $testArtist);
        $this->albumTableMock->expects($this->once())
            ->method('getAlbum')
            ->with(2)
            ->will($this->returnValue($album));
        $this->albumTableMock->expects($this->once())
            ->method('saveAlbum')
            ->with($album);
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $this->albumTableMock);

        $this->dispatch('/album/edit/2', Request::METHOD_POST, $params);

        $this->commonControllerAssert('Album\Controller\Album', 'edit', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }

    public function testDeleteFormInvalidId()
    {
        $this->dispatch('/album/delete/0');

        $this->commonControllerAssert('Album\Controller\Album', 'delete', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }

    public function testDeleteFormNotConfirmed()
    {
        $album = new \Album\Model\Album();
        $this->albumTableMock->expects($this->once())
            ->method('getAlbum')
            ->with(2)
            ->will($this->returnValue($album));
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $this->albumTableMock);

        $this->dispatch('/album/delete/2');

        $this->commonControllerAssert('Album\Controller\Album', 'delete', 'album');
        $variables = $this->getApplication()->getMvcEvent()->getViewModel()->getChildren()[0]->getVariables();
        $this->assertInternalType('array', $variables);
        $this->assertArrayHasKey('album', $variables);
        $this->assertSame($album, $variables['album']);
        $this->assertQueryContentContains('div.container h1', 'Delete album');
    }

    public function testDeleteFormConfirmedNotToDelete()
    {
        $this->dispatch('/album/delete/2', Request::METHOD_POST, $params = array('del' => 'No'));

        $this->commonControllerAssert('Album\Controller\Album', 'delete', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }

    public function testDeleteFormConfirmedToDelete()
    {
        $this->albumTableMock->expects($this->once())
            ->method('deleteAlbum')
            ->with(2);
        $this->getApplicationServiceLocator()->setService('Album\Model\AlbumTable', $this->albumTableMock);

        $this->dispatch('/album/delete/2', Request::METHOD_POST, $params = array('del' => 'Yes'));

        $this->commonControllerAssert('Album\Controller\Album', 'delete', 'album', Response::STATUS_CODE_302);
        $this->assertRedirectTo('/album/');
    }
}

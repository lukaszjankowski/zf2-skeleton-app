<?php
namespace Album\Controller;

use Album\Form\AlbumForm;
use Album\Model\Album;
use Album\Model\AlbumTable;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{
    /**
     * @var AlbumTable
     */
    private $albumTable;

    /**
     * @return AlbumTable
     */
    private function getAlbumTable()
    {
        if (!$this->albumTable) {
            $this->albumTable = $this->getServiceLocator()->get('Album\Model\AlbumTable');
        }
        return $this->albumTable;
    }

    public function indexAction()
    {
        return new ViewModel(
            array(
                'albums' => $this->getAlbumTable()->fetchAll()
            )
        );
    }

    public function addAction()
    {
        $form = new AlbumForm();
        $form->get('submit')->setValue('Add');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $album = new Album();
            $form->setInputFilter($album->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $album->exchangeArray($form->getData());
                $this->getAlbumTable()->saveAlbum($album);
                return $this->redirect()->toRoute('album');
            }
        }

        return array('form' => $form);
    }

    public function editAction()
    {
        $id = (int)$this->params('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('album', array('action' => 'add'));
        }

        try {
            $album = $this->getAlbumTable()->getAlbum($id);
        } catch (\Exception $e) {
            $this->redirect()->toRoute('album', array('action' => 'index'));
        }

        $form = new AlbumForm();
        $form->bind($album);
        $form->get('submit')->setValue('Save');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setInputFilter($album->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $this->getAlbumTable()->saveAlbum($album);
                return $this->redirect()->toRoute('album');
            }
        }

        return array('id' => $id, 'form' => $form);
    }

    public function deleteAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('album');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');
            if ($del == 'Yes') {
                $this->getAlbumTable()->deleteAlbum($id);
            }
            return $this->redirect()->toRoute('album');
        }

        return array('album' => $this->getAlbumTable()->getAlbum($id));
    }
}

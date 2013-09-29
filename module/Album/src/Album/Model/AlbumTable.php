<?php
namespace Album\Model;

use Zend\Db\TableGateway\TableGateway;

class AlbumTable
{
    /**
     * @var TableGateway
     */
    private $tableGateway;

    /**
     * @param TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll()
    {
        $resultSet = $this->tableGateway->select();
        return $resultSet;
    }

    /**
     * @param integer $id
     * @return Album
     * @throws \Exception
     */
    public function getAlbum($id)
    {
        $id = (int)$id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find album id=$id");
        }
        return $row;
    }

    /**
     * @param Album $album
     * @throws \Exception
     */
    public function saveAlbum(Album $album)
    {
        $data = array(
            'artist' => $album->getArtist(),
            'title' => $album->getTitle(),
        );
        $id = (int)$album->getId();

        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getAlbum($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception("Could not find album id=$id");
            }
        }
    }

    /**
     * @param integer $id
     * @throws \Exception
     */
    public function deleteAlbum($id)
    {
        $id = (int)$id;
        if ($this->getAlbum($id)) {
            $this->tableGateway->delete(array('id' => $id));
        } else {
            throw new \Exception("Could not find album id=$id");
        }
    }
}

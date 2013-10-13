<?php
namespace Album;

use Album\Model\Album;
use Album\Model\AlbumTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager;

class Module
{
    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/config/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php',
            include __DIR__ . '/config/routing.config.php',
            include __DIR__ . '/config/navigation.config.php'
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Album\Model\AlbumTable' => function (ServiceManager\ServiceManager $sm) {
                    $tableGateway = $sm->get('AlbumTableGateway');
                    $table = new AlbumTable($tableGateway);
                    return $table;
                },
                'AlbumTableGateway' => function (ServiceManager\ServiceManager $sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultsetPrototype = new ResultSet();
                    $resultsetPrototype->setArrayObjectPrototype(new Album());
                    return new TableGateway('album', $adapter, null, $resultsetPrototype);
                }
            )
        );
    }
}

<?php
return array(
    'navigation' => array(
        'default' => array(
            array(
                'label' => 'Album',
                'route' => 'album',
                'pages' => array(
                    array(
                        'label' => 'Add',
                        'route' => 'album',
                        'action' => 'add',
                    ),
                    array(
                        'label' => 'Edit',
                        'route' => 'album',
                        'action' => 'edit',
                    ),
                    array(
                        'label' => 'Delete',
                        'route' => 'album',
                        'action' => 'delete',
                    ),
                ),
            ),
        ),
    ),
);

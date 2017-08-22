<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace AmoCRMProductRows;

//use Zend\ServiceManager\Factory\InvokableFactory;

return array(
    'router' => array(
        'routes' => array(
            'basic' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/basic[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => Controller\AmoCRMBasicProductsController::class
                    )
                )
            ),
            'prod2entities_actions' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/prod2entities[/:action]',
                    'defaults' => array(
                        'controller' => Controller\ProductInCRMController::class,
                        'action' => 'link'
                    )
                )
            ),
            'prod2entities_main' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/prod2entities[/:id]',
                    'constraints' => array(
                        'id' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => Controller\ProductInCRMController::class
                    )
                )
            )
        )
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5'
    )
);

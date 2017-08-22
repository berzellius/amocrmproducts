<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 04.02.2017
 * Time: 16:47
 */

namespace AmoCRMProductRows\Controller;


use AmoCRMProductRows\Exceptions\SecurityException;
use Zend\Mvc\MvcEvent;

trait ControllerSecuredLogged
{
    use Securing, Logging;
    /**
     * Выполняется перед обработкой запроса
     * @param MvcEvent $e
     * @return mixed
     * @throws SecurityException
     */
    public function onDispatch(MvcEvent $e )
    {
        $this->dispatchSecuring();
        $this->dispatchLogging();

        $this->getResponse()->getHeaders()->addHeaderLine('Access-Control-Allow-Origin', '*');


        return parent::onDispatch( $e );
    }

}
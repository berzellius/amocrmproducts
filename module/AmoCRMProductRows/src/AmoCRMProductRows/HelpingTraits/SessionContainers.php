<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 12.02.2017
 * Time: 20:48
 */

namespace AmoCRMProductRows\HelpingTraits;


use Zend\Session\Container;

trait SessionContainers
{

    protected $sessionContainer;

    /**
     * @return Container
     */
    protected function seekSessionContainer($containerName){
        if($this->sessionContainer == null){
            $this->sessionContainer = new Container($containerName);
        }

        return $this->sessionContainer;
    }

    protected function getSessionData($containerName, $key){
        return $this->seekSessionContainer($containerName)->$key;
    }

    protected function setSessionData($containerName, $key, $value){
        $this->seekSessionContainer($containerName)->$key = $value;
    }
}
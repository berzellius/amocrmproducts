<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 04.02.2017
 * Time: 17:05
 */
//meme
namespace AmoCRMProductRows\Controller;


use Zend\Log\Logger;

trait Logging
{
    protected $log;

    public function dispatchLogging(){
        $this->getLog()->info("request: " . serialize($this->getReqData()));
    }

    /**
     * @return Logger
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param Logger $log
     */
    public function setLog(Logger $log)
    {
        $this->log = $log;
    }
}
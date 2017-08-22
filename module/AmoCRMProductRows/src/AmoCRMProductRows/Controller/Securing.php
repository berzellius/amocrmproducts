<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 04.02.2017
 * Time: 16:35
 */

namespace AmoCRMProductRows\Controller;

use AmoCRMProductRows\AmoCRMAPIClient\AmoCRMAPIServiceFactory;
use AmoCRMProductRows\Exceptions\SecurityException;
use AmoCRMProductRows\SecurityContext\ISecurityData;
use \Zend\Mvc\MvcEvent;

trait Securing
{
    protected $securityData;
    protected $amoCRMAPIServiceFactory;

    public function dispatchSecuring(){
        $sec = $this->checkSecurity();
        if(!$sec){
            throw new SecurityException('Checking security data failed!', 403);
        }
    }

    protected function getReqData(){
        $data = null;

        if(
            $this->request->getMethod() == 'GET'
        ){
            $data = $this->params()->fromQuery();
        }

        if(
            $this->request->getMethod() == 'POST'
        ){
            $data = $this->params()->fromPost();
        }

        return $data;
    }

    protected function checkSecurity(){
        $data = $this->getReqData();
        return ($this->getSecurityData()->checkSecurityKeys($data));
    }


    /**
     * @return ISecurityData
     */
    public function getSecurityData()
    {
        return $this->securityData;
    }

    /**
     * @param ISecurityData $securityData
     */
    public function setSecurityData(ISecurityData $securityData)
    {
        $this->securityData = $securityData;
    }

    /**
     * @return AmoCRMAPIServiceFactory
     */
    public function getAmoCRMAPIServiceFactory()
    {
        return $this->amoCRMAPIServiceFactory;
    }

    /**
     * @param AmoCRMAPIServiceFactory $amoCRMAPIServiceFactory
     */
    public function setAmoCRMAPIServiceFactory($amoCRMAPIServiceFactory)
    {
        $this->amoCRMAPIServiceFactory = $amoCRMAPIServiceFactory;
    }
}
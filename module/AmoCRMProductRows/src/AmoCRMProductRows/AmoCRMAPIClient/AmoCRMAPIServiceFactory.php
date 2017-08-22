<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 12.02.2017
 * Time: 16:28
 */

namespace AmoCRMProductRows\AmoCRMAPIClient;
use Zend\Log\Logger;

class AmoCRMAPIServiceFactory
{
    private $instance;
    protected $log;

    public function getAmoCRMAPIServiceInstance(array $requestData){
        if($this->instance == null){
            $rdata = array(
                'USER_LOGIN' => isset($requestData['amouser'])? $requestData['amouser'] : null,
                'USER_HASH' => isset($requestData['amohash'])? $requestData['amohash'] : null,
                'subdomain' => isset($requestData['subdomain'])? $requestData['subdomain'] : null
            );

            $this->instance = new APIServiceImpl();
            $this->instance->setAuthData($rdata);
            $this->instance->setLog($this->getLog());
        }

        return $this->instance;
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
    public function setLog($log)
    {
        $this->log = $log;
    }
}
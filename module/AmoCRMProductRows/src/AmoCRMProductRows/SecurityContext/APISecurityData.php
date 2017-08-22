<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 12.02.2017
 * Time: 20:36
 */

namespace AmoCRMProductRows\SecurityContext;


use AmoCRMProductRows\AmoCRMAPIClient\AmoCRMAPIServiceFactory;
use AmoCRMProductRows\HelpingTraits\SessionContainers;

class APISecurityData implements ISecurityData
{
    use SessionContainers;

    protected $amoCRMAPIServiceFactory;
    const session_container_name = 'SECURITY';

    /**
     * Проверка ключей безопасности
     * @param array $data
     * @return bool
     */
    public function checkSecurityKeys(array $data)
    {
        if($this->getSessionData(self::session_container_name, 'loggedAccount') != null)
            return true;

        $amoCRMAPIService = $this->getAmoCRMAPIServiceFactory()->getAmoCRMAPIServiceInstance($data);
        $acc = $amoCRMAPIService->getAccount();

        if($acc != null && $acc->id != null){
            $this->setSessionData(self::session_container_name, 'loggedAccount', $acc->id);
            return true;
        }

        return false;
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
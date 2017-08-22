<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 23.01.2017
 * Time: 20:20
 */

namespace AmoCRMProductRows\Controller;


use AmoCRMProductRows\Exceptions\SecurityException;
use AmoCRMProductRows\SecurityContext\ISecurityData;
use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

abstract class AbstractRestfulSecuredController extends AbstractRestfulController
{
    use ControllerSecuredLogged;


    /**
     * Поля, по которым возможен поиск
     * @return array
     */
    public abstract function getFieldsToSearch();

    protected function getWhereArrayByRequest(array $data){
        foreach ($this->getFieldsToSearch() as $field) {
            if(isset($data[$field])){
                $res[$field] = $data[$field];
            }
        }

        return $res;
    }

    /**
     * return Zend\View\Model\JsonModel with security context
     * @param  null|array|Traversable $variables
     * @param  array|Traversable $options
     * @return JsonModel
     */
    public function returnJsonModel($variables, $options = null){
        return new JsonModel($variables, $options);
    }
}
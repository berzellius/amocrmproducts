<?php

namespace AmoCRMProductRows\Controller;

use AmoCRMProductRows\Model\BasicProductTable;
use AmoCRMProductRows\Model\ResultSetProcessor;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 13:44
 */
class AmoCRMBasicProductsController extends AbstractRestfulSecuredController
{
    protected $basicProductTable;

    public function __construct(BasicProductTable $basicProductTable)
    {
        $this->basicProductTable = $basicProductTable;
    }

    public function getList(){
        return $this->returnJsonModel(
            ResultSetProcessor::asArray($this->getBasicProductTable()->fetchAll())
        );
    }

    /*
     * просто возвращаем существующие объекты, поскольку есть потребность в получении списка элементов методом POST
     */
    public function create($data){
        return $this->returnJsonModel(
            ResultSetProcessor::asArray($this->getBasicProductTable()->fetchAll())
        );
    }

    /**
     * @return BasicProductTable
     */
    public function getBasicProductTable()
    {
        return $this->basicProductTable;
    }

    /**
     * @param BasicProductTable $basicProductTable
     * @return AmoCRMBasicProductsController
     */
    public function setBasicProductTable($basicProductTable)
    {
        $this->basicProductTable = $basicProductTable;
        return $this;
    }

    /**
     * Поля, по которым возможен поиск
     * @return array
     */
    public function getFieldsToSearch()
    {
        return array(
            "id",
            "type"
        );
    }
}
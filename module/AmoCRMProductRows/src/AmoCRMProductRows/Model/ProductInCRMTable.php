<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 0:19
 */

namespace AmoCRMProductRows\Model;

use DomainException;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Filter\ToInt;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class ProductInCRMTable extends EntityTable
{
    public function getFullSumsAndAreasForEntities($entities){
        $select = $this->getTableGateway()->getSql()->select();

        $select->columns(
            array(
                'sum' => new Expression('sum(price*quantity)'),
                // суммируем всех кроме quantity == 1
                'area' => new Expression('sum(case when quantity = 1 then 0 else quantity end)'), //new Expression('sum(quantity)'),
                'entityId' => new Expression('"entityId"')
            )
        );

        $select->group("entityId");
        $entitiesPredicates = array();
        foreach ($entities as $entity){
            $entitiesPredicates[] = array('entityId' => $entity);
        }

        $select->where(array('entityId' => $entities), PredicateSet::OP_OR);
        //$res = $this->getTableGateway()->selectWith($select);

        $statement = $this->getTableGateway()->getSql()->prepareStatementForSqlObject($select);
        $res = $statement->execute();
        //$this->getTableGateway()-
        return $res;
    }

    /**
     * Retrieve input filter
     *
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        if($this->inputFilter){
            return $this->inputFilter;
        }

        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'name',
            'required' => true
        ]);

        $inputFilter->add([
            'name' => 'sku',
            'required' => true
        ]);

        $inputFilter->add([
            'name' => 'price',
            'required' => true,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        $inputFilter->add([
            'name' => 'type',
            'required' => true,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        $inputFilter->add([
            'name' => 'entityId',
            'required' => true,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        $inputFilter->add([
            'name' => 'quantity',
            'required' => true,
            'filters' => [
                ['name' => ToInt::class],
            ],
        ]);

        return $inputFilter;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 0:19
 */

namespace AmoCRMProductRows\Model;

use DomainException;
use \Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGateway;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

abstract class EntityTable implements InputFilterAwareInterface
{
    protected $inputFilter;
    const DEFAULT_SELECT_LIMIT = 50;

    /**
     * Set input filter
     * Метод не нужен в данной ситуации
     *
     * @param  InputFilterInterface $inputFilter
     * @return InputFilterAwareInterface
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new DomainException(sprintf(
            '%s does not allow injection of an alternate input filter',
            __CLASS__
        ));
    }

    public function fetchAll($where = null){
        $select = $this->getTableGateway()->getSql()->select();
        if($where != null) $select->where($where);
        $select->limit(self::DEFAULT_SELECT_LIMIT);
        $res = $this->getTableGateway()->selectWith($select);
        return $res;
    }

    public function __construct(TableGateway $tableGateway)
    {
        $this->setTableGateway($tableGateway);
    }

    public function add(Entity $entity)
    {
        $data = $entity->asArray();

        // Если применяется генерация id через последовательность, то нужно это учесть
        if($entity instanceof EntityWithId){
            // генерируем id
            $data['id'] = new Expression("nextval('" . $entity->getSequenceName() . "')");
            $this->getTableGateway()->insert($data);
            // определяем последний результат через имя последовательности
            return $this->getTableGateway()->getAdapter()->getDriver()->getLastGeneratedValue($entity->getSequenceName());
        }

        $this->getTableGateway()->insert($data);
        return $this->getTableGateway()->getLastInsertValue();
    }

    public function update(Entity $entity, $where = null, $joins = null){
        $data = $entity->asArray();
        $this->getTableGateway()->update($data, $where, $joins);
    }

    public function delete(array $where){
        $this->getTableGateway()->delete($where);
    }

    protected $tableGateway;

    /**
     * @return TableGateway
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    /**
     * @param TableGateway $tableGateway
     */
    public function setTableGateway($tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }
}
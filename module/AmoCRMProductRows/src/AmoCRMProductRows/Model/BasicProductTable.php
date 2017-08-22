<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 14:59
 */

namespace AmoCRMProductRows\Model;


use Zend\Db\TableGateway\TableGateway;
use Zend\InputFilter\InputFilterInterface;

class BasicProductTable extends EntityTable
{
    /**
     * Retrieve input filter
     *
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }
}
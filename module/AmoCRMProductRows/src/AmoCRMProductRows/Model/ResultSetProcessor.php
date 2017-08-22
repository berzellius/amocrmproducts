<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 0:15
 */

namespace AmoCRMProductRows\Model;


use Zend\Db\ResultSet\ResultSet;

class ResultSetProcessor
{
    public static function asArray(ResultSet $resultSet){
        $res = array();
        foreach ($resultSet as $item) {
            $res[] = $item;
        }
        return $res;
    }
}
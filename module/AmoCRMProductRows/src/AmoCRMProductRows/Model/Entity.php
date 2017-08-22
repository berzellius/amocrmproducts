<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 23:23
 */

namespace AmoCRMProductRows\Model;


abstract class Entity
{
    public function exchangeArray($data){
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->name;
            if(!empty($data[$name])){
                $property->setValue($this, $data[$name]);
            }
        }
    }

    public function asArray(){
        $res = array();
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $val = $property->getValue($this);
            $res[$name] = $val;
        }

        return $res;
    }

    public function asJson(){
        return json_encode($this->asArray());
    }
}
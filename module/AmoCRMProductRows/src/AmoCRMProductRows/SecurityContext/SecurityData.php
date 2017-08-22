<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 23.01.2017
 * Time: 20:16
 */

namespace AmoCRMProductRows\SecurityContext;


class SecurityData implements ISecurityData
{

    protected $keys;

    /**
     * Проверка ключей безопасности
     * @param array $data
     * @return bool
     */
    public function checkSecurityKeys(array $data)
    {
        foreach ($this->getKeys() as $keys){
            $passed = true;
            foreach ($keys as $k => $v){
                if(!isset($data[$k]) || $data[$k] != $v){ $passed = false; }
            }

            if($passed){ return true; }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param array $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }
}
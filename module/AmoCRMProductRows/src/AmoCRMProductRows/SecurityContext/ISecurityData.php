<?php

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 23.01.2017
 * Time: 20:07
 */
namespace AmoCRMProductRows\SecurityContext;

interface ISecurityData
{

    /**
     * Проверка ключей безопасности
     * @param array $data
     * @return bool
     */
    public function checkSecurityKeys(array $data);
}
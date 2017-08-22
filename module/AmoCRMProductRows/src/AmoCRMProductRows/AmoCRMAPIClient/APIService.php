<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 12.02.2017
 * Time: 16:16
 */

namespace AmoCRMProductRows\AmoCRMAPIClient;


interface APIService
{
    /**
     * Получить данные о контакте
     * @param $id
     * @return \stdClass
     */
    public function getContactById($id);

    /**
     * Получить данные о компании
     * @param $id
     * @return \stdClass
     */
    public function getCompanyById($id);

    /**
     * Получить развернутые данные по сделке (c раскрытой информацией о менеджере, основном контакте и т.д.)
     * @param $id
     * @return \stdClass
     */
    public function getExtendedLeadDataById($id);

    /**
     * Получить данные сделки по ее id
     * @param $id
     * @return \stdClass
     */
    public function getLeadDataById($id);

    public function setAuthData(array $auth_data);
}
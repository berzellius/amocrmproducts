<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 13:19
 */

namespace AmoCRMProductRows\Model;


abstract class EntityWithId extends Entity
{

    /**
     * EntityWithId содержит id, сгенерированный через sequence.
     * Метод нужен для получения имени sequenc'a
     * @return string
     */
    public abstract function getSequenceName();
}
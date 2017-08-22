<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 23:18
 */

namespace AmoCRMProductRows\Model;


class ProductInCRM extends EntityWithId
{
    /**
     * @Id
     * @GeneratedValue(strategy = GenerationType.AUTO, generator = "prod2ent_id_gen")
     * @SequenceGenerator(name = "prod2ent_id_gen", sequenceName = "prod2ent_id_seq")
     * @Column(type="integer")
     */
    public $id;
    public $sku;
    public $name;
    public $price;
    public $type;
    public $entityId;
    public $quantity;

    public function getSequenceName()
    {
        return "prod2ent_id_seq";
    }
}
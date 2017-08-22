<?php

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 13:45
 */
namespace AmoCRMProductRows\Form;
use \Zend\Form\Form;

class ProductInCRMForm extends Form
{
    public function __construct($name = null, array $options = [])
    {
        parent::__construct("productInCRM");


        $this->add(
            [
                "name" => "sku",
                "type" => "hidden"
            ]);
        $this->add(
            [
                "name" => "name",
                "type" => "hidden"
            ]);
        $this->add(
            [
                "name" => "price",
                "type" => "hidden",
            ]);
        $this->add([
                "name" => "type",
                "type" => "hidden"
            ]);
        $this->add(
            [
                "name" => "entityId",
                "type" => "hidden"
            ]
        );

        $this->add(
            [
                "name" => "quantity",
                "type" => "hidden"
            ]
        );
    }
}
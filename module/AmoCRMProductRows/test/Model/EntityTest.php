<?php

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 23:27
 */

namespace AmoCRMProductRows\Test\Model;
use AmoCRMProductRows\Model\BasicProduct;
use \PHPUnit_Framework_TestCase;
use Symfony\Component\Yaml\Tests\B;

spl_autoload_register(function ($class_name) {
    include __DIR__ . '\\..\\..\\src\\' . $class_name . '.php';
});

class EntityTest extends \PHPUnit_Framework_TestCase
{
    public function testExchangeArray(){
        $basicProduct = new BasicProduct();
        $data = array(
            "id" => 7,
            "name" => 'kind of name',
            "price" => 10
        );

        $basicProduct->exchangeArray($data);

        foreach ($data as $k => $v) {
            $this->assertEquals($basicProduct->$k, $data[$k]);
        }
    }

    public function testAsArray(){
        $basicProduct = new BasicProduct();
        $basicProduct->id = 88;
        $basicProduct->name = 'name of basic Product';
        $basicProduct->price = 999;
        $basicProduct->sku = "b8";

        foreach ($basicProduct->asArray() as $k => $v) {
            $this->assertEquals($basicProduct->$k, $v);
        }
    }
}
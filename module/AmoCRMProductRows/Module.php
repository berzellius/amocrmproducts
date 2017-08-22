<?php

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 21.01.2017
 * Time: 13:54
 */
namespace AmoCRMProductRows;

use AmoCRMProductRows\AmoCRMAPIClient\AmoCRMAPIServiceFactory;
use AmoCRMProductRows\AmoCRMAPIClient\APIServiceImpl;
use AmoCRMProductRows\Model\BasicProduct;
use AmoCRMProductRows\Model\ProductInCRMTable;
use AmoCRMProductRows\SecurityContext\APISecurityData;
use AmoCRMProductRows\SecurityContext\ISecurityData;
use AmoCRMProductRows\SecurityContext\SecurityData;
use Dompdf\Dompdf;
use dompdfmodule\Factory\dompdfFactory;
use HttpRequest;
use Zend\Db\Adapter\AdapterInterface;
use AmoCRMProductRows\Model\BasicProductTable;
use AmoCRMProductRows\Controller;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ModelInterface;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
//class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig(){
        return array(
            'factories' => array(
                Model\BasicProductTable::class => function($container){
                    $tableGateway = $container->get(Model\BasicProductTableGateway::class);
                    $table = new BasicProductTable($tableGateway);
                    return $table;
                },
                Model\BasicProductTableGateway::class => function($container){
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\BasicProduct());
                    return new TableGateway('basic_product', $dbAdapter, null, $resultSetPrototype);
                },
                Model\ProductInCRMTable::class => function($container){
                    $tableGateway = $container->get(Model\ProductsToCRMEntitiesTableGateway::class);
                    $table = new ProductInCRMTable($tableGateway);
                    return $table;
                },
                Model\ProductsToCRMEntitiesTableGateway::class => function($container){
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\ProductInCRM());
                    return new TableGateway('products_to_entities', $dbAdapter, null, $resultSetPrototype);
                },
                SecurityContext\ISecurityData::class => function($container){
                    //$securityData = new SecurityData();
                    //$securityData->setKeys($container->get('config')['crm_keys']);
                    $securityData = new APISecurityData();
                    $securityData->setAmoCRMAPIServiceFactory($container->get(AmoCRMAPIServiceFactory::class));

                    return $securityData;
                },
                Logger::class => function($container){
                    $file = $container->get('config')['logger']['file_location'];

                    $stream = @fopen($file, 'a', false);
                    if ($stream) {
                        $logger = new Logger();
                        $writer = new Stream($stream);
                        $logger->addWriter($writer);

                        return $logger;
                    }
                    else {
                        throw new \Exception("logger not started! :: " . $file . serialize($container->get('config')['logger']['file_location']));
                    }
                },
                AmoCRMAPIServiceFactory::class => function($container){
                    $factory = new AmoCRMAPIServiceFactory();
                    $factory->setLog($container->get(Logger::class));

                    return $factory;
                }
                /*Dompdf::class => function($container){
                    $domPdfFactory = new dompdfFactory();
                    return $domPdfFactory->__invoke($container, 'dompdf');
                }*/
            )
        );
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\AmoCRMBasicProductsController::class => function($container) {
                    $amoCRMBasicProductsController = new Controller\AmoCRMBasicProductsController(
                        $container->get(Model\BasicProductTable::class)
                    );

                    $amoCRMBasicProductsController->setLog($container->get(Logger::class));
                    $amoCRMBasicProductsController->setSecurityData($container->get(ISecurityData::class));
                    return $amoCRMBasicProductsController;
                },
                Controller\ProductInCRMController::class => function($container) {
                    $productInCRMController = new Controller\ProductInCRMController(
                        $container->get(Model\ProductInCRMTable::class)
                    );

                    $productInCRMController->setAmoCRMAPIServiceFactory($container->get(AmoCRMAPIServiceFactory::class));
                    $productInCRMController->setDomPDF($container->get('dompdf'));
                    $productInCRMController->setLog($container->get(Logger::class));
                    $productInCRMController->setSecurityData($container->get(ISecurityData::class));
                    return $productInCRMController;
                }
            ],
        ];
    }

    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $e)
    {

        // attach a listener to check for errors
        $events = $e->getTarget()->getEventManager();
        $events->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER, array($this, 'onRenderError'));
    }

    /**
     * При обработке ошибки возвращает Json объект
     * https://akrabat.com/returning-json-errors-in-a-zf2-application/
     * @param $e
     */
    public function onRenderError($e){
        // must be an error
        if (!$e->isError()) {
            return;
        }

        // Check the accept headers for application/json
        $request = $e->getRequest();

        $headers = $request->getHeaders();
        if (!$headers->has('Accept')) {
            return;
        }

        // make debugging easier if we're using xdebug!
        ini_set('html_errors', 0);

        // if we have a JsonModel in the result, then do nothing
        $currentModel = $e->getResult();
        if ($currentModel instanceof JsonModel) {
            return;
        }

        // create a new JsonModel - use application/api-problem+json fields.
        $response = $e->getResponse();
        $model = new JsonModel(array(
            "success" => false,
            "reason" => $response->getReasonPhrase(),
        ));

        // Find out what the error is
        $exception  = $currentModel->getVariable('exception');

        if ($currentModel instanceof ModelInterface && $currentModel->reason) {
            switch ($currentModel->reason) {
                case 'error-controller-cannot-dispatch':
                    $model->message = 'The requested controller was unable to dispatch the request.';
                    break;
                case 'error-controller-not-found':
                    $model->message = 'The requested controller could not be mapped to an existing controller class.';
                    break;
                case 'error-controller-invalid':
                    $model->message = 'The requested controller was not dispatchable.';
                    break;
                case 'error-router-no-match':
                    $model->message = 'The requested URL could not be matched by routing.';
                    break;
                default:
                    $model->message = $currentModel->message;
                    break;
            }
        }

        if ($exception) {
            if ($exception->getCode() && $exception->getCode() >= 200) {
                $e->getResponse()->setStatusCode($exception->getCode());
            }
            $model->message = $exception->getMessage();

            // find the previous exceptions
            $messages = array();
            while ($exception = $exception->getPrevious()) {
                $messages[] = "* " . $exception->getMessage();
            };
            if (count($messages)) {
                $exceptionString = implode("n", $messages);
                $model->messages = $exceptionString;
            }
        }

        // set our new view model
        $model->setTerminal(true);
        $e->setResult($model);
        $e->setViewModel($model);
    }
}
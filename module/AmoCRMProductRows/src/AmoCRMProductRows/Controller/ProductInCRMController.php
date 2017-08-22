<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 22.01.2017
 * Time: 0:27
 */

namespace AmoCRMProductRows\Controller;


use AmoCRMProductRows\AmoCRMAPIClient\AmoCRMAPIServiceFactory;
use AmoCRMProductRows\Form\ProductInCRMForm;
use AmoCRMProductRows\Model\ProductInCRM;
use AmoCRMProductRows\Model\ProductInCRMTable;
use AmoCRMProductRows\Model\ResultSetProcessor;
use AmoCRMProductRows\Utils\Num2Str;
use Dompdf\Dompdf;
use FontLib\EOT\Header;
use Zend\Console\Response;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Http\Headers;
use Zend\Http\Response\Stream;
use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;

class ProductInCRMController extends AbstractRestfulSecuredController
{
    const DEFAULT_TYPE = 2;

    protected $productInCRMTable;

    protected $domPDF;

    /**
     * ProductsToCRMEntitiesController constructor.
     * @param $productsToCRMEntitiesTable
     */
    public function __construct($productsToCRMEntitiesTable)
    {
        $this->productInCRMTable = $productsToCRMEntitiesTable;
    }

    public function getList(){
        $res = ResultSetProcessor::asArray($this->getProductInCRMTable()->fetchAll());
        return $this->returnJsonModel($res);
    }

    public function create($data){
        $productInCRM = new ProductInCRM();

        $form = new ProductInCRMForm();
        $form->setData($data);
        $form->setInputFilter($this->getProductInCRMTable()->getInputFilter());


        if($form->isValid()){
            $productInCRM->exchangeArray($form->getData());
            $id = $this->getProductInCRMTable()->add($productInCRM);

            if($id != null) {
                return $this->returnJsonModel(array("success" => true, "insert" => $id));
            }
            else{
                return $this->returnJsonModel(array("success" => false, "message" => "unknown error!"));
            }
        }

        /*
         * Запрос на добавление не валидирован => переходим к отбору и $data используем как фильтр
         */
        $where = $this->getWhereArrayByRequest($data);
        $res = ResultSetProcessor::asArray($this->getProductInCRMTable()->fetchAll($where));
        return $this->returnJsonModel($res);
    }

    public function checkoutpdfAction()
    {
        $this->getLog()->info("(checkoutBeforeDownloadPdfOffer)");
        if ($this->request->getMethod() == 'POST') {
            $data = $this->params()->fromPost();
            if (isset($data['entityId']) && $data['entityId']) {
                $this->getLog()->info('entityId: ' . $data['entityId']);

                $amoCRMAPIService = $this->getAmoCRMAPIServiceFactory()->getAmoCRMAPIServiceInstance($data);
                $lead = $amoCRMAPIService->getExtendedLeadDataById($data['entityId']);

                $problems = array();

                if(!isset($lead->seller)){
                    $problems[] = 'Не указано юр. лицо, от которого высылать КП!';
                }

                if(!in_array('382114', $lead->tagList)) {
                    if(!isset($lead->area)){
                        $problems[] = 'Не указана площадь!';
                    }

                    if(!isset($lead->company) || $lead->company == null){
                        $problems[] = 'Не указана Компания, которой направляется КП!';
                    }

                    if(!isset($lead->contact)){
                        $problems[] = 'Не указано Контактное лицо Компании, которой направляется КП!';
                    }

                    if(!isset($lead->payment_way)){
                        $problems[] = 'Не указан способ оплаты';
                    }

                    switch ($lead->pipeline_id) {
                        case '299382':
                            // особенности для Контрактов
                            break;
                        default:
                            // особенности для Разовых работ
                            break;
                    }
                }
                else{
                    // особенности для мероприятий
                }

                $where = $this->getWhereArrayByRequest($data);
                $res = ResultSetProcessor::asArray($this->getProductInCRMTable()->fetchAll($where));

                if(count($res) == 0){
                    $problems[] = 'Не сохранено товаров для данной сделки!';
                }

                if(count($problems) > 0){
                    return $this->returnJsonModel(array(
                        'success' => false,
                        'problems' => $problems,
                        //'leadData' => $lead
                    ));
                }
                else{
                    return $this->returnJsonModel(array('success' => true));
                }
            }
            else{
                return $this->returnJsonModel(array('success' => false, 'error' => 'absent entityId in request'));
            }
        }
        else{
            $this->returnJsonModel(array('success' => false, 'error' => 'must be POST request!'));
        }
    }

    public function pdfAction(){
        $this->getLog()->info("(downloadPdfOffer)");
        if($this->request->getMethod() == 'POST') {

            $data = $this->params()->fromPost();
            if(isset($data['entityId']) && $data['entityId']) {
                $this->getLog()->info('entityId: ' . $data['entityId']);

                $where = $this->getWhereArrayByRequest($data);
                $res = ResultSetProcessor::asArray($this->getProductInCRMTable()->fetchAll($where));

                $sum = 0;

                foreach ($res as $prod){
                    $sum += $prod->price * $prod->quantity;
                }
                $sum_text = Num2Str::num2str($sum);
                $resolver = new TemplateMapResolver();
                $resolver->setMap(array(
                    'pdfTemplate' => __DIR__ . '/../view/amocrmproductrows/pdf/offer.phtml',
                    'contractPdfTemplate' => __DIR__ . '/../view/amocrmproductrows/pdf/offer_contract.phtml',
                    'eventPdfTemplate' => __DIR__ . '/../view/amocrmproductrows/pdf/offer_event.phtml'
                ));

                $uri = $this->getRequest()->getUri();
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                $port = $uri->getPort();
                $base = sprintf('%s://%s', $scheme, $host);

                $view = new PhpRenderer();
                $vm = new ViewModel();
                //$vm->setTemplate('pdfTemplate');
                $vm->setVariable('base_path', $base . ':' . $port);
                $vm->setVariable('static_path', __DIR__ . '/../../../../../public/');
                $vm->setVariable('products', $res);
                $vm->setVariable('entityId', $data['entityId']);
                $vm->setVariable('sum', $sum);
                $vm->setVariable('sum_text', $sum_text);

                $amoCRMAPIService = $this->getAmoCRMAPIServiceFactory()->getAmoCRMAPIServiceInstance($data);

                $lead = $amoCRMAPIService->getExtendedLeadDataById($data['entityId']);
                $vm->setVariable('lead' , $lead);
                switch ($lead->pipeline_id){
                    case '299382':
                        $vm->setTemplate('contractPdfTemplate');
                        break;
                    default:
                        if(in_array('382114', $lead->tagList)) {
                            $vm->setTemplate('eventPdfTemplate');
                        }
                        else{
                            $vm->setTemplate('pdfTemplate');
                        }
                        break;
                }


                $view->setResolver($resolver);
                $html2 = $view->render($vm);
                $domPDF = $this->getDomPDF();
                $domPDF->loadHtml($html2);
                $domPDF->render();
                $domPDF->stream('КП ' . $lead->name);
            }
            else return $this->returnJsonModel(array('success' => false, 'error' => 'absent entityId in request'));
        }
        else{
            return $this->returnJsonModel(array('success' => false, 'error' => 'Accept only post request'));
        }

        return null;
    }

    public function apiAction(){
        $this->getLog()->info("(apiAction)");
        if($this->request->getMethod() == 'POST') {
            $data = $this->params()->fromPost();
            $this->getLog()->info("is post");

            $amoCRMAPIService = $this->getAmoCRMAPIServiceFactory()->getAmoCRMAPIServiceInstance($data);
                //AmoCRMAPIServiceFactory::getAmoCRMAPIServiceInstance($data);

            $res = $amoCRMAPIService->getLeadDataById(10561400);
            die($res->name);
        }

        return null;
    }

    public function linkAction(){
        $this->getLog()->info("(linkAction)");
        if($this->request->getMethod() == 'POST') {
            $data = $this->params()->fromPost();
            $this->getLog()->info("is post");

            $entities = array();

            // link
            if(isset($data['link']) && sizeof($data['link'])){
                foreach ($data['link'] as $link) {
                    $productInCRMData = array(
                        'id' => $link['id'],
                        'type' => isset($link['type'])? $link['type'] : self::DEFAULT_TYPE,
                        'price' => $link['price'],
                        'quantity' => $link['quantity'],
                        'entityId' => $link['from_id'],
                        'name' => $link['name'],
                        'sku' => $link['sku']
                    );
                    $this->getLog()->info("link element: " . serialize($productInCRMData));

                    if(!isset($entities[$productInCRMData['entityId']])){
                        $entities[$productInCRMData['entityId']] = array();
                    }

                    $form = new ProductInCRMForm();
                    $form->setData($productInCRMData);
                    $form->setInputFilter($this->getProductInCRMTable()->getInputFilter());

                    if($form->isValid()){
                        $this->getLog()->info("validated");
                        $productInCRM = new ProductInCRM();
                        $productInCRM->exchangeArray($form->getData());
                        $fetch =
                            array(
                                'sku' => $productInCRMData['sku'],
                                'entityId' => $productInCRMData['entityId']
                            );
                        $found = $this->getProductInCRMTable()->fetchAll($fetch);

                        if($found != null && sizeof($found) > 0){
                            $this->getLog()->info("already exists " . sizeof($found) . " records");
                            $exists = ResultSetProcessor::asArray($found)[0];
                            $this->getLog()->info("working with " . json_encode($exists) . " >> type " . gettype($exists));
                            $exists->quantity = $productInCRMData['quantity'];
                            $this->getProductInCRMTable()->update($exists, $fetch);
                        }
                        else{
                            $this->getLog()->info("adding..");
                            $this->getProductInCRMTable()->add($productInCRM);
                        }
                    }
                    else{
                        $this->getLog()->info("data not validated!");
                        throw new \Exception("data not validated!");
                    }
                }
            }

            // unlink
            if(isset($data['unlink']) && sizeof($data['unlink']) > 0){
                foreach ($data['unlink'] as $unlink) {
                    if(
                        isset($unlink['entityId']) && (int)$unlink['entityId'] > 0 &&
                        isset($unlink['sku']) && sizeof($unlink['sku'])
                    ){
                        $toDelete = array(
                            'entityId' => $unlink['entityId'],
                            'sku' => $unlink['sku']
                        );
                        $this->getLog()->info("to delete: " . serialize($toDelete));
                        $this->getProductInCRMTable()->delete($toDelete);

                        if(!isset($entities[$toDelete['entityId']])){
                            $entities[$toDelete['entityId']] = array();
                        }
                    }
                    else{
                        $this->getLog()->info("data not validated!");
                        throw new \Exception("data not validated!");
                    }
                }
            }

            $fullSumAndArea = $this->getProductInCRMTable()->getFullSumsAndAreasForEntities(array_keys($entities));
            $resFullSumAndArea = array();

            foreach ($fullSumAndArea as $r){
                $resFullSumAndArea[] = $r;
            }

            return $this->returnJsonModel(array("success" => true, "sum_area" => $resFullSumAndArea));
        }
        else{
            return $this->returnJsonModel(array("success" => false, "error" => "Accept only post requests"));
        }
    }

    /**
     * @return ProductInCRMTable
     */
    public function getProductInCRMTable()
    {
        return $this->productInCRMTable;
    }

    /**
     * @param ProductInCRMTable $productInCRMTable
     */
    public function setProductInCRMTable($productInCRMTable)
    {
        $this->productInCRMTable = $productInCRMTable;
    }


    /**
     * Поля, по которым возможен поиск
     * @return array
     */
    public function getFieldsToSearch()
    {
        return array("id", "entityId", "type");
    }

    /**
     * @return Dompdf
     */
    public function getDomPDF()
    {
        return $this->domPDF;
    }

    /**
     * @param Dompdf $domPDF
     */
    public function setDomPDF(Dompdf $domPDF)
    {
        $this->domPDF = $domPDF;
    }
}
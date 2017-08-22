<?php
/**
 * Created by PhpStorm.
 * User: berz
 * Date: 12.02.2017
 * Time: 16:24
 */

namespace AmoCRMProductRows\AmoCRMAPIClient;

use AmoCRMProductRows\Controller\Logging;
use AmoCRMProductRows\HelpingTraits\SessionContainers;
use AmoCRMProductRows\Utils\Num2Str;
use Dompdf\Exception;
use Zend\Log\Logger;
use Zend\Session\Container;


class APIServiceImpl implements APIService
{
    use SessionContainers;

    const session_container_name = 'AMOCRM';
    const max_relogins = 5;
    const log_prefix = "AMOCRM API:";

    /**
     * данные для авторизации
     * @var array
     */
    protected $auth_data;
    protected $reLogins = 0;

    protected $log;

    protected function logIn(){
        $this->getLog()->info(self::log_prefix . " log in");

        $curl = $this->getCurlDescriptor2LogIn();
        $response = $this->processCurlData($curl);
        $this->setCookies($response['cookies']);
    }

    /**
     * @return Logger
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param Logger $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    protected function checkLoginData(){
        $cookies = $this->getSessionData(self::session_container_name, 'cookies');
        return isset($cookies['session_id']);
    }

    public function getExtendedLeadDataById($id){
        $account = $this->getAccount();
        $leadData = $this->getLeadDataById($id);
        if($leadData == null)
            return null;

        if($leadData->responsible_user_id != null){
            $respUser = null;
            foreach ($account->users as $user){
                if($user->id == $leadData->responsible_user_id){
                    $leadData->responsible_user = $user;
                    break;
                }
            }
        }

        if($leadData->linked_company_id != null && $leadData->linked_company_id != 0){
            $company = $this->getCompanyById($leadData->linked_company_id);
            if($company != null){
                $leadData->company = $company;
            }
        }

        if($leadData->main_contact_id != null && $leadData->main_contact_id != 0){
            $contact = $this->getContactById($leadData->main_contact_id);
            if($contact != null){
                $leadData->contact = $contact;
            }
        }

        foreach ($leadData->custom_fields as $custom_field){
            if($custom_field->id == '1609960'){
                $values = $custom_field->values;
                if(count($values) > 0){
                    $leadData->area = $values[0]->value;
                }
            }

            if($custom_field->id == '1914360'){
                $values = $custom_field->values;
                if(count($values) > 0){
                    $leadData->seller = array(
                        'name' => $values[0]->value,
                        'id' => $values[0]->enum
                    );
                }
            }

            if($custom_field->id == '1914364'){
                $values = $custom_field->values;
                if(count($values) > 0){
                    $leadData->payment_way = $values[0]->value;
                }
            }

            if($custom_field->id == '1914366'){
                $values = $custom_field->values;
                if(count($values) > 0){
                    $leadData->kp_comment = $values[0]->value;
                }
            }
        }

        if($leadData->price != null){
            $leadData->price_text = Num2Str::num2str($leadData->price);
        }

        $leadData->tagList = array();
        if($leadData->tags != null){
            foreach ($leadData->tags as $tag) {
                $leadData->tagList[] = $tag->id;
            }
        }

        return $leadData;
    }

    /**
     * Получить данные о контакте
     * @param $id
     * @return \stdClass
     */
    public function getContactById($id)
    {
        $url = 'contacts/list';
        $method = 'GET';

        $data = array('id' => $id);

        $this->getLog()->info(self::log_prefix . " getting contact data by id#" . $id);

        $res = $this->request($url, $method, $data);
        $resArr = json_decode($res);
        $contacts = $resArr->response->contacts;
        if(is_array($contacts) && count($contacts) > 0){
            return $contacts[0];
        }
        else{
            return null;
        }
    }

    /**
     * Получить данные о компании
     * @param $id
     * @return \stdClass
     */
    public function getCompanyById($id)
    {
        $url = 'company/list';
        $method = 'GET';

        $data = array('id' => $id);

        $this->getLog()->info(self::log_prefix . " getting company data by id#" . $id);

        $res = $this->request($url, $method, $data);
        $resArr = json_decode($res);
        $companies = $resArr->response->contacts;
        if(is_array($companies) && count($companies) > 0){
            return $companies[0];
        }
        else{
            return null;
        }
    }

    /**
     * Получить данные сделки по ее id
     * @param $id
     * @return \stdClass
     */
    public function getLeadDataById($id)
    {
        $url = 'leads/list';
        $method = 'GET';

        $data = array('id' => $id);

        $this->getLog()->info(self::log_prefix . " getting lead data by id#" . $id);

        $res = $this->request($url, $method, $data);
        if($res == null)
            return null;
        $resArr = json_decode($res);
        $leads = $resArr->response->leads;
        if(is_array($leads) && count($leads) > 0){
            return $leads[0];
        }
        else{
            return null;
        }
    }

    /**
     * Получить информацию об аккаунте
     * @return \stdClass
     */
    public function getAccount(){
        $url = 'accounts/current';
        $method = 'GET';

        $this->getLog()->info(self::log_prefix . " getting account info");
        $res = $this->request($url, $method);
        $resArr = json_decode($res);
        $acc = $resArr->response->account;

        return $acc;
    }

    /**
     * @return array
     */
    public function getAuthData()
    {
        return $this->auth_data;
    }

    /**
     * @param array $auth_data
     */
    public function setAuthData(array $auth_data)
    {
        $this->auth_data = $auth_data;
    }

    protected function request($url, $method = 'GET', $data = null){
        $curl = $this->getCurlDescriptor($url, $method, $data);
        $response = $this->processCurlData($curl);
        $this->setCookies($response['cookies']);

        if($response['code'] != 200 && $response['code'] != 204){
            if($response['code'] == 401){
                // Мы не авторизованы.
                if($this->reLogins < self::max_relogins){
                    $this->reLogins++;
                    $this->logIn();

                    return $this->request($url, $method, $data);
                }
                else{
                    throw new Exception("Превышено количество попыток авторизации", 401);
                }
            }

            throw new Exception("Неожиданный код ответа: " . $response['code'] . "; " . json_encode($response['body']));
        }

        return $response['body'];
    }

    protected function setCookies($cookies){
        $stored_cookies = $this->getSessionData(self::session_container_name, 'cookies');

        foreach ($cookies as $name => $value){
            $stored_cookies[$name] = $value;
        }

        $this->setSessionData(self::session_container_name, 'cookies', $stored_cookies);
    }

    protected function processCurlData($curl){
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
        curl_close($curl); #Завершаем сеанс cURL
        list($headers, $content) = explode("\r\n\r\n", $result, 2); # Делим ответ на заголовки и тело

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        return array(
            'body' => $content,
            'headers' => $headers,
            'cookies' => $cookies,
            'code' => $code
        );
    }

    protected function getCurlDescriptor($url, $method = 'GET', $data = null){
        $subdomain = $this->auth_data['subdomain'];
        $link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/' . $url;

        $headers = array(
            'Content-Type: application/json'
        );
        $cookies = $this->getSessionData(self::session_container_name, 'cookies');
        $cookies_strs = array();

        if($cookies != null) {
            foreach ($cookies as $k => $v) {
                $cookies_strs[] = $k . '=' . $v;
            }
            $headers[] = 'Cookie: ' . implode('; ', $cookies_strs);
        }

        $curl=curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT, 'apiclient');
        curl_setopt($curl,CURLOPT_HEADER, true);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 0);

        switch ($method){
            case 'GET':
                if(is_array($data)) {
                    $params = array();
                    foreach ($data as $k=>$v){
                        $params[] = $k . '=' . $v;
                    }
                    $link = $link . '?' . implode('&', $params);
                }
                break;
            case 'POST':
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
                if(is_array($data))
                    curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        curl_setopt($curl,CURLOPT_URL, $link);

        return $curl;
    }

    protected function getCurlDescriptor2LogIn(){
        $subdomain = $this->auth_data['subdomain'];
        $user = array(
            'USER_LOGIN' => $this->auth_data['USER_LOGIN'],
            'USER_HASH' => $this->auth_data['USER_HASH']
        );
        $link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';

        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'apiclient');
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($curl,CURLOPT_HEADER, true);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

        return $curl;
    }
}
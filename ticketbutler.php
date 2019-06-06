<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 
 * @author    San & Johan
 *  
 */

if (!defined('_PS_VERSION_')) {
    die(header('HTTP/1.0 404 Not Found'));
}

class TicketButler extends Module {

    public function __construct() {
        $this->name = 'ticketbutler';
        $this->tab = 'administration';
        $this->author = 'Johan';
        $this->version = '1.0';
        $this->need_instance = 0;
        $this->bootstrap = true;
 
        parent::__construct();
 
        $this->displayName = $this->l('Ticketbutler');
        $this->description = $this->l('Add Ticket fields to products and Ticketbutler API calls');
        $this->ps_versions_compliancy = array('min' => '1.7.1', 'max' => _PS_VERSION_);
    }
    

       public function install() {
        if (!parent::install() || !$this->_installSql()
                //Register hooks here
                || ! $this->registerHook('actionPaymentConfirmation')
                || ! $this->registerHook('displayAdminProductsExtra')
                || !Configuration::updateValue('ticketbutler_api', 'api')
                || !Configuration::updateValue('ticketbutler_token', 'token')
                
        ) {
            return false;
        }
 
        return true;
    }

    
 
    public function uninstall() { 
 
        return parent::uninstall() && $this->_unInstallSql() &&  
        !Configuration::deleteByName('ticketbutler_api') &&  
        !Configuration::deleteByName('ticketbutler_token') && $this->unregisterHook('displayAdminProductsExtra') && $this->unregisterHook('ActionPaymentConfirmation');
    }
 
    /**
     * Alter Products table
     * @return boolean
     */
    protected function _installSql() {
        $sqlInstall = "ALTER TABLE " . _DB_PREFIX_ . "product "
                . "ADD tbeventid VARCHAR(255) NULL,"
                . "ADD tbticketid VARCHAR(255) NULL";
       
        $returnSql = Db::getInstance()->execute($sqlInstall);
        
        return $returnSql;
    }
 
    /**
     * Alter Products table 
     * @return boolean
     */
    protected function _unInstallSql() {
       $sqlInstall = "ALTER TABLE " . _DB_PREFIX_ . "product "
                . "DROP tbeventid, DROP tbticketid";
 
        $returnSql = Db::getInstance()->execute($sqlInstall); 
 
        return $returnSql;
    }
 
  /**
     * api related hook code, this will execute when order status changed to payment accepted
     * 
     */

    public function hookActionPaymentConfirmation($params)
    {
        
        // Get order from $params and relevant objects

        $order_id = $params['id_order'];

        $order  = new Order($order_id);

        $api_endpoint = Configuration::get('ticketbutler_api');

        $auth_token = Configuration::get('ticketbutler_token');

        $curl_error_key = false;


         if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {

             $products = $order->getProducts(); 
             $customer = new Customer( $order->id_customer );
         
             $first_name = $customer->firstname; 
             $last_name = $customer->lastname;
             $email = $customer->email;

             foreach($products as $product){
 

                 $event_id = $product['tbeventid'];
                 $ticket_id = $product['tbticketid'];
                 $amount = $product['product_quantity'];


                 if((isset($event_id) && !empty($event_id)) && (isset($ticket_id) && !empty($ticket_id))){

                 $payload =   array ( 
                    'event' => $event_id,
                    'address' => 
                    array (
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => '',
                    ),
                    'ticket_types' =>
                    array (
                        0 => 
                        array (
                            'uuid' => $ticket_id,
                            'amount' => $amount, 
                        ),
                    ),
                    'external_order_id' => $order_id,
                );

         } 

        $json_payload = json_encode($payload);
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $api_endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_HTTPHEADER => array(
        "Authorization: TOKEN ".$auth_token,
        "Cache-Control: no-cache",
        "Content-Type: application/json"
        ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
        $curl_error_key = true;  
        }  
             
         }

         }
 

    }

    
 
    /**
     * Show products fields
     * @param type $params
     * @return type
     */
    public function hookDisplayAdminProductsExtra($params) {  
        $product = new Product($params['id_product']);
        $languages = Language::getLanguages(true);
        $this->context->smarty->assign(array(
            'tbeventid' => $product->tbeventid,
            'tbticketid' => $product->tbticketid,
            'languages' => $languages,
            'default_language' => $this->context->employee->id_lang,
            )
           );
        return $this->display(__FILE__, 'views/templates/hook/ticketbutler.tpl');
    }
  
    public function getContent()
{
    $output = null;

    if (Tools::isSubmit('submit'.$this->name)) {
        $ticketbutlerApiEndpoint = strval(Tools::getValue('ticketbutler_api'));
         $ticketbutlerApiToken = strval(Tools::getValue('ticketbutler_token'));

        if (
            !$ticketbutlerApiEndpoint ||
            empty($ticketbutlerApiEndpoint) ||
            !Validate::isGenericName($ticketbutlerApiEndpoint) || !$ticketbutlerApiToken ||
            empty($ticketbutlerApiToken) ||
            !Validate::isGenericName($ticketbutlerApiToken)
        ) {
            $output .= $this->displayError($this->l('Invalid Configuration value'));
        } else {
            Configuration::updateValue('ticketbutler_api', $ticketbutlerApiEndpoint);
            Configuration::updateValue('ticketbutler_token', $ticketbutlerApiToken);
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    return $output.$this->displayForm();
}



public function displayForm()
{
    
    $defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
 
    $fieldsForm[0]['form'] = [
        'legend' => [
            'title' => $this->l('Ticketbutler API Configuration'),
        ],
        'input' => [
            [
                'type' => 'text',
                'label' => $this->l('Ticketbutler API Endpoint'),
                'name' => 'ticketbutler_api',
                'size' => 20,
                'required' => true
            ],
        [
                'type' => 'text',
                'label' => $this->l('Ticketbutler API Token'),
                'name' => 'ticketbutler_token',
                'size' => 20,
                'required' => true
            ]
        ],
         'submit' => [
            'title' => $this->l('Save'),
            'class' => 'btn btn-default pull-right'
        ]  
 
    ];
 


    $helper = new HelperForm();
 
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
 
    $helper->default_form_language = $defaultLanguage;
    $helper->allow_employee_form_lang = $defaultLanguage;
 
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = [
        'save' => [
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ],
        'back' => [
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        ]
    ];
 
    $helper->fields_value['ticketbutler_api'] = Configuration::get('ticketbutler_api');
   $helper->fields_value['ticketbutler_token'] = Configuration::get('ticketbutler_token');

    return $helper->generateForm($fieldsForm);
}

}
<?php
/**
 *
 * @author    SchledermannCo <jo@schledermannco.com>
 * @copyright 2007-2019 SchledermannCo
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html General Public License (GPL 3.0)
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
        $this->author = 'SchledermannCo';
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
        ) {
            return false;
        }
 
        return true;
    }

    
 
    public function uninstall() { 
 
        return parent::uninstall() && $this->_unInstallSql() && $this->unregisterHook('displayAdminProductsExtra') && $this->unregisterHook('ActionPaymentConfirmation');
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
     
     // Our API code here
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
}
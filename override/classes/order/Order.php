<?php
/*
 * Override Class ProductCore
 */

class Order extends OrderCore { 
 
    public $tborder;
 
       
        public function __construct($id = null, $id_lang = null){
            Order::$definition['fields']['tborder'] = [
                'type' => self::TYPE_HTML,
                'required' => false
            ];
            
             
            parent::__construct($id, $id_lang);
    }
} 
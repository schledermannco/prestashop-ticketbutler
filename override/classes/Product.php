<?php
/*
 * Override Class ProductCore
 */

class Product extends ProductCore { 
	public $tbeventcheck;
	public $tbeventid;
	public $tbticketid;
	  
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null){
	 
			Product::$definition['fields']['tbeventid'] = [
	            'type' => self::TYPE_STRING,
	            'required' => false, 'size' => 255
	        ];
	        Product::$definition['fields']['tbticketid'] = [
	            'type' => self::TYPE_STRING,
	            'required' => false, 'size' => 255
	        ];
	         
	        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
	}
} 
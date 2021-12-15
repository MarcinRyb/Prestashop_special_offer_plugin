<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class homepage_special_offer extends Module
{
    public function __construct()
    {
        $this->name = 'homepage_special_offer';
        $this->tab = 'front_office_features';
        $this->version = '0.3.5';
        $this->author = 's18321';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PrestaShop special offer module');
        $this->description = $this->l('Display daily special offer on homepage.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
{
    if (Shop::isFeatureActive()) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }

    //install plugin, register hook to home page and set default config values
   return (
        parent::install() 
        // && $this->registerHook('leftColumn')
        // && $this->registerHook('header')
        && $this->registerHook('displayHome')
        && Configuration::updateValue('MYMODULE_NAME', 'Special Offer')
    ); 
}

//uninstall plugin along stored configuration 
public function uninstall()
{
    return (
        parent::uninstall() 
        && Configuration::deleteByName('MYMODULE_NAME')
    );
}
 
    public function getContent()
{
    
    $output = '';

    // this part is executed only when the form is submitted
    if (Tools::isSubmit('submit' . $this->name)) {
        // retrieve the value set by the user
        $configValue = (string) Tools::getValue('MYMODULE_NAME');

        // check that the value is valid
        if (empty($configValue) || !Validate::isGenericName($configValue)) {
            // invalid value, show an error
            $output = $this->displayError($this->l('Invalid Configuration value'));
        } else {
            // value is ok, update it and display a confirmation message
            Configuration::updateValue('MYMODULE_NAME', $configValue);
            $output = $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    // display any message, then the form
    return $output . $this->displayForm();
}

public function displayForm()
{
    // Init Fields form array
    $form = [
        'form' => [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Set Mode'),
                    'name' => 'MYMODULE_NAME',
                    'size' => 20,
                    'required' => true,
                ],
                //will implement
                // [
                //     'type' => 'select',
				// 		'label' => $this->l('When do you want the special offer to change?'),
				// 		'name' => 'MODE',
				// 		'default_value' => 0,
				// 		'options' => array(
				// 			'query' => array(
				// 				array('id' => 0, 'name' => $this->l('As soon as stock changes')),
				// 				array('id' => 1, 'name' => $this->l('Every day'))
				// 			),
				// 			'id' => 'id',
				// 			'name' => 'name',
				// 		)
                // ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ],
    ];

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->table = $this->table;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
    $helper->submit_action = 'submit' . $this->name;

    // Default language
    $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

    // Load current value into the form
    $helper->fields_value['MYMODULE_NAME'] = Tools::getValue('MYMODULE_NAME', Configuration::get('MYMODULE_NAME'));

    return $helper->generateForm([$form]);
}


 public function hookDisplayHome($params)
    {
        //setting id of product to be on sale
        $db = \Db::getInstance();
        $request = "select id_product from ". _DB_PREFIX_ ."stock_available where quantity= (select MAX(quantity) from ". _DB_PREFIX_ . "stock_available)";
        $fromsql = $db->getValue($request);
        $language = Context::getContext()->language->id;
        $id_product = $fromsql;
        $image = Image::getCover($id_product);
        $product = new Product($id_product, false, Context::getContext()->language->id);
        $link = new Link;
        $url = $link->getProductLink($product);
        $imagePath = $link->getImageLink($product->link_rewrite, $image['id_image'], 'home_default');


        //Writting offer html (return)
        $string = '<a href=' . $url . '><center><img src="http://'.$imagePath .'" alt="Image of the product" class="img-fluid" loading="lazy" width="250" height="250"></center></a><br></br>';
       

        //Trying to access and print a value from config
        $configValue = (string) Configuration::getValue('MYMODULE_NAME');


        // if ($configValue == null){
        //     $configValue = 'null';
        // }



        // checking language
        if ($language == 1
        ) {
            $string = $string . '<p style="text-align: center;">Today\'s special offer! '. gettype($configValue).'</p>';
        }
        if ($language == 2
        ) {
            $string = $string . '<p style="text-align: center;"> Specjalna Oferta</p>';
        }
        return $string;
    }
}


































        // $othersql = $db->getRow($requestsimples);
        // $db->getRow($requestsimple);
        // $requestsimplee = "select id_product from prestashop." . _DB_PREFIX_ . "product";
        // $requestsimple = $db->getRow($requestsimplee);
        //  $otherothersql = Db::getInstance() ->executeS('select id_product from ' . _DB_PREFIX_ . 'product where limit 1');
        //  $otherothersqlss = $db ->execute('select id_product from ' . _DB_PREFIX_ . 'product');
       
        // $id_product = 10;
// Language id
//$id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
 
// Load Product object
//$product = new Product($id_product, false, $id_lang);
//$product = new Product((int) $id_product);
// Validate Product object
//if (Validate::isLoadedObject($product)) {
 // Initialize the Link Object
// $link = new Link();
 
 // Get Product URL
 //$url = $link->getProductLink($product);

 //$image = Image::getCover($id_product);
 //$img = $product->getCover($product->id);
 //$img_url = $link->getImageLink(isset($product->link_rewrite) ? $product->link_rewrite : $product->name, (int)$img['id_image'], $image_type);
//$imagePath = $link->getImageLink($product->link_rewrite[Context::getContext()->language->id], $image['id_image'], 'home_default');
//<img class="js-qv-product-cover img-fluid" src="http://localhost:8080/12-medium_default/brown-bear-cushion.jpg" alt="Brown bear cushion" title="Test Img" loading="lazy" width="452" height="452">
//}
         //return '<a href="{$url}">Get product </a>';
        //return '<a href="https://prestashop.com/">PrestaShop.com</a>';
        //$string = '<p> id_product = ' . $id_product . '! </p>';
    //$string = '<p> fromsql' . $fromsql .' requestsimple = '. $requestsimple .' otherothersqlss = '. $otherothersqlss.' othersql = '. $othersql . ' otherothersql = ' . $otherothersql .' otherothersqls = '. $otherothersqls .' </a>';
        //$string = '<a href=' . $url . '>"<img alt="Qries" src=http//' . $img_url . "
       // width=\"150\" height=\"70\"></a>";
       //SELECT id_product FROM prestashop.ps_stock_available where quantity = (SELECT MAX(quantity) FROM prestashop.ps_stock_available);
        //$requestsimples = "select id_product from " . _DB_PREFIX_ . "product";
        
        //$otherothersqls = Db::getInstance() ->getValue('select id_product from prestashop.' . _DB_PREFIX_ . 'product');

        //$otherothersqlss = $db ->execute('select id_product from ' . _DB_PREFIX_ . 'product where quantity = ');
        //src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')|escape:'html':'UTF-8'}"
        
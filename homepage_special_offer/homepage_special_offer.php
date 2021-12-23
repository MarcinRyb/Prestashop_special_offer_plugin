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
        if (!Configuration::get('SPECIAL')) {
            $this->warning = $this->l('No name provided');
        }
        if (!Configuration::get('OFFER_MODE')) {
            $this->warning = $this->l('No test provided');
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
            && $this->registerHook('displayHome')
            && Configuration::updateValue('SPECIAL', 0)
            && Configuration::updateValue('OFFER_MODE', 0)
            && Configuration::updateValue('THRESHOLD', 1)
            && Configuration::updateValue('LAST_PRODUCT_ID', 25)
            &&  $this -> setLastSavedDate()
        );
    }

    //uninstall plugin along stored configuration
    public function uninstall()
    {
        return (
            parent::uninstall()
            && Configuration::deleteByName('SPECIAL')
            && Configuration::deleteByName('OFFER_MODE')
            && Configuration::deleteByName('LAST_SAVED_DATE')
            && Configuration::deleteByName('THRESHOLD')
            && Configuration::deleteByName('LAST_PRODUCT_ID')
        );
    }

    public function getContent()
    {
        $output = '';

        // this part is executed only when the form is submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            // retrieve the value set by the user
            $specialValue = (string) Tools::getValue('SPECIAL');
            $configValue = (string) Tools::getValue('OFFER_MODE');
            $thresholdValue = (int) Tools::getValue('THRESHOLD');

            // check that the value is valid
            if (($configValue == 3 &&!$this->searchForProduct($specialValue)) || empty($thresholdValue) || is_int($thresholdValue)!=1) {// (configValue==3 && empty($thresholdValue)) || (configValue==3 && empty($thresholdValue)) || !Validate::isGenericName($thresholdValue)) {//empty($configValue) || !Validate::isGenericName($configValue)
                // invalid value, show an error
                $output = $this->displayError($this->l('Invalid Configuration value'));
            } else {
                // value is ok, update it and display a confirmation message
                Configuration::updateValue('THRESHOLD', $thresholdValue);
                Configuration::updateValue('SPECIAL', $specialValue);
                Configuration::updateValue('OFFER_MODE', $configValue);
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
                        'label' => $this->l('Input specific ID'),
                        'name' => 'SPECIAL',
                        'size' => 20,
                    ],
                    [
                                        'type' => 'text',
                                        'label' => $this->l('set threshold'),
                                        'name' => 'THRESHOLD',
                                        'size' => 20,
                                    ],
                    [
                                                'type' => 'select',
                                                    'label' => $this->l('How do you want the special offer to change?'),
                                                    'name' => 'OFFER_MODE',
                                                    'options' => [
                                                        'query' => [
                                                            ['id' => 0, 'name' => $this->l('As soon as stock changes the biggest number in stock')],
                                                            ['id' => 1, 'name' => $this->l('Every day from the biggest number in stock')],
                                                            ['id' => 2, 'name' => $this->l('Every day at random')],
                                                            ['id' => 3, 'name' => $this->l('Specific ID')],
                                                        ],
                                                        'id' => 'id',
                                                        'name' => 'name',
                                                    ],
                                            ],
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
        $helper->fields_value['SPECIAL'] = Tools::getValue('SPECIAL', Configuration::get('SPECIAL'));
        $helper->fields_value['THRESHOLD'] = Tools::getValue('THRESHOLD', Configuration::get('THRESHOLD'));
        $helper->fields_value['OFFER_MODE'] = Tools::getValue('OFFER_MODE', Configuration::get('OFFER_MODE'));

        return $helper->generateForm([$form]);
    }
    private function setLastSavedDate(){
        $mydate=getdate(date("U"));
        $date_string = (string) $mydate[mday].'-'.$mydate[mon].'-'. $mydate[year];
        Configuration::updateValue('LAST_SAVED_DATE',$date_string);
        return true;
    }
    private function setTestDate(){
        $mydate=getdate(date("U"));
        $date_string = (string) '19'.'-'.$mydate[mon].'-'. $mydate[year];
        Configuration::updateValue('LAST_SAVED_DATE',$date_string);
        return true;
    }
    private function getTimeDiff(){
        $mydate=getdate(date("U"));
        $now_date_string = (string) $mydate[mday].'-'.$mydate[mon].'-'. $mydate[year];
        $now_Date=date_create($now_date_string);
        $lastSavedDate_string = (string) Configuration::get('LAST_SAVED_DATE');
        //$lastSavedDate_string = (string) $config_array['LAST_SAVED_DATE'];
        $lastSavedDate=date_create($lastSavedDate_string);
        $diff=date_diff($lastSavedDate,$now_Date);
        return (int) $diff->format("%a");
    }

    public function getConfigData(){

        $config_data_array = array();
        $config_data_array['SPECIAL'] = Configuration::get('SPECIAL');
        $config_data_array['OFFER_MODE'] = Configuration::get('OFFER_MODE');
        return $config_data_array;
    }


    private function createHrefForProduct($id_product){
        $image = Image::getCover($id_product);
        $product = new Product($id_product, false, Context::getContext()->language->id);
        $link = new Link;
        $url = $link->getProductLink($product);
        $imagePath = $link->getImageLink($product->link_rewrite, $image['id_image'], 'home_default');

        //Writing offer html (return)
        $string = '<a href=' . $url . '><center><img src="http://'.$imagePath .'" alt="Image of the product" class="img-fluid" loading="lazy" width="250" height="250"></center></a><br></br>';
        return $string;
    }

    private function getProductIdFromDb($sqlQuery){
        $db = \Db::getInstance();
        return $db->getValue($sqlQuery);
    }

    private function getAllProductsIds(){
        $db = \Db::getInstance();
        $threshold = Configuration::get('THRESHOLD');
        if($threshold == 0){
            $threshold = $threshold + 1;
        }
        return $db->executeS('select distinct id_product from '. _DB_PREFIX_ .'stock_available where quantity >='. $threshold);
    }

    private function selectProductWithBiggestStock(){
        $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
        $productId = $this->getProductIdFromDb($sql_query);
        $hrefForProduct = $this->createHrefForProduct($productId);
        Configuration::updateValue('LAST_PRODUCT_ID', $productId);
        return true;
    }

     private function searchForProduct($productId){
    $list_of_products = $this -> getAllProductsIds();
                    for ($i = 0; $i < count($list_of_products); $i++) {
                        $allProductsIds[$i] = $list_of_products[$i]['id_product'];
                    }
                    if (array_search($productId, $allProductsIds, $strict = false)){
                        return true;
                    }
                    return false;
    }

    public function hookDisplayHome($params)
    {
        $errors = '';
        $language = Context::getContext()->language->id;
        //Trying to access and print a value from config
        $config_array = $this->getConfigData(); //(string) Configuration::getValue('MODULE_INTERVAL');
        $configValue = $config_array['OFFER_MODE'];

        // test code beginning
        if ($config_array['SPECIAL']=='test'){
            $this -> setTestDate();
            $errors = $errors . 'test day has been set';
        } else{
            $errors = $errors . 'test day not set';
        }
        // test code end


        $sql_query = '';
        $hrefForProduct = '';
        $list_of_products = $this -> getAllProductsIds();
        for ($i = 0; $i < count($list_of_products); $i++) {
            $allProductsIds[$i] = $list_of_products[$i]['id_product'];
        }

        if ($configValue == 0) // choose product with the biggest quantity
        {
            $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
            $productId = $this->getProductIdFromDb($sql_query);
            $hrefForProduct = $this->createHrefForProduct($productId);
            Configuration::updateValue('LAST_PRODUCT_ID', $productId);
        }
        else if ($configValue == 1) // choose product with biggest quantity each day
        {
            $daysPassed = $this -> getTimeDiff();
            if ($daysPassed >= 1)
            {
                $errors = $errors . 'more than a day passed';
                $this -> setLastSavedDate();
                $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
                $productId = $this->getProductIdFromDb($sql_query);
                $hrefForProduct = $this->createHrefForProduct($productId);
                Configuration::updateValue('LAST_PRODUCT_ID', $productId);

            } else {
                $errors = $errors . 'day not passed';
                $lastProductId = Configuration::get('LAST_PRODUCT_ID');
                $threshold = Configuration::get('THRESHOLD');
                $hrefForProduct = $this->createHrefForProduct($lastProductId);
                $sql_query = 'select quantity from '. _DB_PREFIX_ .'stock_available where id_product= '.  $lastProductId ;
                $quantity = $this->getProductIdFromDb($sql_query);
                if($quantity >= $threshold){
                $hrefForProduct = $this->createHrefForProduct($lastProductId);
                }else if ($quantity < $threshold){
                $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
                                            $productId = $this->getProductIdFromDb($sql_query);
                                            $hrefForProduct = $this->createHrefForProduct($productId);
                                            Configuration::updateValue('LAST_PRODUCT_ID', $productId);
                }

            }
        }
        else if ($configValue == 2) // random product of the day that hasn't been chosen yesterday
        {
            $errors = $errors . 'mode 2';
            $daysPassed = $this -> getTimeDiff();
            if ($daysPassed >= 1)
            {
                $errors = $errors . 'more than a day passed';
                $this -> setLastSavedDate();
                $list_of_products = $this -> getAllProductsIds();
                for ($i = 0; $i < count($list_of_products); $i++) {
                                        $allProductsIds[$i] = $list_of_products[$i]['id_product'];
                                    }

                  shuffle($allProductsIds);
                $randomElement = $allProductsIds[0];
                $lastUsedItem = Configuration::get('LAST_PRODUCT_ID');
                $errors = $errors . ' before shuffeling choosen '. $randomElement ;
                while($randomElement == $lastUsedItem){
                    shuffle($allProductsIds);
                    $errors = $errors . ' shufflin, didnt like '. $randomElement ;
                    $randomElement = $allProductsIds[0];
                    $errors = $errors . ' maybe this one? '. $randomElement ;
                }
                $errors = $errors . ' after shuffeling choosen '. $randomElement ;


                $hrefForProduct = $this->createHrefForProduct($randomElement);
                                $hrefForProduct = $this->createHrefForProduct($randomElement);
                                Configuration::updateValue('LAST_PRODUCT_ID', $randomElement);

//                 $hrefForProduct = $this -> createHrefForProduct($randomElement);
                // $allProductsIds = $this -> getAllProductsIds();
                // if ($lastProductId == null)  // if there was no random product chosen before
                // {
                //     $rndProductId = array_pop(shuffle($allProductsIds));
                //     $lastProductId = $rndProductId;
                // }
                // else
                // {
                //     do {
                //         $rndProductId = array_pop(shuffle($allProductsIds));
                //     }
                //     while ($lastProductId != $rndProductId);

                //     $lastProductId = $rndProductId;
                //}
                // do {
                //             $rndProductId = array_pop(shuffle($allProductsIds));
                //         }
                //         while ($lastProductId != $rndProductId);

                //         $lastProductId = $rndProductId;
//                 $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
//                 $productId = $this->getProductIdFromDb($sql_query);
            } else {
                $errors = $errors . 'day not passed';
                $lastProductId = Configuration::get('LAST_PRODUCT_ID');
                $hrefForProduct = $this->createHrefForProduct($lastProductId);
            }
        }else if ($configValue == 3) // user chooses specific ID
            {
                $productId =  Configuration::get('SPECIAL');

                //check whether such product exits
//                 $list_of_products = $this -> getAllProductsIds();
//                 for ($i = 0; $i < count($list_of_products); $i++) {
//                     $allProductsIds[$i] = $list_of_products[$i]['id_product'];
//                 }
//                 if (!array_search($productId, $allProductsIds, $strict = false)){
//                     $errors = $errors . 'product with id: ' . $productId .' does not exit';
//                 }


                if($this->searchForProduct($productId)){
                     $errors = $errors . ' product id ' . $productId .' does exist ';
                     $hrefForProduct = $this->createHrefForProduct($productId);
                     Configuration::updateValue('LAST_PRODUCT_ID', $productId);
                } else {
                $errors = $errors . ' product id ' . $productId .' does not exist is false ';
                }



                $errors = $errors . ' mode 3, product_id ' . $productId;
            }

        // checking language
        if ($language == 1
        ) {
            $string = $hrefForProduct . '<p style="text-align: center;">Today\'s special offer!  </p>';
        }
        if ($language == 2
        ) {
            $string = $hrefForProduct . '<p style="text-align: center;"> '.$configValue.' Specjalna Oferta'. $errors.'</p>';
        }
        return $string;
    }
}




// w Configure ma byc:
// 1. wybierz id produktu ktory ma byc naszym special offer
// 2. wybierz do jakiej ilosci produktu w stocku bedzie znizka
// 3. wybierz przez ile dni ma dzialac ta znizka
// 4. wybierz czy jak zejdzie ten stock to ma wybierac nowy randomowy produkt ktory ma stock wiekszy niz ten podany 2.
//    (i 3. automatycznie zmienia sie na 0)
// lub wybierz
// 5. % znizki
// (jesli 2. lub 3. sie juz nie spelnia -> sprawdz czy 4. jest ustawione jako wybierz nowy produkt randomowo czy moze jakas inna opcja)



























// if (!defined('_PS_VERSION_')) {
//     exit;
//
// class homepage_special_offer extends Module
// {
//         public function __construct()
//         {
//             $this->name = 'homepage_special_offer';
//             $this->tab = 'front_office_features';
//             $this->version = '0.3.5';
//             $this->author = 's18321';
//             $this->need_instance = 0;
//             $this->ps_versions_compliancy = [
//                 'min' => '1.7',
//                 'max' => _PS_VERSION_
//             ];
//             $this->bootstrap = true;
//
//             parent::__construct();
//
//             $this->displayName = $this->l('PrestaShop special offer module');
//             $this->description = $this->l('Display daily special offer on homepage.');
//
//             $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
//             if (!Configuration::get('MODULE_INTERVAL')) {
//                 $this->warning = $this->l('No name provided');
//             }
//         }
//
//         public function install()
//     {
//         if (Shop::isFeatureActive()) {
//             Shop::setContext(Shop::CONTEXT_ALL);
//         }
//
//         //install plugin, register hook to home page and set default config values
//        return (
//             parent::install()
//             // && $this->registerHook('leftColumn')
//             // && $this->registerHook('header')
//             && $this->registerHook('displayHome')
//             && Configuration::updateValue('MODULE_INTERVAL', 'Special Offer')
//             && Configuration::updateValue('OFFER_MODE', '0')
//             && $this -> setLastSavedDate()
//         );
//     }
//
//     //uninstall plugin along stored configuration
//     public function uninstall()
//     {
//         return (
//             parent::uninstall()
//             && Configuration::deleteByName('MODULE_INTERVAL')
//             && Configuration::deleteByName('OFFER_MODE')
//             && Configuration::deleteByName('LAST_SAVED_DATE')
//         );
//     }
//
//     public function getContent()
//     {
//         $output = '';
//
//         // this part is executed only when the form is submitted
//         if (Tools::isSubmit('submit' . $this->name)) {
//             // retrieve the value set by the user
//             $configValue = (string) Tools::getValue('MODULE_INTERVAL');
//             $configMode = (string) Tools::getValue('OFFER_MODE');
//             // check that the value is valid
//             if (empty($configValue) || !Validate::isGenericName($configValue) ) {
//                 // invalid value, show an error
//                 $output = $this->displayError($this->l('Invalid Configuration value'));
//             } else {
//                 // value is ok, update it and display a confirmation message
//                 Configuration::updateValue('MODULE_INTERVAL', $configValue);
//                  Configuration::updateValue('OFFER_MODE', $configMode);
//                 $output = $this->displayConfirmation($this->l('Settings updated'));
//             }
//         }
//
//         // display any message, then the form
//         return $output . $this->displayForm();
//     }
//
//     public function displayForm()
//     {
//         // Init Fields form array
//         $form = [
//             'form' => [
//                 'legend' => [
//                     'title' => $this->l('Settings'),
//                 ],
//                 'input' => [
//                     [
//                         'type' => 'text',
//                         'label' => $this->l('Set interval'),
//                         'name' => 'MODULE_INTERVAL',
//                         'size' => 20
//                     ],
//                     [
//                         'type' => 'select',
//                             'label' => $this->l('When do you want the special offer to change?'),
//                             'name' => 'OFFER_MODE',
//                             'options' => array(
//                                 'query' => array(
//                                     array('id' => 0, 'name' => $this->l('As soon as stock changes')),
//                                     array('id' => 1, 'name' => $this->l('Every X-day (1 as default)'))
//                                 ),
//                                 'id' => 'id',
//                                 'name' => 'name',
//                             )
//                     ],
//                 ],
//                 'submit' => [
//                     'title' => $this->l('Save'),
//                     'class' => 'btn btn-default pull-right',
//                 ],
//             ],
//         ];
//
//         $helper = new HelperForm();
//
//         // Module, token and currentIndex
//         $helper->table = $this->table;
//         $helper->name_controller = $this->name;
//         $helper->token = Tools::getAdminTokenLite('AdminModules');
//         $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
//         $helper->submit_action = 'submit' . $this->name;
//
//         // Default language
//         $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
//
//         // Load current value into the form
//         $helper->fields_value['MODULE_INTERVAL'] = Tools::getValue('MODULE_INTERVAL', Configuration::get('MODULE_INTERVAL'));
//         $helper->fields_value['OFFER_MODE'] = Tools::getValue('OFFER_MODE', Configuration::get('OFFER_MODE'));
//
//         return $helper->generateForm([$form]);
//     }
//
//     public function getConfigData(){
//
//         $config_data_array = array();
//         $config_data_array['MODULE_INTERVAL'] = Configuration::get('MODULE_INTERVAL');
//         $config_data_array['OFFER_MODE'] = Configuration::get('OFFER_MODE');
//         $config_data_array['LAST_SAVED_DATE'] = Configuration::get('LAST_SAVED_DATE');
//         return $config_data_array;
//     }
//
//
//     private function createHrefForProduct($id_product){
//         $image = Image::getCover($id_product);
//         $product = new Product($id_product, false, Context::getContext()->language->id);
//         $link = new Link;
//         $url = $link->getProductLink($product);
//         $imagePath = $link->getImageLink($product->link_rewrite, $image['id_image'], 'home_default');
//
//         //Writing offer html (return)
//         $string = '<a href=' . $url . '><center><img src="http://'.$imagePath .'" alt="Image of the product" class="img-fluid" loading="lazy" width="250" height="250"></center></a><br></br>';
//         return $string
//     }
//
//     private function getProductIdFromDb(){
//         $db = \Db::getInstance();
//         return $db->getValue($sqlQuery);
//     }
//
//     private function getAllProductsIds(){
//         $db = \Db::getInstance();
//         return $db->getValue('select id_product from '. _DB_PREFIX_ .'stock_available');
//     }
//
//
//     private function getTimeDiff(){
//         $mydate=getdate(date("U"));
//         $now_date_string = (string) $mydate[mday].'-'.$mydate[mon].'-'. $mydate[year];
//         $now_Date=date_create($now_date_string);
//         $lastSavedDate_string = (string) $config_array['LAST_SAVED_DATE'];
//         $lastSavedDate=date_create($lastSavedDate_string);
//         $diff=date_diff($lastSavedDate,$now_Date);
//         return (int) $diff->format("%a");
//     }
//     private function getLastSavedDate(){
//         $lastSavedDate = (string) $config_array['LAST_SAVED_DATE'];
//         $lastSavedDate_date=date_create((string) $lastSavedDate[mday].'-'.$lastSavedDate[mon].'-'. $lastSavedDate[year]);
//         return $lastSavedDate_date;
//     }
//     private function getDateFromString($date_string){
//         $lastSavedDate = (string) $config_array['LAST_SAVED_DATE'];
//         $lastSavedDate_date=date_create((string) $lastSavedDate[mday].'-'.$lastSavedDate[mon].'-'. $lastSavedDate[year]);
//         return $lastSavedDate_date;
//     }
//     private function setLastSavedDate(){
//         $mydate=getdate(date("U"));
//         $date_string = (string) $mydate[mday].'-'.$mydate[mon].'-'. $mydate[year];
//         Configuration::updateValue('LAST_SAVED_DATE', $date_string);
//         return true;
//     }
//
//
//     public function hookDisplayHome($params)
//     {
//         $language = Context::getContext()->language->id;
//         //Trying to access and print a value from config
//         $config_array = $this->getConfigData(); //(string) Configuration::getValue('MODULE_INTERVAL');
//         $configValue = $config_array['OFFER_MODE'];
//         $interval = $config_array['MODULE_INTERVAL'];
//
//         $lastProductId = null;
//
//         $sql_query = '';
//         $hrefForProduct = '';
//         if ($configValue == 0) // product with the biggest quantity
//         {
//             $sql_query = 'select id_product from '. _DB_PREFIX_ .'stock_available where quantity= (select MAX(quantity) from '. _DB_PREFIX_ . 'stock_available)';
//             $productId = $this->getProductIdFromDb($sql_query);
//             $hrefForProduct = $this->createHrefForProduct($productId);
//         } else if ($configValue == 1) // random product of the day that hasn't been chosen yesterday
//         {
//             $daysPassed = $this -> getTimeDiff();
//             if ($daysPassed >= 1)
//             {
//                 $allProductsIds = $this -> getAllProductsIds();
//                 if ($lastProductId == null)  // if there was no random product chosen before
//                 {
//                     $rndProductId = array_pop(shuffle($allProductsIds));
//                     $lastProductId = $rndProductId;
//                 }
//                 else
//                 {
//                     do {
//                         $rndProductId = array_pop(shuffle($allProductsIds));
//                     }
//                     while ($lastProductId != $rndProductId);
//
//                     $lastProductId = $rndProductId;
//                 }
//
//                 $hrefForProduct = $this->createHrefForProduct($lastProductId);
//             }
//         }
//
//
//         // checking language
//         if ($language == 1
//         ) {
//             $string = $string . '<p style="text-align: center;">Today\'s special offer! '. $configValue .'</p>';
//         }
//         if ($language == 2
//         ) {
//             $string = $string . '<p style="text-align: center;"> Specjalna Oferta</p>';
//         }
//         return $string;
//     }
// }


































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


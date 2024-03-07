<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class bnsaveexporter extends Module
{
    const CATALOG_LIST = [
        100 => 'Neimportuoti',
        1 => 'Augintiniams',
        2 => 'Drabužiai ir Avalynė',
        3 => 'Elektronika ir Technika',
        4 => 'Grožis ir Aksesuarai',
        5 => 'Maistas ir Gėrimai',
        6 => 'Namai ir Buitis',
        7 => 'Pramogos ir Kelionės',
        8 => 'Vaikams',
        // 9 => 'Nuolaidų kodai',
        10 => 'Kita',
        11 => 'Sportas ir Sveikata',
        12 => 'Suaugusiems (18+)',
        // 13 => 'Paslaugos',
        14 => 'Sodo prekės',
    ];
    const DONT_IMPORT_CATALOG_ID = 100;
    const JSON_DATE_FORMAT = 'Y-m-d';
    const JSON_TIME_FORMAT = 'H:i:s';
    const JSON_DATE_TIME_FORMAT = self::JSON_DATE_FORMAT . ' ' . self::JSON_TIME_FORMAT;
    const START_OF_THE_DAY = '00:00:00';
    const CRONJOB_TIME = '03:00:00';
    const EXPORT_DIRECTORY = _PS_IMG_DIR_ . 'upload/bnsave-exports';
    const EXPORT_FILE = 'discounts.json';
    const DECIMALS = 2;

    public static $languageId = null;
    public static $categories = null;

    public function __construct()
    {
        $this->name = 'bnsaveexporter';
        $this->tab = 'migration_tools';
        $this->version = '1.0.0';
        $this->author = 'Bnsave (www.bnsave.lt)';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.6.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bnsave Exporter');
        $this->description = $this->l('Exports products with discounts from Woocommerce to Bnsave.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('BNSAVEEXPORTER_SHOP_NAME')) {
            $this->warning = $this->l('Nenurodytas parduotuvės pavadinimas.');
        }

        if (!Configuration::get('BNSAVEEXPORTER_USE_LANGUAGE_ISO')) {
            $this->warning = $this->l('Nenurodytas kalbos ISO kodas.');
        }

        if (!Configuration::get('BNSAVEEXPORTER_EXCLUDE_TAGS')) {
            $this->warning = $this->l('Nenurodyti tagai kurių nereikia siųsti į Bnsave.');
        }

        if (!Configuration::get('BNSAVEEXPORTER_CATEGORY_MAPPING')) {
            $this->warning = $this->l('Nenurodytas kategorijų susiejimas.');
        }
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() && $this->createAssets() && $this->configure();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall() && $this->removeAssets();
    }

    /**
     * @return bool
     */
    private function createAssets()
    {
        if (!file_exists(self::EXPORT_DIRECTORY) && !mkdir(self::EXPORT_DIRECTORY, 0755, true) && !is_dir(self::EXPORT_DIRECTORY)) {
            return false;
        }

        try {
            file_put_contents($this->getExportFilePath(), json_encode([]));
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function removeAssets()
    {
        try {
            unlink($this->getExportFilePath());
        } catch (Exception $exception) {
            // Do nothing, file doesn't exist already
        }

        return true;
    }

    /**
     * @return string
     */
    private function getExportFilePath()
    {
        return self::EXPORT_DIRECTORY . '/' . self::EXPORT_FILE;
    }

    /**
     * @return bool
     */
    private function configure()
    {
        if (!Configuration::hasKey('BNSAVEEXPORTER_SHOP_NAME')) {
            Configuration::updateValue('BNSAVEEXPORTER_SHOP_NAME', 'My Shop');
        }

        if (!Configuration::hasKey('BNSAVEEXPORTER_CATEGORY_MAPPING')) {
            Configuration::updateValue('BNSAVEEXPORTER_CATEGORY_MAPPING', json_encode([]));
        }

        if (!Configuration::hasKey('BNSAVEEXPORTER_EXCLUDE_TAGS')) {
            Configuration::updateValue('BNSAVEEXPORTER_EXCLUDE_TAGS', 'pagrindinis,home');
        }

        if (!Configuration::hasKey('BNSAVEEXPORTER_USE_LANGUAGE_ISO')) {
            Configuration::updateValue('BNSAVEEXPORTER_USE_LANGUAGE_ISO', 'lt');
        }

        return true;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitForm1')) {
            Configuration::updateValue('BNSAVEEXPORTER_SHOP_NAME', (string) Tools::getValue('BNSAVEEXPORTER_SHOP_NAME'));
            Configuration::updateValue('BNSAVEEXPORTER_USE_LANGUAGE_ISO', (string) Tools::getValue('BNSAVEEXPORTER_USE_LANGUAGE_ISO'));
            Configuration::updateValue('BNSAVEEXPORTER_EXCLUDE_TAGS', (string) Tools::getValue('BNSAVEEXPORTER_EXCLUDE_TAGS'));

            $output = $this->displayConfirmation($this->l('Konfiguracija atnaujinta'));
        }

        if (Tools::isSubmit('submitForm2')) {
            Configuration::updateValue('BNSAVEEXPORTER_CATEGORY_MAPPING', json_encode(Tools::getValue('BNSAVEEXPORTER_CATEGORY_MAPPING')));

            $output = $this->displayConfirmation($this->l('Kategorijų susiejimas atnaujintas'));
        }

        return $output . $this->renderForm1() . $this->renderForm2();
    }

    /**
     * @return string
     */
    public function renderForm1()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Nustatymai'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Parduotuvės pavadinimas, kuris bus matomas Bnsave'),
                        'name' => 'BNSAVEEXPORTER_SHOP_NAME',
                        'size' => 150,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Kalbos ISO kodas iš kurios traukti informaciją, pvz.: lt arba en'),
                        'name' => 'BNSAVEEXPORTER_USE_LANGUAGE_ISO',
                        'size' => 8,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Tagai per kablelį kurių nenorite siųsti į Bnsave (nedėkite tarpo po kablelio, siūlome įtraukti: pagrindinis,home)'),
                        'name' => 'BNSAVEEXPORTER_EXCLUDE_TAGS',
                        'size' => 1000,
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Išsaugoti'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submitForm1';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value['BNSAVEEXPORTER_SHOP_NAME'] = Configuration::get('BNSAVEEXPORTER_SHOP_NAME');
        $helper->fields_value['BNSAVEEXPORTER_USE_LANGUAGE_ISO'] = Configuration::get('BNSAVEEXPORTER_USE_LANGUAGE_ISO');
        $helper->fields_value['BNSAVEEXPORTER_EXCLUDE_TAGS'] = Configuration::get('BNSAVEEXPORTER_EXCLUDE_TAGS');

        return $helper->generateForm([$form]);
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function renderForm2()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Kategorijų susiejimas'),
                ],
                'input' => [],
                'submit' => [
                    'title' => $this->l('Išsaugoti'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $categories = self::getCategories();
        $catalogOptions = $this->getCatalogOptions();
        foreach ($categories as $id => $category) {
            $form['form']['input'][] = [
                'type' => 'select',
                'label' => 'Kategorija: <strong>' . $category . '</strong>',
                'name' => 'BNSAVEEXPORTER_CATEGORY_MAPPING[' . $id . ']',
                'required' => false,
                'options' => [
                    'query' => $catalogOptions,
                    'id' => 'id',
                    'name' => 'name'
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submitForm2';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $catalogMappingConfiguration = json_decode(Configuration::get('BNSAVEEXPORTER_CATEGORY_MAPPING'), true);
        foreach ($catalogMappingConfiguration as $categoryId => $catalogId) {
            $helper->fields_value['BNSAVEEXPORTER_CATEGORY_MAPPING[' . $categoryId . ']'] = $catalogId;
        }

        return $helper->generateForm([$form]);
    }

    /**
     * @return int
     * @throws PrestaShopDatabaseException
     */
    public static function getLanguageId()
    {
        if (self::$languageId) {
            return (int) self::$languageId;
        }

        $tablePrefix = _DB_PREFIX_;
        $isoCode = Configuration::get('BNSAVEEXPORTER_USE_LANGUAGE_ISO');

        $sql = <<<SQL
            SELECT id_lang as id
            FROM  {$tablePrefix}lang
            WHERE iso_code = "{$isoCode}"
            ORDER BY id DESC LIMIT 1
SQL;

        $results = Db::getInstance()->executeS($sql);
        $languageId = (int) $results[0]['id'];
        self::$languageId = $languageId;

        return $languageId;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getCategories()
    {
        if (self::$categories) {
            return self::$categories;
        }

        $tablePrefix = _DB_PREFIX_;
        $languageId = self::getLanguageId();

        $sql = <<<SQL
            SELECT id_category as id, name
            FROM  {$tablePrefix}category_lang
            WHERE id_lang = "{$languageId}"
            ORDER BY id_category ASC
SQL;

        $results = Db::getInstance()->executeS($sql);

        if ($results === []) {
            return [];
        }

        $categories = [];
        foreach ($results as $category) {
            $categories[$category['id']] = $category['name'];
        }

        self::$categories = $categories;

        return $categories;
    }

    /**
     * @return array
     */
    private function getCatalogOptions()
    {
        $options = [];
        foreach (self::CATALOG_LIST as $id => $name) {
            $options[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $options;
    }
}

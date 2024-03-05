<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class bnsaveexporter extends Module
{
    const CATALOG_LIST = [
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
    const OTHER_CATALOG_ID = 10;
    const JSON_DATE_FORMAT = 'Y-m-d';
    const JSON_TIME_FORMAT = 'H:i:s';
    const JSON_DATE_TIME_FORMAT = self::JSON_DATE_FORMAT . ' ' . self::JSON_TIME_FORMAT;
    const START_OF_THE_DAY = '00:00:00';
    const CRONJOB_TIME = '03:00:00';
    const EXPORT_DIRECTORY = _PS_IMG_DIR_ . 'upload/bnsave-exports';
    const EXPORT_FILE = 'discounts.json';
    const DECIMALS = 2;

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

        $this->displayName = $this->trans('Bnsave Exporter', [], 'Modules.Bnsaveexporter.Admin');
        $this->description = $this->trans('Exports products with discounts from Woocommerce to Bnsave.', [], 'Modules.Bnsaveexporter.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Bnsaveexporter.Admin');

        if (!Configuration::get('BNSAVEEXPORTER_NAME')) {
            $this->warning = $this->trans('No name provided', [], 'Modules.Mymodule.Admin');
        }
    }

    public function install()
    {
        return parent::install() && $this->createAssets() && $this->configure();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->removeAssets();
    }

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

    private function removeAssets()
    {
        try {
            unlink($this->getExportFilePath());
        } catch (Exception $exception) {
            // Do nothing, file doesn't exist already
        }

        return true;
    }

    private function getExportFilePath()
    {
        return self::EXPORT_DIRECTORY . '/' . self::EXPORT_FILE;
    }

    private function configure()
    {
        Configuration::updateValue('BNSAVEEXPORTER_SHOP_NAME', 'My Shop');
        Configuration::updateValue('BNSAVEEXPORTER_CATEGORY_MAPPING', []);
        Configuration::updateValue('BNSAVEEXPORTER_EXCLUDE_TAGS', '');
        Configuration::updateValue('BNSAVEEXPORTER_USE_LANGUAGE_ISO', 'lt');

        return true;
    }
}

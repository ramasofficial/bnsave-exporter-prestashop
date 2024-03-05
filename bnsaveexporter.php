<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class bnsaveexporter extends Module
{
    const EXPORT_DIRECTORY = _PS_IMG_DIR_ . 'upload/bnsave-exports';
    const EXPORT_FILE = 'discounts.json';

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

        return true;
    }
}

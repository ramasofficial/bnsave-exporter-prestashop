<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BnsaveExporter extends Module
{
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
        return parent::install() && $this->createDir();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->removeDir();
    }

    private function createDir()
    {
        $export_directory = _PS_IMG_DIR_ . 'upload/bnsave-exports';

        if (!mkdir($export_directory, 0755, true) && !is_dir($export_directory)) {
            return false;
        }

        return true;
    }

    private function removeDir()
    {
        $export_directory = _PS_IMG_DIR_ . 'upload/bnsave-exports';

        return unlink($export_directory);
    }
}

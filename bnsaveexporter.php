<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BnsaveExporter extends Module
{
    public function __construct()
    {
        $this->name = 'bnsaveexporter';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Bnsave';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.6.0.0',
            'max' => '8.99.99',
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
}

<?php

class BnsaveExporterValidationModuleFrontController  extends ModuleFrontController
{
    public $auth = false;
    public $ajax;

    public function initContent()
    {
        $this->ajax = true;

        $action = Tools::getValue('action');

        if ($action === 'json') {
            $this->printJson();
            exit;
        }

        if ($action === 'cron') {
            $this->cron();
            exit;
        }

        $this->ajaxRender('No route found.');
    }

    private function printJson()
    {
        $contents = file_get_contents(bnsaveexporter::EXPORT_DIRECTORY . '/' . bnsaveexporter::EXPORT_FILE);

        header('Content-Type: application/json');

        echo $contents;
    }

    private function cron()
    {
        // TODO
        echo 'cron';
    }
}

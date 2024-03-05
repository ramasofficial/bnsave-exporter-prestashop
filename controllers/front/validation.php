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
        $tablePrefix = _DB_PREFIX_;

        $sql = <<<SQL
            SELECT p.id_product, pl.name, pl.description_short, sa.quantity
            FROM  {$tablePrefix}product p
            JOIN {$tablePrefix}specific_price sp ON p.id_product = sp.id_product
            JOIN {$tablePrefix}stock_available sa ON p.id_product = sa.id_product
            JOIN {$tablePrefix}product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 2
            WHERE p.active = 1
            AND sp.reduction > 0
            AND sa.quantity > 0
SQL;

        $results = Db::getInstance()->executeS($sql);

        $this->handleResults($results);

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    private function handleResults($results)
    {
        $products = [];
        foreach ($results as $product) {
            $products[] = [
                'name' => $product['name'],
                'description' => strip_tags($product['description_short'], '<br>'),
                'shop_name' => Configuration::get('BNSAVEEXPORTER_SHOP_NAME'),
                'catalog_id' => '',
                'price' => '',
                'old_price' => '',
                'discount' => '',
                'valid_from' => '',
                'valid_until' => '',
                'image' => '',
                'shop_link' => '',
                'promo_code' => '',
                'tags' => [],
                'city_id' => [],
                'external_id' => $product['id_product'],
                'quantity' => $product['quantity'],
            ];
        }

        file_put_contents(bnsaveexporter::EXPORT_DIRECTORY . '/' . bnsaveexporter::EXPORT_FILE, json_encode($products));
    }
}

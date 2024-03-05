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

        // TODO: deal with language = 2
        $sql = <<<SQL
            SELECT sp.id_specific_price as id, sp.id_product as product_id, sp.id_product_attribute as product_attribute_id, sp.from, sp.to, pl.name, pl.description_short as description, sa.quantity
            FROM  {$tablePrefix}specific_price sp
            JOIN {$tablePrefix}product p ON sp.id_product = p.id_product
            JOIN {$tablePrefix}stock_available sa ON sp.id_product = sa.id_product AND sp.id_product_attribute = sa.id_product_attribute
            JOIN {$tablePrefix}product_lang pl ON sp.id_product = pl.id_product AND pl.id_lang = 2
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
            $description = trim(strip_tags($product['description'], '<br>'));
            $productObj = new Product((int) $product['product_id'], true, 2);

            $validFrom = $this->hasDate($product['from']) ?
                (new DateTime($product['from']))->format(bnsaveexporter::JSON_DATE_TIME_FORMAT) :
                (new DateTime())->format(bnsaveexporter::JSON_DATE_FORMAT) . ' ' . bnsaveexporter::START_OF_THE_DAY;

            $validUntil = $this->hasDate($product['to']) ?
                (new DateTime($product['to']))->format(bnsaveexporter::JSON_DATE_TIME_FORMAT) :
                (new DateTime())->add(new DateInterval('P1D'))->format(bnsaveexporter::JSON_DATE_FORMAT) . ' ' . bnsaveexporter::CRONJOB_TIME;

            $link = new Link();
            $cover = Product::getCover((int) $product['product_id']);
            $image = $link->getImageLink($productObj->link_rewrite ?? $productObj->name, (int) $cover['id_image'], 'large_default');
            $price = $productObj->getPrice(true, $product['product_attribute_id'], 2);
            $oldPrice = $productObj->getPriceWithoutReduct(false, $product['product_attribute_id'], 2);

            if ($price >= $oldPrice) {
                continue;
            }

            $products[] = [
                'name' => $product['name'],
                'description' => $description !== null && $description !== '' ? $description : null,
                'shop_name' => Configuration::get('BNSAVEEXPORTER_SHOP_NAME'),
                'catalog_id' => $productObj->getDefaultCategory(),
                'price' => $price,
                'old_price' => $oldPrice,
                'discount' => null,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'image' => $image,
                'shop_link' => $link->getProductLink($productObj), // TODO: take has to rewrite
                'promo_code' => null,
                'tags' => [],
                'city_id' => null,
                'external_id' => (int) $product['id'],
                'quantity' => (int) $product['quantity'],
            ];
        }

        $this->writeToFile($products);
    }

    private function writeToFile(array $products)
    {
        file_put_contents(bnsaveexporter::EXPORT_DIRECTORY . '/' . bnsaveexporter::EXPORT_FILE, json_encode($products));
    }

    private function hasDate(string $date)
    {
        return $date !== '0000-00-00 00:00:00';
    }
}

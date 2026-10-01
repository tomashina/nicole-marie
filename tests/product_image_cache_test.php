<?php
define('DB_PREFIX', 'oc_');
require_once dirname(__DIR__) . '/system/engine/registry.php';
require_once dirname(__DIR__) . '/system/engine/model.php';
require_once dirname(__DIR__) . '/catalog/model/catalog/product.php';

class ProductImageCacheTestDb {
	public $queries = array();
	public $images = array(
		42 => array(array('product_image_id' => 1, 'image' => 'catalog/first.jpg', 'sort_order' => 0)),
		43 => array(array('product_image_id' => 2, 'image' => 'catalog/second.jpg', 'sort_order' => 0))
	);

	public function query($sql) {
		$this->queries[] = $sql;
		if (!preg_match("/product_id = '([0-9]+)' ORDER BY sort_order ASC$/", $sql, $matches)) {
			throw new RuntimeException('Unexpected image query.');
		}
		$result = new stdClass();
		$result->rows = isset($this->images[(int)$matches[1]]) ? $this->images[(int)$matches[1]] : array();
		return $result;
	}
}

function assertProductImages($condition, $message) {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$database = new ProductImageCacheTestDb();
$registry = new Registry();
$registry->set('db', $database);
$model = new ModelCatalogProduct($registry);

$first = $model->getProductImages(42);
assertProductImages($first === $database->images[42], 'Image rows changed.');
assertProductImages($model->getProductImages('42') === $first && count($database->queries) === 1, 'Repeated product images were queried again.');
assertProductImages($model->getProductImages(43) === $database->images[43] && count($database->queries) === 2, 'Different products shared cached images.');
assertProductImages($model->getProductImages(44) === array() && $model->getProductImages(44) === array() && count($database->queries) === 3, 'Empty image results were not cached.');

$first[0]['image'] = 'changed.jpg';
assertProductImages($model->getProductImages(42) === $database->images[42], 'Caller changes modified cached image rows.');

$next_request = new ModelCatalogProduct($registry);
assertProductImages($next_request->getProductImages(42) === $database->images[42] && count($database->queries) === 4, 'Image cache escaped the model request lifetime.');
echo "product_image_cache_test: OK\n";

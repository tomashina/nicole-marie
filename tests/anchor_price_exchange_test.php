<?php
require_once dirname(__DIR__) . '/system/library/anchor_price_exchange.php';

function assertTrue($condition, $message) {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$editable = tempnam(sys_get_temp_dir(), 'anchor-editable-');
$handle = fopen($editable, 'wb');
$trailing_backslash_text = "Torba; završava\\";
AnchorPriceExchange::writeEditableCsv($handle, array(array(
	'product_id' => 42,
	'model' => '=MODEL',
	'sku' => "'+SKU-42",
	'product_name' => $trailing_backslash_text,
	'price' => '80.0000',
	'gross_price' => '100.0000',
	'currency_code' => '@EUR',
	'reference_date' => '=2026-09-10',
	'verification_status' => '-confirmed'
)));
fclose($handle);

$rows = AnchorPriceExchange::readEditableCsv($editable);
assertTrue(count($rows) === 1, 'Editable CSV row count is incorrect.');
assertTrue($rows[0]['product_id'] === '42', 'Product ID did not round-trip.');
assertTrue($rows[0]['gross_price'] === '100.0000', 'Gross price did not round-trip.');
assertTrue($rows[0]['model'] === "'=MODEL", 'Spreadsheet formula protection is missing.');
assertTrue($rows[0]['sku'] === "''+SKU-42", 'An existing leading apostrophe was not reversibly protected.');
assertTrue($rows[0]['product_name'] === $trailing_backslash_text, 'A quoted value ending in a backslash was corrupted.');
assertTrue($rows[0]['currency_code'] === "'@EUR", 'Currency formula protection is missing.');
assertTrue($rows[0]['reference_date'] === "'=2026-09-10", 'Date formula protection is missing.');
assertTrue($rows[0]['verification_status'] === "'-confirmed", 'Status formula protection is missing.');
assertTrue(AnchorPriceExchange::spreadsheetOriginalText($rows[0]['model']) === '=MODEL', 'Formula protection was not reversible.');
assertTrue(AnchorPriceExchange::spreadsheetOriginalText($rows[0]['sku']) === "'+SKU-42", 'Original apostrophe was not restored.');
assertTrue(AnchorPriceExchange::spreadsheetOriginalText(AnchorPriceExchange::spreadsheetSafeText("  =SUM(A1:A2)")) === "  =SUM(A1:A2)", 'Leading-space formula protection was not reversible.');
assertTrue(AnchorPriceExchange::spreadsheetOriginalText(AnchorPriceExchange::spreadsheetSafeText("\tformula")) === "\tformula", 'Leading-tab formula protection was not reversible.');
@unlink($editable);

$publication = tempnam(sys_get_temp_dir(), 'anchor-publication-');
$handle = fopen($publication, 'wb');
fwrite($handle, "\xEF\xBB\xBF");
$header = array('Prodajni kanal', 'ID proizvoda', 'Naziv proizvoda', 'Šifra/model', 'SKU', 'Marka/proizvođač', 'Jedinica mjere', 'Cijena po jedinici (EUR)', 'Redovna maloprodajna cijena (EUR)', 'Aktualna maloprodajna cijena (EUR)', 'Poseban oblik prodaje', 'Naziv posebnog oblika prodaje', 'Aktualna akcijska cijena (EUR)', 'Sidrena cijena (EUR)', 'Datum sidrene cijene', 'Barkod', 'Dostupnost', 'Količina', 'Status zalihe', 'Valuta');
$original_name = '=HYPERLINK("https://example.invalid","A & B < haljina")';
$original_model = "Model; završava\\";
$original_manufacturer = "'@Brand";
$original_unit = "\tkom";
$original_stock_status = '+Na zalihi';
fputcsv($handle, $header, ';', '"', '');
fputcsv($handle, array(
	'Web trgovina',
	'42',
	AnchorPriceExchange::spreadsheetSafeText($original_name),
	AnchorPriceExchange::spreadsheetSafeText($original_model),
	AnchorPriceExchange::spreadsheetSafeText('SKU-42'),
	AnchorPriceExchange::spreadsheetSafeText($original_manufacturer),
	AnchorPriceExchange::spreadsheetSafeText($original_unit),
	'90,00',
	'100,00',
	'90,00',
	'DA',
	'Akcija',
	'90,00',
	'95,00',
	'2026-09-10',
	'3850000000000',
	'Dostupno',
	'2',
	AnchorPriceExchange::spreadsheetSafeText($original_stock_status),
	'EUR'
), ';', '"', '');
fclose($handle);

$xml = AnchorPriceExchange::publicationCsvToXml($publication, array(
	'publication_id' => 7,
	'published_at' => '2026-10-01 07:30:00',
	'source_filename' => 'cjenik_web.csv',
	'source_sha256' => hash_file('sha256', $publication),
	'product_count' => 1
));
$document = simplexml_load_string($xml);
assertTrue($document !== false, 'Generated XML is invalid.');
assertTrue((string)$document->products->product->product_id === '42', 'XML product ID is incorrect.');
assertTrue((string)$document->products->product->name === $original_name, 'XML did not restore the formula-protected product name.');
assertTrue((string)$document->products->product->model === $original_model, 'XML parsing corrupted a quoted model ending in a backslash.');
assertTrue((string)$document->products->product->manufacturer === $original_manufacturer, 'XML did not preserve an original apostrophe.');
assertTrue((string)$document->products->product->unit === $original_unit, 'XML did not restore a protected leading tab.');
assertTrue((string)$document->products->product->stock_status === $original_stock_status, 'XML did not restore a formula-protected stock status.');
assertTrue((string)$document->products->product->anchor_price === '95,00', 'XML anchor price is incorrect.');
@unlink($publication);

echo "anchor_price_exchange_test: OK\n";

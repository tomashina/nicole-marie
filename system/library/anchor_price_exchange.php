<?php
/**
 * CSV import/export and XML rendering helpers for the anchor-price module.
 *
 * The class deliberately has no OpenCart dependencies so the exchange format
 * can be validated from the command line as well as from admin/catalog code.
 */
class AnchorPriceExchange {
	const DEFAULT_MAX_ROWS = 10000;

	public static function editableHeaders() {
		return array(
			'product_id',
			'model',
			'sku',
			'product_name',
			'price',
			'gross_price',
			'currency_code',
			'reference_date',
			'verification_status'
		);
	}

	public static function writeEditableCsv($handle, array $rows) {
		if (!is_resource($handle)) {
			throw new InvalidArgumentException('CSV output handle is not valid.');
		}

		if (fwrite($handle, "\xEF\xBB\xBF") === false) {
			throw new RuntimeException('Unable to write the CSV byte-order mark.');
		}

		if (!self::writeCsvRow($handle, self::editableHeaders(), ';')) {
			throw new RuntimeException('Unable to write the CSV header.');
		}

		foreach ($rows as $row) {
			$values = array();

			foreach (self::editableHeaders() as $header) {
				$value = isset($row[$header]) ? (string)$row[$header] : '';

				if (in_array($header, array('model', 'sku', 'product_name', 'currency_code', 'reference_date', 'verification_status'), true)) {
					$value = self::spreadsheetSafeText($value);
				}

				$values[] = $value;
			}

			if (!self::writeCsvRow($handle, $values, ';')) {
				throw new RuntimeException('Unable to write a CSV data row.');
			}
		}
	}

	public static function readEditableCsv($path, $max_rows = self::DEFAULT_MAX_ROWS) {
		if (!is_string($path) || !is_file($path) || !is_readable($path)) {
			throw new InvalidArgumentException('CSV file is not readable.');
		}

		$max_rows = max(1, (int)$max_rows);
		$handle = @fopen($path, 'rb');

		if (!$handle) {
			throw new RuntimeException('Unable to open the CSV file.');
		}

		try {
			$first_line = fgets($handle);

			if ($first_line === false) {
				throw new RuntimeException('CSV file is empty.');
			}

			$delimiter = self::detectDelimiter($first_line);
			rewind($handle);
			$headers = self::readCsvRow($handle, $delimiter);

			if (!is_array($headers) || !$headers) {
				throw new RuntimeException('CSV header is missing.');
			}

			$headers[0] = self::stripBom($headers[0]);
			$column_map = self::editableColumnMap($headers);
			$required = array('product_id', 'price', 'gross_price', 'reference_date');

			foreach ($required as $field) {
				if (!isset($column_map[$field])) {
					throw new RuntimeException('CSV column is required: ' . $field . '.');
				}
			}

			$rows = array();
			$line_number = 1;

			while (($values = self::readCsvRow($handle, $delimiter)) !== false) {
				$line_number++;

				if (self::rowIsEmpty($values)) {
					continue;
				}

				if (count($rows) >= $max_rows) {
					throw new RuntimeException('CSV file exceeds the maximum of ' . $max_rows . ' data rows.');
				}

				$row = array('_line' => $line_number);

				foreach (self::editableHeaders() as $field) {
					$index = isset($column_map[$field]) ? $column_map[$field] : null;
					$row[$field] = $index !== null && array_key_exists($index, $values)
						? trim(self::toUtf8($values[$index]))
						: '';
				}

				$rows[] = $row;
			}

			if (!$rows) {
				throw new RuntimeException('CSV file contains no data rows.');
			}

			return $rows;
		} finally {
			fclose($handle);
		}
	}

	public static function publicationCsvToXml($path, array $metadata = array()) {
		if (!class_exists('XMLWriter')) {
			throw new RuntimeException('The XMLWriter PHP extension is required.');
		}

		if (!is_string($path) || !is_file($path) || !is_readable($path)) {
			throw new InvalidArgumentException('Published CSV file is not readable.');
		}

		$handle = @fopen($path, 'rb');

		if (!$handle) {
			throw new RuntimeException('Unable to open the published CSV file.');
		}

		try {
			$first_line = fgets($handle);

			if ($first_line === false) {
				throw new RuntimeException('Published CSV file is empty.');
			}

			$delimiter = self::detectDelimiter($first_line);
			rewind($handle);
			$headers = self::readCsvRow($handle, $delimiter);

			if (!is_array($headers)) {
				throw new RuntimeException('Published CSV header is missing.');
			}

			$headers[0] = self::stripBom($headers[0]);
			$field_names = array(
				'sales_channel',
				'product_id',
				'name',
				'model',
				'sku',
				'manufacturer',
				'unit',
				'unit_price',
				'regular_price',
				'current_price',
				'special_offer',
				'special_offer_name',
				'special_price',
				'anchor_price',
				'anchor_date',
				'barcode',
				'availability',
				'quantity',
				'stock_status',
				'currency'
			);

			if (count($headers) !== count($field_names)) {
				throw new RuntimeException('Published CSV has an unexpected column count.');
			}

			$writer = new XMLWriter();
			$writer->openMemory();
			$writer->setIndent(true);
			$writer->setIndentString('  ');
			$writer->startDocument('1.0', 'UTF-8');
			$writer->startElement('digital_price_list');
			$writer->writeAttribute('version', '1.0');
			$writer->startElement('metadata');

			foreach (array('publication_id', 'published_at', 'source_filename', 'source_sha256') as $field) {
				$writer->writeElement($field, self::xmlText(isset($metadata[$field]) ? $metadata[$field] : ''));
			}

			$expected_count = isset($metadata['product_count']) ? (int)$metadata['product_count'] : 0;
			$writer->writeElement('product_count', (string)$expected_count);
			$writer->endElement();
			$writer->startElement('products');
			$count = 0;

			while (($values = self::readCsvRow($handle, $delimiter)) !== false) {
				if (self::rowIsEmpty($values)) {
					continue;
				}

				if (count($values) !== count($field_names)) {
					throw new RuntimeException('Published CSV contains a malformed data row.');
				}

				$writer->startElement('product');

				foreach ($field_names as $index => $field_name) {
					// Published CSV text is prefixed with an apostrophe when it could
					// be evaluated as a spreadsheet formula. XML has no such risk, so
					// return the original value rather than leaking the CSV safeguard.
					$value = self::spreadsheetOriginalText($values[$index]);
					$writer->writeElement($field_name, self::xmlText($value));
				}

				$writer->endElement();
				$count++;
			}

			$writer->endElement();
			$writer->endElement();
			$writer->endDocument();

			if ($expected_count > 0 && $count !== $expected_count) {
				throw new RuntimeException('Published CSV product count does not match its publication record.');
			}

			return $writer->outputMemory();
		} finally {
			fclose($handle);
		}
	}

	private static function editableColumnMap(array $headers) {
		$aliases = array(
			'product_id' => array('product_id', 'id_proizvoda', 'id_artikla'),
			'model' => array('model', 'sifra_model', 'sifra'),
			'sku' => array('sku'),
			'product_name' => array('product_name', 'naziv_proizvoda', 'naziv_artikla', 'naziv'),
			'price' => array('price', 'net_price', 'neto_cijena', 'neto_sidrena_cijena'),
			'gross_price' => array('gross_price', 'bruto_price', 'bruto_cijena', 'bruto_sidrena_cijena', 'sidrena_cijena'),
			'currency_code' => array('currency_code', 'currency', 'valuta'),
			'reference_date' => array('reference_date', 'referentni_datum', 'datum_sidrene_cijene'),
			'verification_status' => array('verification_status', 'status', 'status_provjere')
		);
		$normalised = array();

		foreach ($headers as $index => $header) {
			$key = self::normaliseHeader($header);

			if ($key !== '' && !isset($normalised[$key])) {
				$normalised[$key] = $index;
			}
		}

		$map = array();

		foreach ($aliases as $field => $field_aliases) {
			foreach ($field_aliases as $alias) {
				if (isset($normalised[$alias])) {
					$map[$field] = $normalised[$alias];
					break;
				}
			}
		}

		return $map;
	}

	private static function normaliseHeader($value) {
		$value = self::stripBom(self::toUtf8($value));
		$value = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');

		if (function_exists('iconv')) {
			$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

			if ($ascii !== false) {
				$value = $ascii;
			}
		}

		$value = strtolower($value);
		$value = preg_replace('/[^a-z0-9]+/', '_', $value);

		return trim($value, '_');
	}

	private static function detectDelimiter($line) {
		$best_delimiter = ';';
		$best_count = 0;

		foreach (array(';', ',', "\t") as $delimiter) {
			// An empty escape character makes parsing RFC 4180 compatible. A
			// backslash escape corrupts quoted values that end in a backslash.
			$values = str_getcsv($line, $delimiter, '"', '');
			$count = is_array($values) ? count($values) : 0;

			if ($count > $best_count) {
				$best_count = $count;
				$best_delimiter = $delimiter;
			}
		}

		return $best_delimiter;
	}

	private static function readCsvRow($handle, $delimiter) {
		return fgetcsv($handle, 0, $delimiter, '"', '');
	}

	private static function writeCsvRow($handle, array $row, $delimiter) {
		return fputcsv($handle, $row, $delimiter, '"', '') !== false;
	}

	private static function rowIsEmpty(array $row) {
		foreach ($row as $value) {
			if (trim((string)$value) !== '') {
				return false;
			}
		}

		return true;
	}

	private static function stripBom($value) {
		$value = (string)$value;

		return strncmp($value, "\xEF\xBB\xBF", 3) === 0 ? substr($value, 3) : $value;
	}

	private static function toUtf8($value) {
		$value = (string)$value;

		if ($value === '' || preg_match('//u', $value)) {
			return $value;
		}

		if (function_exists('iconv')) {
			$converted = @iconv('Windows-1250', 'UTF-8//IGNORE', $value);

			if ($converted !== false) {
				return $converted;
			}
		}

		return $value;
	}

	public static function spreadsheetSafeText($value) {
		$value = self::toUtf8($value);

		if (self::isSpreadsheetFormulaRisk($value)) {
			$value = "'" . $value;
		}

		return $value;
	}

	/**
	 * Remove exactly the apostrophe added by spreadsheetSafeText().
	 *
	 * Existing leading apostrophes are preserved because export adds one more
	 * whenever the text after them is formula-like.
	 */
	public static function spreadsheetOriginalText($value) {
		$value = self::toUtf8($value);

		if (isset($value[0]) && $value[0] === "'" && self::isSpreadsheetFormulaRisk(substr($value, 1))) {
			return substr($value, 1);
		}

		return $value;
	}

	private static function isSpreadsheetFormulaRisk($value) {
		if ($value === '') {
			return false;
		}

		// Excel-compatible applications may evaluate =, +, -, @, tab or CR
		// even after leading apostrophes/whitespace. Include LF for CSV values
		// assembled from imported or multi-line catalogue text.
		return preg_match("/^'*(?:[\\t\\r]|[\\p{Z}\\t\\r\\n]*[=+\\-@])/u", $value) === 1;
	}

	private static function xmlText($value) {
		$value = self::toUtf8($value);

		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
	}
}

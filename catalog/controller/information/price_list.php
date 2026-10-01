<?php
class ControllerInformationPriceList extends Controller {
	public function index() {
		if (!$this->isEnabled()) {
			return $this->notFound();
		}

		$this->load->language('information/price_list');
		$this->load->model('extension/module/anchor_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_intro'] = $this->language->get('text_intro');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['column_location'] = $this->language->get('column_location');
		$data['column_published'] = $this->language->get('column_published');
		$data['column_products'] = $this->language->get('column_products');
		$data['column_file'] = $this->language->get('column_file');
		$data['button_download'] = $this->language->get('button_download');
		$data['button_download_csv'] = $this->language->get('button_download_csv');
		$data['button_download_xml'] = $this->language->get('button_download_xml');
		$data['text_latest_links'] = $this->language->get('text_latest_links');
		$data['latest_csv'] = $this->url->link('information/price_list/latest', 'format=csv');
		$data['latest_xml'] = $this->url->link('information/price_list/latest', 'format=xml');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $data['heading_title'],
			'href' => $this->url->link('information/price_list')
		);

		$data['publications'] = array();
		$publications = $this->model_extension_module_anchor_price->getPublications();

		foreach ($publications as $publication) {
			$data['publications'][] = array(
				'location_name' => $this->language->get('text_location'),
				'published'     => date($this->language->get('datetime_format'), strtotime($publication['published_at'])),
				'product_count' => (int)$publication['product_count'],
				'filename'      => $publication['filename'],
				'xml_filename'  => preg_replace('/\.csv$/i', '.xml', $publication['filename']),
				'download'      => $this->url->link('information/price_list/download', 'publication_id=' . (int)$publication['publication_id']),
				'download_csv'  => $this->url->link('information/price_list/download', 'publication_id=' . (int)$publication['publication_id']),
				'download_xml'  => $this->url->link('information/price_list/xml', 'publication_id=' . (int)$publication['publication_id'])
			);
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/price_list', $data));
	}

	public function download() {
		if (!$this->isEnabled()) {
			return $this->notFound();
		}

		$publication_id = isset($this->request->get['publication_id']) ? (int)$this->request->get['publication_id'] : 0;
		$this->load->model('extension/module/anchor_price');
		$publication = $this->model_extension_module_anchor_price->getPublication($publication_id, true);

		if (!$publication) {
			return $this->notFound();
		}

		return $this->servePublication($publication, 'csv');
	}

	public function xml() {
		if (!$this->isEnabled()) {
			return $this->notFound();
		}

		$publication_id = isset($this->request->get['publication_id']) ? (int)$this->request->get['publication_id'] : 0;
		$this->load->model('extension/module/anchor_price');
		$publication = $publication_id > 0
			? $this->model_extension_module_anchor_price->getPublication($publication_id, true)
			: $this->latestPublication();

		if (!$publication) {
			return $this->notFound();
		}

		return $this->servePublication($publication, 'xml');
	}

	public function latest() {
		if (!$this->isEnabled()) {
			return $this->notFound();
		}

		$format = isset($this->request->get['format']) ? strtolower(trim((string)$this->request->get['format'])) : 'csv';

		if (!in_array($format, array('csv', 'xml'), true)) {
			return $this->notFound();
		}

		$this->load->model('extension/module/anchor_price');
		$publication = $this->latestPublication();

		if (!$publication) {
			return $this->notFound();
		}

		return $this->servePublication($publication, $format);
	}

	private function latestPublication() {
		return $this->model_extension_module_anchor_price->getLatestPublication();
	}

	private function isEnabled() {
		return (bool)$this->config->get('module_anchor_price_status');
	}

	private function servePublication(array $publication, $format) {
		$path = $this->model_extension_module_anchor_price->publicationPath($publication);

		if (!$path || !$this->model_extension_module_anchor_price->publicationFileIsValid($publication, $path)) {
			return $this->notFound();
		}

		$filename = basename($publication['filename']);

		if ($format === 'xml') {
			try {
				require_once DIR_SYSTEM . 'library/anchor_price_exchange.php';
				$output = AnchorPriceExchange::publicationCsvToXml($path, array(
					'publication_id' => (int)$publication['publication_id'],
					'published_at' => $publication['published_at'],
					'source_filename' => $filename,
					'source_sha256' => $publication['checksum_sha256'],
					'product_count' => (int)$publication['product_count']
				));
			} catch (Exception $exception) {
				return $this->notFound();
			}

			$filename = preg_match('/\.csv$/i', $filename)
				? preg_replace('/\.csv$/i', '.xml', $filename)
				: $filename . '.xml';
			$content_type = 'application/xml; charset=utf-8';
			$disposition = 'inline';
		} else {
			$output = file_get_contents($path);

			if ($output === false) {
				return $this->notFound();
			}

			$content_type = 'text/csv; charset=utf-8';
			$disposition = 'attachment';
		}

		$this->response->setCompression(0);
		$this->response->addHeader('Content-Type: ' . $content_type);
		$this->response->addHeader('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
		$this->response->addHeader('Content-Length: ' . strlen($output));
		$this->response->addHeader('X-Content-Type-Options: nosniff');
		$this->response->setOutput($output);
	}

	private function notFound() {
		$this->response->addHeader('HTTP/1.1 404 Not Found');
		$this->load->language('error/not_found');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_error'] = $this->language->get('text_error');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['continue'] = $this->url->link('common/home');
		$data['breadcrumbs'] = array();
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}

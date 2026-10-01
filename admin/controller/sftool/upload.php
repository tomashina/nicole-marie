<?php
class ControllerSFToolUpload extends Controller {

	private $error = array();
	
	public function index($option = array()) {
		require_once(DIR_SYSTEM . 'library/sftool.php');
		require_once(DIR_SYSTEM . 'library/sfcache.php');
		
		$this->load->language('sftool/upload');
		
		$this->load->model('tool/image');
		
		$data['column_image'] = $this->language->get('column_image');	
		$data['column_name'] = $this->language->get('column_name');		
		$data['entry_upload'] = $this->language->get('entry_upload');
		$data['text_completed'] = $this->language->get('text_completed');
		$data['text_filesize'] = $this->language->get('text_filesize');
		$data['text_remove'] = $this->language->get('text_remove');
		$data['text_download'] = $this->language->get('text_download');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_drag_file'] = $this->language->get('text_drag_file');
		$data['text_upload_file'] = $this->language->get('text_upload_file');
		$data['text_upload_empty'] = $this->language->get('text_upload_empty');
		$data['error_limit'] = $this->language->get('error_limit');
		$data['error_quantity'] = $this->language->get('error_quantity');
		
		$this->document->addScript('view/javascript/jquery/upload/canvas-to-blob.min.js');
		$this->document->addScript('view/javascript/jquery/upload/load-image.all.min.js');
		$this->document->addScript('view/javascript/jquery/upload/vendor/jquery.ui.widget.js');
		$this->document->addScript('view/javascript/jquery/upload/jquery.iframe-transport.js');
		$this->document->addScript('view/javascript/jquery/upload/jquery.fileupload.js');
		$this->document->addScript('view/javascript/jquery/upload/jquery.fileupload-process.js');
		$this->document->addScript('view/javascript/jquery/upload/jquery.fileupload-image.js');
		$this->document->addScript('view/javascript/jquery/upload/upload-helper.js');
		$this->document->addScript('view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
		$this->document->addStyle('view/javascript/jquery/magnific/magnific-popup.css');

		$db = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
		
		$appData=aplVerifyLicense($db);
		
		if ($appData['notification_case']!="notification_license_ok") {
			echo "License verification failed because of this reason: ".$appData['notification_text'];
			exit();
		}

		$data['popup'] = $this->model_tool_image->resize('no_image.png', $this->config->get('sftool_image_popup_width'), $this->config->get('sftool_image_popup_height'));
		$data['no_image'] = $this->model_tool_image->resize('no_image.png', $this->config->get('sftool_image_no_image_width'), $this->config->get('sftool_image_no_image_height'));
		$data['fileMaxSize'] = '9999999';
		$data['maxQuantity'] = '10';
		$data['previewMaxWidth'] = $this->config->get('sftool_image_preview_width');
		$data['previewMaxHeight'] = $this->config->get('sftool_image_preview_height');
		
		$data['user_token'] = $this->session->data['user_token'];

		return $this->load->view('sftool/customer', $data);
	}
	
	public function upload() {
		$this->load->language('sftool/upload');

		$json = array();
		
		if (empty($this->request->files) || is_null($this->request->files)) {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name']) && $this->validate()) {
			// Sanitize the filename
			$filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')));

			// Validate the filename length
			if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 64)) {
				$json['error'] = $this->language->get('error_filename');
			}

			// Allowed file extension types
			$allowed = array();

			$extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_ext_allowed'));

			$filetypes = explode("\n", $extension_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Allowed file mime types
			$allowed = array();

			$mime_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_mime_allowed'));

			$filetypes = explode("\n", $mime_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array($this->request->files['file']['type'], $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Check to see if any PHP files are trying to be uploaded
			$content = file_get_contents($this->request->files['file']['tmp_name']);

			if (preg_match('/\<\?php/i', $content)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Return any upload error
			if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
			}
			
			if ($this->request->files['file']['size'] > $this->config->get('config_file_max_size')*1.024) {
				$json['error'] = $this->language->get('error_limit');
			}
		
		} else {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json) {
			$file = $filename . '.' . token(32);

			move_uploaded_file($this->request->files['file']['tmp_name'], DIR_UPLOAD . $file);

			// Hide the uploaded file name so people can not link to it directly.
			$this->load->model('sftool/upload');
			$this->load->model('tool/image');

			$json['code'] = $this->model_sftool_upload->addUpload($filename, $file);
			
			$random_str = md5($json['code']);
			
			copy(DIR_UPLOAD . $file, DIR_IMAGE . $random_str . $filename);
	
			$json['popup'] = $this->model_tool_image->resize($random_str . $filename, '500', '500');
			$json['href'] = $this->url->link('sftool/upload/download&user_token=' . $this->session->data['user_token'], '&code=' . $json['code'], true);
			$json['name'] = $this->request->files['file']['name'];
			$json['success'] = $this->language->get('text_upload');
			$json['myfile'] = $this->request->files['file'];

			unlink(DIR_IMAGE . $random_str . $filename);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete() {
		$this->load->language('sftool/upload');
        $this->load->model('sftool/upload');
		
		$json = array();

		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = 0;
		}
		
		if (isset($this->request->get['customer_id'])) {
			$customer_id = $this->request->get['customer_id'];
		} else {
			$customer_id = array();
		}
		
		if (isset($this->request->get['file'])) {
			$files = $this->request->get['file'];
		} else {
			$files = array();
		}
		
		$keeper = array();
		
		foreach ($files as $file) {
			if ($file != $code) {
				$keeper[] = $file;
			}
		}
		
		$filedata = array();
		
		$filedata = array(
			'customer_id'	=> $customer_id,
			'file'			=> $keeper
		);

		$upload_info = $this->model_sftool_upload->getUploadByCode($code);
		
		if ($upload_info && $this->validate()) {
			$file = DIR_UPLOAD . $upload_info['filename'];
			
			if (file_exists($file)) {
				unlink($file);
				
				$this->model_sftool_upload->DeleteUploadByCode($code);
				$this->model_sftool_upload->UpdateCustomer($filedata);
				
				$json['success'] = $this->language->get('text_success');
			}
		} else {
			$json['error'] = $this->language->get('error_delete');
		}

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function download() {
		$this->load->model('sftool/upload');

		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = 0;
		}

		$upload_info = $this->model_sftool_upload->getUploadByCode($code);

		if ($upload_info) {
			$file = DIR_UPLOAD . $upload_info['filename'];
			$mask = basename($upload_info['name']);

			if (!headers_sent()) {
				if (is_file($file)) {
					header('Content-Type: application/octet-stream');
					header('Content-Description: File Transfer');
					header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize($file));

					readfile($file, 'rb');
					exit;
				} else {
					exit('Error: Could not find file ' . $file . '!');
				}
			} else {
				exit('Error: Headers already sent out!');
			}
		}
	}
	
	public function validateFile() {
		$json = array();
		
		$this->load->language('sftool/upload');
		
		if ($this->request->get['filesize'] > $this->config->get('config_file_max_size')*1.024) {
			$json['error'] = $this->language->get('error_limit');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sftool/upload')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
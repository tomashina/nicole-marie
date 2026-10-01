<?php
class ControllerSFToolSFTool extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('sftool/upload');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sftool/upload');
		$this->model_sftool_upload->DeleteTempFiles();

		$this->getList();
	}

	public function delete() {
		$this->load->language('sftool/upload');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sftool/upload');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $upload_id) {
				$this->model_sftool_upload->DeleteFile($upload_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('sftool/sftool', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}
	
	public function cleanUp() {
		$this->load->language('sftool/upload');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sftool/upload');
		
		if ($this->validate()) {
			$this->model_sftool_upload->DeleteAllFiles();
			$this->model_sftool_upload->DeleteTempFiles();
				
			$this->session->data['success'] = $this->language->get('text_success');
		}
		
		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$this->response->redirect($this->url->link('sftool/sftool', 'user_token=' . $this->session->data['user_token'] . $url, true));
		
		$this->getList();
	}

	protected function getList() {
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_list'] = $this->language->get('text_list');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_no_results'] = $this->language->get('text_no_results');
		
		$data['column_name'] = $this->language->get('column_name');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_encrypt'] = $this->language->get('column_encrypt');
		$data['column_code'] = $this->language->get('column_code');
		$data['column_date_added'] = $this->language->get('column_date_added');
		
		$data['button_cleanup'] = $this->language->get('button_cleanup');
		$data['button_delete'] = $this->language->get('button_delete');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sftool/sftool', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);
		
		require_once(DIR_SYSTEM . 'library/sftool.php');
		require_once(DIR_SYSTEM . 'library/sfcache.php');
			
		$db = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
			
		$appData=aplVerifyLicense($db);
		
		

		$data['clean_up'] = $this->url->link('sftool/sftool/cleanUp', 'user_token=' . $this->session->data['user_token'] . $url, true);
		
		$data['delete'] = $this->url->link('sftool/sftool/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['filters'] = array();

		$filter_data = array(
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$filter_total = $this->model_sftool_upload->getTotalFiles();

		$results = $this->model_sftool_upload->getFiles($filter_data);

		foreach ($results as $result) {
			$data['files'][] = array(
				'upload_id' 	=> $result['upload_id'],
				'name'          => $result['name'],
				'filename'      => $result['filename'],
				'code'      	=> $result['code'],
				'date_added'    => $result['date_added']
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$url = '';

		$pagination = new Pagination();
		$pagination->total = $filter_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('sftool/sftool', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($filter_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($filter_total - $this->config->get('config_limit_admin'))) ? $filter_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $filter_total, ceil($filter_total / $this->config->get('config_limit_admin')));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sftool/sftool_list', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sftool/sftool')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

}
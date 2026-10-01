<?php
define('EXTENSION_VERSION','1.2.0');
class ControllerExtensionModuleHbLowstock extends Controller {
		
	protected $registry;
	private $error = array(); 
	
	public function __construct($registry) {
		$this->registry = $registry;
		if (version_compare(VERSION,'3.0.0.0','>=' )) {
			$this->hb_template_folder 		= 'oc3';
			$this->hb_extension_base 		= 'marketplace/extension';
			$this->hb_token_name 			= 'user_token';
			$this->hb_template_extension 	= '';
			$this->hb_extension_route 		= 'extension/module';
		}else if (version_compare(VERSION,'2.2.0.0','<=' )) {
			$this->hb_template_folder 		= 'oc2';
			$this->hb_extension_base 		= 'extension/module';
			$this->hb_token_name 			= 'token';
			$this->hb_template_extension 	= '.tpl';
			$this->hb_extension_route 		= 'module';
		}else{
			$this->hb_template_folder 		= 'oc2';
			$this->hb_extension_base 		= 'extension/extension';
			$this->hb_token_name 			= 'token';
			$this->hb_template_extension 	= '';
			$this->hb_extension_route 		= 'extension/module';
		}

		if (!isset($_SESSION))  { 
			session_start(); 
		} 
		$_SESSION["hbfm_access_key"]  	= $this->session->data[$this->hb_token_name];
		$_SESSION["hbfm_store_url"]		= HTTPS_CATALOG;

		$this->load->model('extension/module/hb_lowstock');
		$this->load->language($this->hb_extension_route.'/hb_lowstock');

		$this->hb_lowstock_nostock_color 	= ($this->config->get('hb_lowstock_nostock_color')) ? $this->config->get('hb_lowstock_nostock_color') : 'FF0000';
		$this->hb_lowstock_qty 				= ($this->config->get('hb_lowstock_qty')) ? $this->config->get('hb_lowstock_qty') : '5';
		$this->hb_lowstock_add_mode 	    = ($this->config->get('hb_lowstock_add_mode')) ? $this->config->get('hb_lowstock_add_mode') : false;
	}

	public function index() {
		$data['extension_version'] = EXTENSION_VERSION;
		
		$data['store_id'] = 0;

		$this->load->language($this->hb_extension_route.'/hb_lowstock');
		$this->load->model('extension/module/hb_lowstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$extn_info = $this->model_setting_setting->getSetting('hb_lowstock', 0);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('hb_lowstock', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&type=module', true));
			
		}
		
		$text_strings = array(
				'heading_title',
				'tab_report','tab_setting','tab_template','tab_log',
				'text_search_product','text_category','text_manufacturer','text_category_lookup','text_manufacturer_lookup','text_status','text_all','text_enable','text_disable',
				'text_lowstock_qty','text_lowstock_nostock_color','text_lowstock_nostock_template','text_lowstock_to','text_lowstock_template','text_lowstock_edit_mode','text_trigger_orderstatus','text_optional_apps','text_et_connected','text_install_et','text_oopv_validation','text_alternate_trigger','text_auto_disable','text_list_columns','text_enable_logs','text_search_label',
				'text_report_mode','text_all_products','text_low_stock_products',
				'text_export','button_save','button_cancel','button_docs','button_create_template','button_clear_logs'
		);
		
		foreach ($text_strings as $text) {
			$data[$text] = $this->language->get($text);
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link($this->hb_extension_base, $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true)
		);
		
		$data['action'] = $this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true);
		$data['cancel'] = $this->url->link($this->hb_extension_base, $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&type=module', true);

		$data['create_template'] 	= $this->url->link($this->hb_extension_route.'/hb_lowstock/create_template', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name].'&store_id='.$data['store_id'], true);
		$data['preview_link']		= HTTPS_CATALOG.'index.php?route=extension/module/hb_lowstock/preview';
		$data['preview_link'] 		= html_entity_decode($data['preview_link'], ENT_QUOTES, 'UTF-8');
		$data['clear'] 				= $this->url->link($this->hb_extension_route.'/hb_lowstock/clear_logs', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true);
		
		$data[$this->hb_token_name] = $this->session->data[$this->hb_token_name];
		$data['base_route'] = $this->hb_extension_route;	

		$data['admin_language_id'] = (int)$this->config->get('config_language_id');

		$data['email_templates'] = $this->model_extension_module_hb_lowstock->getEmailTemplates($data['store_id']);
		
		$this->load->model('localisation/stock_status');
		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['hb_lowstock_qty'] 				= isset($extn_info['hb_lowstock_qty'])?$extn_info['hb_lowstock_qty']:'5';
		$data['hb_lowstock_nostock_color'] 		= isset($extn_info['hb_lowstock_nostock_color'])?$extn_info['hb_lowstock_nostock_color']:'FF0000';
		$data['hb_lowstock_template'] 			= isset($extn_info['hb_lowstock_template'])?$extn_info['hb_lowstock_template']:'0';
		$data['hb_lowstock_to'] 				= isset($extn_info['hb_lowstock_to'])?$extn_info['hb_lowstock_to']: $this->config->get('config_email');
		$data['hb_lowstock_nostock_template'] 	= isset($extn_info['hb_lowstock_nostock_template'])?$extn_info['hb_lowstock_nostock_template']:'0';
		$data['hb_lowstock_nostock_to'] 		= isset($extn_info['hb_lowstock_nostock_to'])?$extn_info['hb_lowstock_nostock_to']: $this->config->get('config_email');
		$data['hb_lowstock_status'] 			= isset($extn_info['hb_lowstock_status'])?$extn_info['hb_lowstock_status']: '';
		$data['hb_lowstock_nostock_status'] 	= isset($extn_info['hb_lowstock_nostock_status'])?$extn_info['hb_lowstock_nostock_status']: '';
		$data['hb_lowstock_add_mode'] 			= isset($extn_info['hb_lowstock_add_mode'])?$extn_info['hb_lowstock_add_mode']: '';
		$data['hb_lowstock_auto_disable'] 		= isset($extn_info['hb_lowstock_auto_disable'])?$extn_info['hb_lowstock_auto_disable']: '';
		$data['hb_lowstock_auto_disable_ss'] 	= isset($extn_info['hb_lowstock_auto_disable_ss'])?$extn_info['hb_lowstock_auto_disable_ss']: '5';
		$data['hb_lowstock_order_statuses'] 	= isset($extn_info['hb_lowstock_order_statuses'])?$extn_info['hb_lowstock_order_statuses']: array();
		$data['hb_lowstock_alt_trigger'] 		= isset($extn_info['hb_lowstock_alt_trigger'])?$extn_info['hb_lowstock_alt_trigger']: '';
		$data['hb_lowstock_dashboard_mode'] 	= isset($extn_info['hb_lowstock_dashboard_mode'])?$extn_info['hb_lowstock_dashboard_mode']: 'low';
		$data['hb_lowstock_worklog'] 			= isset($extn_info['hb_lowstock_worklog'])?$extn_info['hb_lowstock_worklog']: '';		
		$data['hb_lowstock_opov_validation'] 	= isset($extn_info['hb_lowstock_opov_validation'])?$extn_info['hb_lowstock_opov_validation']: '';

		$columns = $this->model_extension_module_hb_lowstock->table_columns();
	
		foreach ($columns as $column) {
			$data['hb_lowstock_col'][$column] 	= isset($extn_info['hb_lowstock_col_'.$column])?$extn_info['hb_lowstock_col_'.$column]:'';
			$data['show'][$column] 				= isset($extn_info['hb_lowstock_col_'.$column])?$extn_info['hb_lowstock_col_'.$column]:false;

			$data['product_columns'][] = array(
				'column_id'		=> $column,
				'column_name'	=> $this->language->get('text_'.$column),
			);
		}

		//LINK OPTIONAL APPS
		if ($this->model_extension_module_hb_lowstock->isExtensionInstalled('email_templates')) {
			$data['et_extn_linked'] = true;
		}else{
			$data['et_extn_linked'] = false;
		}

		$check_updates = $this->model_extension_module_hb_lowstock->check_updates();

		if (!empty($check_updates)){
			$data['update_info'] = 'New version '.$check_updates['version'].' is available. Please install the new version by downloading it from <a href="'.$check_updates['access_link'].'" target="_blank">here</a>. For more details refer to <a href="'.$check_updates['changelog'].'" target="_blank">changelog</a>.';
		}else{
			$data['update_info'] = false;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/'.$this->hb_template_folder.'/hb_lowstock'.$this->hb_template_extension, $data));
	}

	public function templates() {  			
		$store_id 	= (isset($this->request->get['store_id'])) ? (int)$this->request->get['store_id']: 0;
		$page 		= (isset($this->request->get['page'])) ? (int)$this->request->get['page']: 1;
		$search 	= (isset($this->request->get['search'])) ? $this->request->get['search']: '';
		
		$data = array(
			'start' 	=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' 	=> $this->config->get('config_limit_admin'),
			'store_id'	=> $store_id,
			'search'	=> $search
		);

		$data[$this->hb_token_name] = $this->session->data[$this->hb_token_name];
		$data['base_route'] 		= $this->hb_extension_route;	
		
		$reports_total 		= $this->model_extension_module_hb_lowstock->getTotalTemplates($data); 		
		$records 			= $this->model_extension_module_hb_lowstock->getTemplates($data);
		$data['records'] 	= array();
		
		foreach ($records as $record) {
			$data['records'][] = array(
				'id' 			=> $record['id'],
				'label' 		=> $record['template_label'],
				'edit'			=> $this->url->link($this->hb_extension_route.'/hb_lowstock/edit_template', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name].'&id='.$record['id'], true),
				'date_added' 	=> $record['date_added']
			);
		}
		
		$pagination = new Pagination();
		$pagination->total = $reports_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->hb_extension_route.'/hb_lowstock/templates', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&search='.$search.'&page={page}', true);

		$data['pagination'] = $pagination->render();
		$limit = $this->config->get('config_limit_admin');

		$data['results'] = sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($pagination->total - $limit)) ? $pagination->total : ((($page - 1) * $limit) + $limit), $pagination->total, ceil($pagination->total / $limit));

		$text_strings = array(
			'text_id','text_label','text_date_added','text_action','text_no_records','button_edit','button_preview','button_delete',
		);
		
		foreach ($text_strings as $text) {
			$data[$text] = $this->language->get($text);
		}

		$this->response->setOutput($this->load->view('extension/module/'.$this->hb_template_folder.'/hb_lowstock_templates'.$this->hb_template_extension, $data));
	}

	public function create_template(){	
		$store_id = (isset($this->request->get['store_id']))? (int)$this->request->get['store_id']:'0';
		$last_inserted_id = $this->model_extension_module_hb_lowstock->addTemplateInstance('NEW LABEL EN',$store_id);
		$this->response->redirect($this->url->link($this->hb_extension_route.'/hb_lowstock/edit_template', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name].'&id='.$last_inserted_id, true));
	}
	
	public function delete_template(){
		$id = trim($this->request->post['id']);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_build_template` WHERE `id` = '".(int)$id."'");
		$json['success'] = 'Template deleted successfully';
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function edit_template(){
		if (isset($this->request->get['store_id'])){
			$data['store_id'] = (int)$this->request->get['store_id'];
		}else{
			$data['store_id'] = 0;
		}
		
		if (isset($this->request->get['id'])){
			$data['template_id'] = (int)$this->request->get['id'];
		}else{
			$data['template_id'] = 0;
		}
		
		$template_id = $data['template_id'];
		
		$this->load->language($this->hb_extension_route.'/hb_lowstock');
		$this->load->model('tool/image');
		
		$this->document->setTitle($this->language->get('heading_title_edit'));
		
		$text_strings = array(
				'heading_title','heading_title_edit',
				'button_short_codes','button_generate','button_refresh','button_desktop','button_tablet','button_mobile',
				'tab_email_template','tab_email_option','tab_email_preview',
				'text_label','text_email_content','text_layouts','text_width','text_color1',
				'text_color2','text_color3','text_color4','text_content_sample',
				'text_sender_name','text_sender_email','text_bcc','text_reply_to','text_subject',
				'text_email_preview',
				'button_save','button_cancel',
		);
		
		foreach ($text_strings as $text) {
			$data[$text] = $this->language->get($text);
		}
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true)
   		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($this->hb_extension_base, $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&type=module', true)
		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name].'&store_id='.$data['store_id'], true)
   		);
		
		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title_edit'),
			'href'      => $this->url->link($this->hb_extension_route.'/hb_lowstock/edit_template', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name].'&id='.$template_id, true)
   		);
				
		$data['cancel'] = $this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] .'&store_id='.$data['store_id']. '&type=module', true);
		$data[$this->hb_token_name] = $this->session->data[$this->hb_token_name];
		$data['base_route'] = $this->hb_extension_route;
		
		$data['simple_layouts'] 	= $this->model_extension_module_hb_lowstock->simple_layout_templates('view/template/extension/hbapps/simple_email_layouts');
		$data['simple_contents'] 	= $this->model_extension_module_hb_lowstock->simple_layout_templates('view/template/extension/module/hb_lowstock/simple_email_contents');
		
		$this->load->model('setting/setting');
		$store_info = $this->model_setting_setting->getSetting('config', $data['store_id']);
		$extn_info = $this->model_setting_setting->getSetting('hb_ose', $data['store_id']);
		
		//get data
		$result = $this->model_extension_module_hb_lowstock->getTemplate($template_id);
		$email_options = array();
		$data['label'] = '';
		if ($result){
			$data['label'] = $result['template_label'];
			$result['email_options'] = trim(preg_replace('/\s+/', ' ', $result['email_options']));
			$email_options = json_decode($result['email_options'], true);
		}

		$data['email_body'] 	= (isset($result['draft_body']))? html_entity_decode($result['draft_body']) : '';

		$data['email_subject'] 	= (isset($email_options['email_subject']))? $email_options['email_subject'] : 'Your order has been updated to the following status: {order_status}';
		$data['sender_name'] 	= (isset($email_options['sender_name']))? $email_options['sender_name'] : $store_info['config_name'];
		$data['sender_email'] 	= (isset($email_options['sender_email']))? $email_options['sender_email'] : $store_info['config_email'];
		$data['email_replyto'] 	= (isset($email_options['email_replyto']))? $email_options['email_replyto'] : '';
		$data['email_bcc'] 		= (isset($email_options['email_bcc']))? $email_options['email_bcc'] : '';
		
		//preview
		$data['preview_link']		= HTTPS_CATALOG.'index.php?route=extension/module/hb_lowstock/preview&template_id='.$data['template_id'];
		$data['preview_link'] 		= html_entity_decode($data['preview_link'], ENT_QUOTES, 'UTF-8');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/'.$this->hb_template_folder.'/hb_lowstock_template_edit'.$this->hb_template_extension, $data));
	}
	
	public function update_label(){
		$id = (int)$this->request->get['id'];
		$label = trim($this->request->post['label']);
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->db->query("UPDATE `" . DB_PREFIX . "hb_build_template` SET `template_label` = '" . $this->db->escape($label). "' WHERE id = '".(int)$id."'");
			$json['success'] = 'Template Label Updated';
		}else{
			$json['warning'] =  $this->language->get('error_permission');
		}
		
		$this->response->setOutput(json_encode($json));
	}
	
	public function savecontents(){
		$template_id 	= (int)$this->request->get['template_id'];
		//$draft_head 	= '<!DOCTYPE html><html><head><title>{email_subject}</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="X-UA-Compatible" content="IE=edge" /></head><body>';
		$draft_head		= '';
		$draft_body 	= $this->request->post['draft_body'];
	
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->db->query("UPDATE " . DB_PREFIX . "hb_build_template SET `draft_head` = '" . $this->db->escape(html_entity_decode($draft_head)). "', `draft_body` = '" . $this->db->escape(html_entity_decode($draft_body)). "', date_modified = now() WHERE `id` = '".(int)$template_id."'");
			$json['success'] = 'Email Contents Saved Successfully';
		}else{
			$json['warning'] =  $this->language->get('error_permission');
		}
		
		$this->response->setOutput(json_encode($json));
	}

	public function saveemailoptions(){
		$template_id = (int)$this->request->get['template_id'];
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->db->query("UPDATE " . DB_PREFIX . "hb_build_template SET `email_options` = '" . $this->db->escape(json_encode($this->request->post)). "', date_modified = now() WHERE `id` = '".(int)$template_id."'");
			$json['success'] = 'Email Options Saved Successfully';
		}else{
			$json['warning'] =  $this->language->get('error_permission');
		}
		
		$this->response->setOutput(json_encode($json));
	}

	public function loadSimpleLayout(){
		$json['layout'] = ' ';
		
		$data = $this->request->post;
		$this->load->model('setting/setting');
		$config 			= $this->model_setting_setting->getSetting('config', (int)$data['store_id']);
		$data['logo'] 		= isset($config['config_logo'])? HTTPS_CATALOG.'image/'.$config['config_logo'] : HTTPS_CATALOG.'image/catalog/logo.png';
		$data['store_url'] 	= HTTPS_CATALOG;

		$file = 'view/template/extension/hbapps/simple_email_layouts/'.$data['selected_template'].'.txt';
		if (file_exists($file)) {
			$json['layout'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
			
			$content_file = 'view/template/extension/module/hb_lowstock/simple_email_contents/'.$data['email_type'].'.txt';
			if (file_exists($file)) {
				$content = file_get_contents($content_file, FILE_USE_INCLUDE_PATH, null);
			}else{
				$content = '';
			}

			$json['layout'] = str_replace('{content}', $content, $json['layout']);
			
			foreach ($data as $key => $value){
				$json['layout'] = str_replace('{'.$key.'}',$value,$json['layout']);
			}
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}

	public function products(){
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		$page 					= (isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1;
		$search 				= (isset($this->request->get['search'])) ? $this->request->get['search'] : '';
		$search_category_id 	= (isset($this->request->get['search_category_id'])) ? $this->request->get['search_category_id'] : '';
		$search_manufacturer_id = (isset($this->request->get['search_manufacturer_id'])) ? $this->request->get['search_manufacturer_id'] : '';
		$search_status 			= (isset($this->request->get['search_status'])) ? $this->request->get['search_status'] : '';


		$data = array(
			'start' 	=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' 	=> $this->config->get('config_limit_admin'),
			'search'	=> $search,
			'search_category_id'		=> $search_category_id,
			'search_manufacturer_id'	=> $search_manufacturer_id,
			'search_status'				=> $search_status
		);

		$data[$this->hb_token_name] = $this->session->data[$this->hb_token_name];	
		
		$product_total = $this->model_extension_module_hb_lowstock->getTotalProducts($data); 		
		$results = $this->model_extension_module_hb_lowstock->getProducts($data);

		$data['products'] = array();
		foreach ($results as $result) {
			if ($this->config->get('hb_lowstock_dashboard_mode') == 'low') {
				if (is_file(DIR_IMAGE . $result['option_image'])) {
					$image = $this->model_tool_image->resize($result['option_image'], 40, 40);
				}else if (is_file(DIR_IMAGE . $result['image'])) {
					$image = $this->model_tool_image->resize($result['image'], 40, 40);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', 40, 40);
				}
			}else{
				if (is_file(DIR_IMAGE . $result['image'])) {
					$image = $this->model_tool_image->resize($result['image'], 40, 40);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', 40, 40);
				}
			}
			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

					break;
				}
			}

			$product_categories = array();
			if ($this->config->get('hb_lowstock_col_category')) {
				$categories = $this->model_catalog_product->getProductCategories($result['product_id']);

				foreach ($categories as $category_id) {
					$category_info = $this->model_extension_module_hb_lowstock->getCategory($category_id);

					if ($category_info) {
						$product_categories[] = array(
							'category_id' => $category_info['category_id'],
							'name'        => ($category_info['path']) ? $category_info['path'] . ' / ' . $category_info['name'] : $category_info['name']
						);
					}
				}
			}

			$option_quantity = array();
			$validation_qty = true;
			if ($this->config->get('hb_lowstock_col_option_quantity') && $this->config->get('hb_lowstock_dashboard_mode') == 'all') {
				if ($this->model_extension_module_hb_lowstock->isOptionAvailable($result['product_id'])) {
					$option_quantity = $this->model_extension_module_hb_lowstock->getOptionQuantities($result['product_id']);
					$sum_option_qty = $this->model_extension_module_hb_lowstock->optionQuantitySum($result['product_id']);
					
					if ($sum_option_qty != $result['quantity']){
						$validation_qty = false;
					}
				}
			}else{
				if (!empty($result['product_option_value_id'])) {
					$option_quantity[] = array(
						'product_option_value_id'	=> $result['product_option_value_id'],
						'option_name'				=> $result['option_name'],
						'option_value_name'			=> $result['option_value_name'],
						'quantity'					=> $result['option_quantity'],
						'hb_pov_threshold'			=> $result['hb_pov_threshold']
					);
				}
			}

			$data['products'][] = array(
				'product_id' 			=> $result['product_id'],
				'image'      			=> $image,
				'name' 					=> $result['name'],
				'product_categories' 	=> $product_categories,
				'option_quantity'		=> $option_quantity,
				'model' 				=> $result['model'],
				'sku' 					=> $result['sku'],
				'upc' 					=> $result['upc'],
				'price'      			=> $this->currency->format($result['price'], $this->config->get('config_currency')),
				'special'    			=> $special,
				'manufacturer' 			=> $result['manufacturer'],
				'quantity' 				=> $result['quantity'],
				'hb_p_threshold'		=> $result['hb_p_threshold'],
				'validation_qty' 		=> $validation_qty,
				'status'				=> $result['status'],
				'date_added'			=> $result['date_added'],
				'edit' 					=> $this->url->link('catalog/product/edit', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&product_id=' . $result['product_id'], true),
				'view'					=> HTTPS_CATALOG.'index.php?route=product/product&product_id='. $result['product_id']
			);
		}
		
		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->hb_extension_route.'/hb_lowstock/products', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name] . '&search='.$search.'&search_category_id='.$search_category_id.'&search_manufacturer_id='.$search_manufacturer_id.'&search_status='.$search_status.'&page={page}', true);

		$data['pagination'] = $pagination->render();
		$limit = $this->config->get('config_limit_admin');

		$data['results'] = sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($pagination->total - $limit)) ? $pagination->total : ((($page - 1) * $limit) + $limit), $pagination->total, ceil($pagination->total / $limit));

		$text_strings = array(
			'text_product_id','text_name','text_image','text_model','text_sku','text_upc','text_manufacturer','text_category','text_price','text_quantity','text_option_quantity','text_status','text_action','text_qty_validation_error','text_lowstock_qty','text_threshold','text_no_records','text_enable','text_disable','button_edit','button_view',
		);
		
		foreach ($text_strings as $text) {
			$data[$text] = $this->language->get($text);
		}

		$data['column_set_1'] = array(
			array($this->language->get('text_model'),'model','left'),
			array($this->language->get('text_sku'),'sku','left'),
			array($this->language->get('text_upc'),'upc','left'),
			array($this->language->get('text_manufacturer'),'manufacturer','left'),
		);

		$data['hb_lowstock_nostock_color'] 	= $this->hb_lowstock_nostock_color;
		$data['hb_lowstock_add_mode'] 		= $this->hb_lowstock_add_mode;

		$this->response->setOutput($this->load->view('extension/module/'.$this->hb_template_folder.'/hb_lowstock_products'.$this->hb_template_extension, $data));
	}

	public function update_quantity(){
		$product_id 				= (int)$this->request->post['product_id'];
		$value 						= $this->request->post['value'];
			
		if ($value == '') {
			$json['warning'] = $this->language->get('error_empty_qty_field');
		}else{
			if ($this->validate()) {
				$this->model_extension_module_hb_lowstock->updateQuantity($product_id, $value, $this->hb_lowstock_add_mode);
				$json['success'] = $this->language->get('text_qty_updated');

				$json['qty'] = $this->model_extension_module_hb_lowstock->getQuantity($product_id);

			}else{
				$json['warning'] = $this->language->get('error_permission');
			}
		}
		
		$this->response->setOutput(json_encode($json));
	}

	public function update_threshold(){
		$product_id 				= (int)$this->request->post['product_id'];
		$value 						= $this->request->post['value'];
			
		if ($value == '') {
			$json['warning'] = $this->language->get('error_empty_threshold_field');
		}else{
			if ($this->validate()) {
				$this->model_extension_module_hb_lowstock->updateThreshold($product_id, $value);
				$json['success'] = $this->language->get('text_threshold_updated');

			}else{
				$json['warning'] = $this->language->get('error_permission');
			}
		}
		
		$this->response->setOutput(json_encode($json));
	}

	public function update_option_quantity(){
		$product_id 				= (int)$this->request->post['product_id'];
		$product_option_value_id 	= (int)$this->request->post['product_option_value_id'];
		$value 						= $this->request->post['value'];
			
		if ($value == '') {
			$json['warning'] = $this->language->get('error_empty_qty_field');
		}else{
			if ($this->validate()) {
				$this->model_extension_module_hb_lowstock->updateOptionQuantity($product_id, $product_option_value_id, $value, $this->hb_lowstock_add_mode);
				$json['success'] 	= $this->language->get('text_qty_updated');
				$json['qty'] 		= $this->model_extension_module_hb_lowstock->getQuantity($product_id);
				$json['hb_lowstock_add_mode'] 		= $this->hb_lowstock_add_mode;
				$json['option_qty'] = $this->model_extension_module_hb_lowstock->getOptionQuantity($product_option_value_id);
			}else{
				$json['warning'] = $this->language->get('error_permission');
			}
		}		
		
		$this->response->setOutput(json_encode($json));
	}

	public function update_option_threshold(){
		$product_id 				= (int)$this->request->post['product_id'];
		$product_option_value_id 	= (int)$this->request->post['product_option_value_id'];
		$value 						= $this->request->post['value'];
			
		if ($value == '') {
			$json['warning'] = $this->language->get('error_empty_threshold_field');
		}else{
			if ($this->validate()) {
				$this->model_extension_module_hb_lowstock->updateOptionThreshold($product_id, $product_option_value_id, $value);
				$json['success'] 	= $this->language->get('text_threshold_updated');
			}else{
				$json['warning'] = $this->language->get('error_permission');
			}
		}		
		
		$this->response->setOutput(json_encode($json));
	}

	public function update_status(){
		$product_id 				= (int)$this->request->post['product_id'];
		$value 						= (int)$this->request->post['value'];
			
		if ($this->validate()) {
			$this->model_extension_module_hb_lowstock->updateStatus($product_id, $value);
			$json['success'] = $this->language->get('text_status_updated');
		}else{
			$json['warning'] = $this->language->get('error_permission');
		}
	
		$this->response->setOutput(json_encode($json));
	}

	public function logs(){
		if (!file_exists(DIR_LOGS . 'huntbee_lowstock_logs')) {
			mkdir(DIR_LOGS . 'huntbee_lowstock_logs', 0777, true);
		}

		$file = DIR_LOGS . 'huntbee_lowstock_logs/lowstock_logs.txt';
		if (file_exists($file)) {
			$data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
		}else{
			$data['log'] = '';
		}
		$this->response->setOutput($this->load->view('extension/module/'.$this->hb_template_folder.'/hb_lowstock_worklog'.$this->hb_template_extension, $data));
	}
	
	public function clear_logs() {
		if (!$this->validate()) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$file = DIR_LOGS . 'huntbee_lowstock_logs/lowstock_logs.txt';

			$handle = fopen($file, 'w+');

			fclose($handle);

			$this->session->data['success'] =  $this->language->get('text_success_logs');
		}

		$this->response->redirect($this->url->link($this->hb_extension_route.'/hb_lowstock', $this->hb_token_name.'=' . $this->session->data[$this->hb_token_name], true));
	}

	public function export2csv(){		
		if (isset($this->request->get['search'])) {
			$search = $this->request->get['search'];
		} else {
			$search = '';
		}

		if (isset($this->request->get['search_category_id'])) {
			$search_category_id = $this->request->get['search_category_id'];
		} else {
			$search_category_id = '';
		}

		if (isset($this->request->get['search_manufacturer_id'])) {
			$search_manufacturer_id = $this->request->get['search_manufacturer_id'];
		} else {
			$search_manufacturer_id = '';
		}

		$filename = 'huntbee_lowstock_'.date("Y-m-d-H-i-s").'.csv';

        $fp = fopen('php://output', 'w');
        
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='.$filename);

        fputs( $fp, "\xEF\xBB\xBF" );

		$data = array(
			'search'	=> $search,
			'search_category_id'		=> $search_category_id,
			'search_manufacturer_id'	=> $search_manufacturer_id
		);
        $rows = $this->model_extension_module_hb_lowstock->export_products($data);

		if (!empty($rows)) {
			$first_row = $rows[0];
			
			foreach ($first_row as $key => $value){
				$columns[] = $key;
			}
			fputcsv($fp, $columns);

			foreach ($rows as $row) {	
				fputcsv($fp, $row);
			}	
		}
  
        fclose($fp);
        exit;
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->hb_extension_route.'/hb_lowstock')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install(){
		$this->load->model('extension/module/hb_lowstock');
		$this->model_extension_module_hb_lowstock->install();
		$data['success'] = 'Module has been installed successfully';
	}
	
	public function uninstall(){
		$this->load->model('extension/module/hb_lowstock');
		$this->model_extension_module_hb_lowstock->uninstall();
		$data['success'] = 'Module uninstalled Successfully!';
	}
	
	public function update(){
		$this->load->model('extension/module/hb_lowstock');
		$this->model_extension_module_hb_lowstock->update();
		return true;
	}
}
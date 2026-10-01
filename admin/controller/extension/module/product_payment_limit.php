<?php
#############################################################################
   # version 1.2.0 
   # copyright Sunflowerbiz
   # contact : yolanda_txw@hotmail.com
#############################################################################
class ControllerExtensionModuleProductPaymentLimit extends Controller {
	private $error = array();


	public function index() {
		$this->template = 'extension/module/product_payment_limit.tpl';
	if(isset($this->session->data['user_token']) ){
	$this->session->data['token']=$this->session->data['user_token'];
	$this->template='extension/module/product_payment_limit';
	}
	
		$this->load->language('extension/module/product_payment_limit');
		$this->document->setTitle( $this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('module_product_payment_limit', $this->request->post);
			$this->model_setting_setting->editSetting('mix_cart_payment', $this->request->post);

			$this->model_setting_setting->editSetting('conflict_cart_payment', $this->request->post);
			
	if(!empty($this->request->post['force_payment_methods_array'])){
		$this->request->post['force_payment_methods'] = implode('|',$this->request->post['force_payment_methods_array']);
  	}else{
		$this->request->post['force_payment_methods'] = NULL;
	}
			
			$this->model_setting_setting->editSetting('force_payment_methods', $this->request->post);

		$this->session->data['success'] = $this->language->get('text_success');
$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
				
		}
		
	$this->db->query("CREATE  TABLE IF NOT EXISTS `" . DB_PREFIX . "product_payment_limit` ( 
  				`product_payment_limit_id` int(11) NOT NULL auto_increment, 
  				`product_id` int( 11  )  NOT  NULL default  '0',
 				`product_payment_limit` text  NOT  NULL default  '' ,
 				PRIMARY KEY  (`product_payment_limit_id`)
 	)");
	
	
		$data['heading_title'] = $this->language->get('heading_title');
		$data['enable_product_payment_limit'] = $this->language->get('enable_product_payment_limit');
		$data['mix_cart_payment_method'] = $this->language->get('mix_cart_payment_method');
		$data['mix_cart_payment_method_2'] = $this->language->get('mix_cart_payment_method_2');
		$data['mix_cart_payment_method_1'] = $this->language->get('mix_cart_payment_method_1');

		$data['conflict_cart_payment_method'] = $this->language->get('conflict_cart_payment_method');
		$data['conflict_cart_payment_method_2'] = $this->language->get('conflict_cart_payment_method_2');
		$data['conflict_cart_payment_method_1'] = $this->language->get('conflict_cart_payment_method_1');
  	
		$data['force_payment_method'] = $this->language->get('force_payment_method');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning']))
		{
			$data['error_warning'] = $this->error['warning'];
		}
		else
		{
			$data['error_warning'] = '';
		}
		if (isset($this->error['error_sort_order']))
		{
			$data['error_sort_order'] = $this->error['error_sort_order'];
		}
		else
		{
			$data['error_sort_order'] = '';
		}
		if (isset($this->error['error_limit']))
		{
			$data['error_limit'] = $this->error['error_limit'];
		}
		else
		{
			$data['error_limit'] = '';
		}

  		$this->document->breadcrumbs = array();

   		$this->document->breadcrumbs[] = array(
       		'href'      => (HTTPS_SERVER . 'index.php?route=common/home&token=' . $this->session->data['token']),
       		'text'      => $this->language->get('text_home'),
      		'separator' => FALSE
   		);

   		$this->document->breadcrumbs[] = array(
       		'href'      => (HTTPS_SERVER . 'index.php?route=extension/extension&user_token=' . $this->session->data['token']),
       		'text'      => $this->language->get('text_module'),
      		'separator' => ' :: '
   		);

   		$this->document->breadcrumbs[] = array(
       		'href'      => (HTTPS_SERVER . 'index.php?route=extension/module/product_payment_limit&user_token=' . $this->session->data['token']),
       		'text'      => $this->language->get('heading_title'),
      		'separator' => ' :: '
   		);

	
		
$data['action'] = $this->url->link('extension/module/product_payment_limit', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		

		if (isset($this->request->post['product_payment_limit'])) {
			$data['product_payment_limit'] = $this->request->post['module_product_payment_limit_status'];
		} else {
			$data['product_payment_limit'] = $this->config->get('module_product_payment_limit_status');
		}
		
		if (isset($this->request->post['mix_cart_payment'])) {
			$data['mix_cart_payment'] = $this->request->post['mix_cart_payment'];
		} else {
			$data['mix_cart_payment'] = $this->config->get('mix_cart_payment');
		}
		
		
		if (isset($this->request->post['conflict_cart_payment'])) {
			$data['conflict_cart_payment'] = $this->request->post['conflict_cart_payment'];
		} else {
			$data['conflict_cart_payment'] = $this->config->get('conflict_cart_payment');
		}
		
	
		if (isset($this->request->post['force_payment_methods'])) {
			$data['force_payment_methods'] = $this->request->post['force_payment_methods'];
		} else {
			$data['force_payment_methods'] = $this->config->get('force_payment_methods');
		}
		
		$this->id       = 'product_payment_limit';
		$this->template = 'extension/module/product_payment_limit';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		
		
		
		
		
			$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $data['heading_title'] ,
			'href' => $this->url->link('extension/module/product_payment_limit', 'user_token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('extension/module/product_payment_limit', 'user_token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['token'], 'SSL');
		
		$force_payment_methods=explode('|',$data['force_payment_methods']);
		$data['text_edit'] = $this->language->get('text_edit');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

 $payment_methods=array();
				  $files = glob(DIR_APPLICATION . 'controller/extension/payment/*.php');
		if(!isset($data['force_payment_methods'])) $data['force_payment_methods']=array();
		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');
				
				$this->load->language('extension/payment/' . $extension);
	
				$action = array();	
				if( $this->config->get('payment_'.$extension . '_status')){
				$title=$this->language->get('heading_title');
				if(stristr($title,'<script')) $title=$extension;	
				$payment_methods[] = array(
					'code'       => $extension,
					'name'       => $title,
					'status'     => $this->config->get($extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'sort_order' => $this->config->get($extension . '_sort_order'),
					'action'     => $action,
					'isforce'     => (in_array($extension, $force_payment_methods)? 'checked="checked"' :'')
				);
				}
			}
		}
		
		//$force_payment_methods=explode('|',$data['force_payment_methods']);
		
		$data['payment_methods']=$payment_methods;
	//	$data['force_payment_methods']=$force_payment_methods;
		
		$this->response->setOutput($this->load->view($this->template, $data));
		//$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
		//$this->render();
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/product_payment_limit')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
	
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>
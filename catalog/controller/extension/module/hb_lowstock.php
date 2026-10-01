<?php  
class ControllerExtensionModuleHbLowstock extends Controller {
	protected $registry;

	public function __construct($registry) {
		$this->registry = $registry;
		if (version_compare(VERSION,'2.2.0.0','<' )) {
			$this->user = new User($this->registry);
		}else{
			$this->user = new Cart\User($this->registry);
		}
	}

	public function preview(){
		$this->load->model('extension/module/hb_lowstock');
		
		if (isset($this->request->get['template_id'])) {
			$template_id = (int)$this->request->get['template_id'];
		}else{
			$template_id = '0';
		}
		
		if ($this->user->isLogged()) {
			//get a random product ID
			$query = $this->db->query("SELECT p.product_id FROM  `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_description` pd ON (p.product_id = pd.product_id) WHERE p.status = 1 AND pd.language_id = '".(int)$this->config->get('config_language_id')."' AND p.quantity <= '".(int)$this->config->get('hb_lowstock_qty')."' ORDER BY RAND() LIMIT 1");
			if ($query->row){
				$product_id 			= (int)$query->row['product_id'];

				$alert_data = array(
					'product_id' 	=> $product_id,
					'template_id'	=> (int)$template_id,
					'email_enabled'	=> true,
					'to'			=> $this->config->get('config_email'),
					'preview'		=> true
				);

				$result = $this->model_extension_module_hb_lowstock->alert($alert_data);
			}else{
				$result = 'NO ELIGIBLE PRODUCT FOUND FOR PREVIEW';
			}
			echo $result;
		}else{
			die('You are not authorized to view this!');
		}
	}	

	//EVENTS
	public function event_lowstock_alert(&$route, &$args){
		$order_id = $args[0];
		$this->load->model('extension/module/hb_lowstock');
		$this->model_extension_module_hb_lowstock->stock_validation($order_id);
	}

	public function event_lowstock_alert_21xx($order_id){
		$this->load->model('extension/module/hb_lowstock');
		$this->model_extension_module_hb_lowstock->stock_validation($order_id);
	}
	
	public function event_lowstock_alert_22xx($route, $output, $order_id, $order_status_id){
		$this->load->model('extension/module/hb_lowstock');	
		$this->model_extension_module_hb_lowstock->stock_validation($order_id);
	}
}
?>
<?php
class ModelExtensionModulecompgafad extends Model {
	private $divcls = 'form-group';
	private $lblcls = 'control-label';
	private $wellcls = 'well well-sm';
	private $grpcls = 'input-group-addon';
	private $slctcls = 'form-control';
	
	private $modpath = 'module/compgafad'; 
	private $modvar = 'model_module_compgafad';
	private $modname = 'compgafad';
	private $modsprtor = '/';
	
	private $evntcode = 'compgafad';
	private $error = array();
	private $status = '';
	private $setting = array();

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
		
		if(substr(VERSION,0,3)=='2.3') {
			$this->modpath = 'extension/module/compgafad';
			$this->modvar = 'model_extension_module_compgafad';
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modpath = 'extension/module/compgafad';
			$this->modvar = 'model_extension_module_compgafad';
			$this->modname = 'module_compgafad';
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/compgafad/module/compgafad';
			$this->modvar = 'model_extension_compgafad_module_compgafad';
			$this->modname = 'module_compgafad';
			$this->modsprtor = '.';
			$this->divcls = 'row mb-3';
			$this->lblcls = 'col-form-label';
			$this->wellcls = 'form-control';
			$this->grpcls = 'input-group-text';
			$this->slctcls = 'form-select';			
		}
		
		$ismulti_store = 1;
		if($ismulti_store) {
			$this->setting = $this->getSetting();
			$this->status = ($this->config->get($this->modname.'_status') && $this->setting['status']) ? true : false;	
		} else {
			$this->status = $this->config->get($this->modname.'_status');
		}
 	}
	public function getcache() {
		$json = array();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function pageview() {
		if($this->status) {
			$fb_g_code = array();

			$fb_g_code[] = $this->get_google_consent_code();
			
			if($this->setting['gmid']) {
				$fb_g_code[] = '<!-- Global site tag (gtag.js) - Google Analytics -->
				<script async src="https://www.googletagmanager.com/gtag/js?id='.($this->setting['gmid']).'"></script>
				<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag(\'js\', new Date());';
				if($this->setting['gmid']) { 
					$fb_g_code[] = 'gtag(\'config\', \''.$this->setting['gmid'].'\');';
				}
				if($this->setting['prch_adwid']) {
					$fb_g_code[] = 'gtag(\'config\', \''.$this->setting['prch_adwid'].'\', {\'allow_enhanced_conversions\':true});';
				}
				$fb_g_code[] = '</script>';
			}
			
			$src = substr(VERSION,0,3) == '4.0' ? 'extension/compgafad/catalog/view/javascript/compgafad.js' : 'catalog/view/javascript/compgafad.js';
			
			$fb_g_code[] = sprintf('<script src="%s" type="text/javascript"></script>',$src);
			
			return join($fb_g_code);
		}			
	}
	public function login() {
		$fb_g_code = array();
		if($this->status) {
 			$evname = 'Login'; $evid = rand(); $flag_cust_ev = 1;	
			
			if($this->setting['gmid']) { 
				$fb_g_code[] = "<script type='text/javascript'> gtag('event', 'login', {'method': 'Account Login'}); </script>";
			}		
			
			return join($fb_g_code);
		}
	}
	public function logoutbefore() {
		$this->session->data['compgafad_logout_flag'] = 1;
	}
	public function logout() {
		$fb_g_code = array();
		if($this->status && isset($this->session->data['compgafad_logout_flag'])) {
			unset($this->session->data['compgafad_logout_flag']);
 			$evname = 'Logout'; $evid = rand(); $flag_cust_ev = 1;
			
			if($this->setting['gmid']) { 
				$fb_g_code[] = "<script type='text/javascript'> gtag('event', 'logout', {'method': 'Logout'}); </script>";
			}
			
			return join($fb_g_code);
		}
	}
	public function signupbefore() {
		$this->session->data['compgafad_signup_flag'] = 1;
	}
	public function signup() {
		$fb_g_code = array();
		if($this->status && isset($this->session->data['compgafad_signup_flag'])) {
			unset($this->session->data['compgafad_signup_flag']);
 			$evname = 'CompleteRegistration'; $evid = rand();			
			
			if($this->setting['gmid']) { 
				$fb_g_code[] = "<script type='text/javascript'> gtag('event', 'sign_up', {'method': 'Signup'}); </script>";
			}
			$fb_g_code[] = $this->get_adw(1.0, 'sign_up');
			
			return join($fb_g_code);
		}
	}
	public function contact() {
		$fb_g_code = array();
		if($this->status) {
 			$evname = 'Contact';
			$evid = rand();
			
			if($this->setting['gmid']) { 
				$fb_g_code[] = "<script type='text/javascript'> gtag('event', 'contact', {'event_category': 'contact', 'event_label': 'contact'}); </script>";
			}
			
			return join($fb_g_code);
		}
	}
	public function addtocart() {
		$json['script'] = false;
		if ($this->status && isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) {
			$pid = (int)$this->request->post['product_id'];
			$quantity = (int)$this->request->post['quantity'];
			
			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = array();
			}
				
			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($pid);
			
			if ($pinfo) {
				$json = array();
				
				if ((int)$quantity >= $pinfo['minimum']) {
					$quantity = (int)$this->request->post['quantity'];
				} else {
					$quantity = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				}
				
				if(substr(VERSION,0,3)=='4.0') {
					$product_options = $this->model_catalog_product->getOptions($pid);
				} else {
					$product_options = $this->model_catalog_product->getProductOptions($pid);
				}
	
				foreach ($product_options as $product_option) {
					if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
						$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
					}
				}

				if (!$json) {
					// do add to cart
					$option_price = 0;
	
					foreach ($option as $product_option_id => $value) {
						$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$pid . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
						if ($option_query->num_rows) {
							if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
								if ($option_value_query->num_rows) {
									if ($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-') {
										$option_price -= $option_value_query->row['price'];
									}	
								}
							} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
								foreach ($value as $product_option_value_id) {
									$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
									if ($option_value_query->num_rows) {
										if ($option_value_query->row['price_prefix'] == '+') {
											$option_price += $option_value_query->row['price'];
										} elseif ($option_value_query->row['price_prefix'] == '-') {
											$option_price -= $option_value_query->row['price'];
										}
									}
								}
							}
						}
					}
					
					$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
					
					$pinfo['quantity'] = $quantity;
					
					$fb_g_value = $this->tax->calculate($pinfo['price'] + $option_price, $pinfo['tax_class_id'], $this->config->get('config_tax')) * $quantity;
										
					$evname = 'AddToCart'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo);
					
					$json['script'] = $this->get_gevent('add_to_cart', 'ecommerce', 'add_to_cart', $fb_g_pdata, $fb_g_value);
					
					$json['script'] .= $this->get_adw($fb_g_value, 'add_to_cart');
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function addtowishlist() {
		$json['script'] = false;
		if($this->status && isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) {	
			$pid = (int)$this->request->post['product_id'];
			$quantity = (int)$this->request->post['quantity'];	
			
			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($pid);
			
			if ($pinfo) {
				if ((int)$quantity >= $pinfo['minimum']) {
					$quantity = (int)$this->request->post['quantity'];
				} else {
					$quantity = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				}
				
				$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
					
				$pinfo['quantity'] = $quantity;
				
				$fb_g_value = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax')) * $quantity;
				
				$evname = 'AddToWishlist'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo);
				
				$json['script'] = $this->get_gevent('add_to_wishlist', 'ecommerce', htmlspecialchars_decode(strip_tags($pinfo['name'])), $fb_g_pdata, $fb_g_value);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function viewcont() {
		$fb_g_code = array();
		if($this->status && isset($this->request->get['product_id'])) { 
   			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			
			if($pinfo) {
				$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
				$pinfo['quantity'] = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				
				$fb_g_value = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax'));
				
				$evname = 'ViewContent'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo); $flag_nm = 1;
				
				$fb_g_code[] = $this->get_gevent('view_item', 'ecommerce', htmlspecialchars_decode(strip_tags($pinfo['name'])), $fb_g_pdata, $fb_g_value);
				
				return join($fb_g_code);
			}
		}
	}
	public function viewcategory() {
		$fb_g_code = array();
		if($this->status && !empty($this->request->get['path'])) {			
			$this->load->model('catalog/product');
			
			$path = '';
 			$parts = explode('_', (string)$this->request->get['path']);
 			$category_id = (int)end($parts);
			$catname = $this->getcatnamefromID($category_id);
			
			$pinfo = array();
			$ptotal = array();
			$result = $this->getcategory($category_id);

			if($result) {
				foreach($result as $rs) {
					$pid = $rs['product_id'];
					$pdata = $this->model_catalog_product->getProduct($pid);
					if(!$pdata) {continue;}
					$pdata['price'] = $pdata['special'] ? $pdata['special'] : $pdata['price'];
 					$pdata['quantity'] = $pdata['minimum'] ? $pdata['minimum'] : 1;
					
					$pinfo[$pid] = $pdata;
					$pinfo[$pid]['price'] = $pdata['price'];
 					$ptotal[] = $this->tax->calculate($pdata['price'], $pdata['tax_class_id'], $this->config->get('config_tax'));
				}
				
				$fb_g_value = array_sum($ptotal);
				
				$evname = 'ViewCategory'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = $pinfo; $flag_nm = 1;				
				
				$fb_g_code[] = $this->get_gevent('view_category', 'ecommerce', htmlspecialchars_decode(strip_tags($catname)), $fb_g_pdata, $fb_g_value);
				
				return join($fb_g_code);
			}
		}
	}
	public function search() {
		$fb_g_code = array();
		if($this->status && !empty($this->request->get['search'])) {
			$srchstr = $this->request->get['search'];
			$this->load->model('catalog/product');
			
			$pinfo = array();
			$pinfo = array();
			$result = $this->getsearchrs($this->request->get['search']);
			
			if($result) {
				foreach($result as $rs) {
					$pid = $rs['product_id'];
					$pdata = $this->model_catalog_product->getProduct($pid);
					if(!$pdata) {continue;}
					$pdata['price'] = $pdata['special'] ? $pdata['special'] : $pdata['price'];
 					$pdata['quantity'] = $pdata['minimum'] ? $pdata['minimum'] : 1;
					
					$pinfo[$pid] = $pdata;
					$pinfo[$pid]['price'] = $pdata['price'];
 					$ptotal[] = $this->tax->calculate($pdata['price'], $pdata['tax_class_id'], $this->config->get('config_tax'));
				}
				
				$fb_g_value = array_sum($ptotal);
				
				$evname = 'Search'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $pinfo; $flag_nm = 1;
				
				$fb_g_code[] = $this->get_gevent('search', 'ecommerce', htmlspecialchars_decode(strip_tags($srchstr)), $fb_g_pdata, $fb_g_value, $srchstr);
 				
				return join($fb_g_code);
			}
		}
	}
	public function remove_from_cart() {
		$fb_g_code = array();
		if (isset($this->request->post['key']) || isset($this->request->get['remove'])) {
			foreach($this->cart->getProducts() as $cartprod) {
				if((isset($cartprod['key']) && $cartprod['key'] == $this->request->get['remove']) || 
					(isset($cartprod['key']) && $cartprod['key'] == $this->request->post['key']) || 
					(isset($cartprod['cart_id']) && $cartprod['cart_id'] == $this->request->post['key'])) 
				{
					$fb_g_value = $cartprod['total'];
					
					$evname = 'RemoveFromCart'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = array($cartprod);					
					
					$fb_g_code[] = $this->get_gevent('remove_from_cart', 'ecommerce', 'remove_from_cart', $fb_g_pdata, $fb_g_value);
					
					$this->session->data['event_removecart_code'] = join($fb_g_code);
				}
			}
		}
	}
	public function viewcart() {
		$fb_g_code = array();
		if($this->status && $this->cart->hasProducts()) {
			$fb_g_value = $this->cart->getTotal();
			
			$evname = 'ViewCart'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = $this->cart->getProducts(); 
			
			$fb_g_code[] = $this->get_gevent('view_cart', 'ecommerce', 'view_cart', $fb_g_pdata, $fb_g_value);
 			
			if(isset($this->session->data['event_removecart_code'])) {
				$fb_g_code[] = $this->session->data['event_removecart_code'];
				unset($this->session->data['event_removecart_code']);
			}
			
			return join($fb_g_code);
		}
	}
	public function beginchk() {
		$fb_g_code = array();
		if($this->status && $this->cart->hasProducts()) {
			$fb_g_value = $this->cart->getTotal();
			
			$evname = 'InitiateCheckout'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $this->cart->getProducts(); 
			
			$fb_g_code[] = $this->get_gevent('begin_checkout', 'ecommerce', 'begin_checkout', $fb_g_pdata, $fb_g_value);
			
			$fb_g_code[] = $this->get_adw($fb_g_value, 'begin_checkout');
			
			return join($fb_g_code);
		}
	}
	public function purchasebefore() {
		if(isset($this->session->data['order_id'])) { 
			$this->session->data['compgafad_order_id'] = $this->session->data['order_id'];
		} else if(isset($this->session->data['xsuccess_order_id'])) { 
			$this->session->data['compgafad_order_id'] = $this->session->data['xsuccess_order_id'];
		} else {
			$this->session->data['compgafad_order_id'] = $this->getorderid();
		}
	}
	public function purchase() {
		$this->purchasebefore();
		$fb_g_code = array();
		if($this->status && !empty($this->session->data['compgafad_order_id'])) {
			$this->set_ord_flg($this->session->data['compgafad_order_id']);
			
			$order_id = $this->session->data['compgafad_order_id'];
			unset($this->session->data['compgafad_order_id']);			
			
			$this->load->model('checkout/order');
 			$orderdata = $this->model_checkout_order->getOrder($order_id);
 			$orderdata['order_products'] = $this->getorderproduct($order_id); 
			$orderdata['order_tax'] = $this->getordertax($order_id);
			$orderdata['order_shipping'] = $this->getordershipping($order_id);
			$orderdata['order_coupon'] = $this->getordercoupon($order_id);
			
			$fb_g_value = $orderdata['total'];
			
			$evname = 'Purchase'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $orderdata['order_products']; $flag_nm = 0; $srchstr = '';
			
			$fb_g_code[] = $this->get_gevent('purchase', 'ecommerce', 'purchase', $fb_g_pdata, $fb_g_value, $srchstr, $orderdata);
			
			$fb_g_code[] = $this->get_adwconv($orderdata);
			
			return join($fb_g_code);
		}
	}
	
	// Helpers	
	public function get_gevent($evname, $evcat = '', $evlbl = 0, $fb_g_pdata = array(), $fb_g_value = 0, $srchstr = '', $orderdata = array()) {
 		if($this->status && $this->setting['gmid']) {			
			$cnt = -1; 
			$gitems = array();							
					
 			if($fb_g_pdata) { 
				foreach ($fb_g_pdata as $pinfo) {
					$cnt++;
					if(isset($pinfo['tax_class_id'])) {
						$pinfo['price'] = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax'));
					}
					if(isset($pinfo['tax'])) {
						$pinfo['price'] = $pinfo['price'] + $pinfo['tax'];
					}
					$catname = $this->getcatname($pinfo['product_id']);
					$brand_name = $this->getbrandname($pinfo['product_id']);				
					
					$gitems[$cnt] = array(
						'affiliation' => htmlspecialchars_decode(strip_tags($this->getstorename())),
						'id' => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
						'name' => htmlspecialchars_decode(strip_tags($pinfo['name'])),
						'item_id' => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
						'item_name' => htmlspecialchars_decode(strip_tags($pinfo['name'])),
						'item_brand' => htmlspecialchars_decode(strip_tags($brand_name)),
						'item_category' => htmlspecialchars_decode(strip_tags($catname)),
						'currency' => $this->session->data['currency'],
						'price' => $this->getcurval($pinfo['price']),
						'quantity' => $pinfo['quantity'],
						'index' => $cnt,
						'list_position' => $cnt,
					);
				}
			}
			
			$gtag = array(
				'affiliation' => htmlspecialchars_decode(strip_tags($this->getstorename())),
				'event_category' => (!empty($catname) && $evcat) ? $evcat : $catname,
				'event_label' => $evlbl,
				'currency' => $this->session->data['currency'],
				'value' => $this->getcurval($fb_g_value),
 			);
			
			if($orderdata) {			
				$gtag['transaction_id'] = $orderdata['order_id'];
				$gtag['tax'] = $orderdata['order_tax'];
				$gtag['shipping'] = $orderdata['order_shipping'];			
			}
			
			if($gitems) { $gtag['items'] = $gitems; }
			
			if(!empty($srchstr)) { $gtag['search_term'] = htmlspecialchars_decode(strip_tags($srchstr)); }
						
			if(isset($this->session->data['coupon']) && $evlbl == 'ecommerce') {
				$gtag['coupon'] = $this->session->data['coupon'];
			}
			if(isset($orderdata['order_coupon']['couponcode'])) {
				$gtag['coupon'] = $orderdata['order_coupon']['couponcode'];
			}
 			
			return "<script type='text/javascript'> gtag('event', '".$evname."', ".json_encode($gtag,true)."); </script>";			
		}
		return '';
	}
	public function get_adw($val, $evname) { 
		$adwid = 0;
		$adw_currency = $this->session->data['currency'];
		
		if($this->setting['signup_adwid'] && $evname == 'sign_up') { 
			$adwid = $this->setting['signup_adwid'];
			$adwlbl = $this->setting['signup_adwlbl'];
		} else if($this->setting['addtc_adwid'] && $evname == 'add_to_cart') {
			$adwid = $this->setting['addtc_adwid'];
			$adwlbl = $this->setting['addtc_adwlbl'];
		} else if($this->setting['bgnchk_adwid'] && $evname == 'begin_checkout') {
			$adwid = $this->setting['bgnchk_adwid'];
			$adwlbl = $this->setting['bgnchk_adwlbl'];
		}	
		
		if($adwid) {
			return "<script type=\"text/javascript\"> gtag('event', 'conversion', {'send_to': '$adwid/$adwlbl', 'event_name': '$evname', 'value': '$val', 'currency': '$adw_currency' }); </script>";
		}
			
		return '';
	}
	public function get_adwconv($orderdata) {						
		$adwid = $this->setting['prch_adwid'];
		$adwlbl = $this->setting['prch_adwlbl'];
			
		$code = '';
		
		if($this->setting['prch_adwid']) {
			$code .= $this->get_userdata($orderdata);
			
			$code .= $this->get_varenh_data($orderdata);
			
			$code .= "<script type=\"text/javascript\"> gtag('event', 'conversion', {'send_to': '$adwid/$adwlbl', 'transaction_id': '".$orderdata['order_id']."', 'value': '".$this->getcurval($orderdata['total'])."', 'currency': '".$this->session->data['currency']."' }); </script>";
			
			$code .= $this->get_convcartdata($orderdata);
		}
		
		return $code;
	}
	public function get_google_consent_code() {
		$fb_g_code = array();
		
		if($this->setting['cmv2']) {
			$cmv2_flag = (isset($_COOKIE['cookie_consent_user_accepted']) && $_COOKIE['cookie_consent_user_accepted'] == 1) ? 1 : 0;
			
			if($cmv2_flag == 0) {
				$fb_g_code[] = "<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				
				gtag('consent', 'default', {
					'ad_storage': 'denied',
					'ad_user_data': 'denied',
					'ad_personalization': 'denied',
					'analytics_storage': 'denied',
					'functionality_storage': 'denied',
					'personalization_storage': 'denied',
					'security_storage': 'denied'
				});
				</script>";
			}
			if($cmv2_flag == 1) {
				$fb_g_code[] = "<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				
				gtag('consent', 'update', {
					'ad_storage': 'granted',
					'ad_user_data': 'granted',
					'ad_personalization': 'granted',
					'analytics_storage': 'granted',
					'functionality_storage': 'granted',
					'personalization_storage': 'granted',
					'security_storage': 'granted'
				});
				</script>";
			}
			$fb_g_code[] = "<script>
			function compgafad_consentGrantedAdStorage() {
				gtag('consent', 'update', {
					'ad_storage': 'granted',
					'ad_user_data': 'granted',
					'ad_personalization': 'granted',
					'analytics_storage': 'granted',
					'functionality_storage': 'granted',
					'personalization_storage': 'granted',
					'security_storage': 'granted'
				});

				var ckdate = new Date();
				ckdate.setTime(ckdate.getTime() + (30*24*60*60*1000));					
				document.cookie = 'cookie_consent_user_accepted=1;expires='+ckdate.toUTCString()+'; path=/';
			}
			
			$(document).delegate('.cc-allow', 'click', function() {
				compgafad_consentGrantedAdStorage();
				console.log('trigger - compgafad_consentGrantedAdStorage');
			});
			$(document).delegate('.cc-deny', 'click', function() {
				compgafad_consentGrantedAdStorage();
				console.log('trigger - compgafad_consentGrantedAdStorage');
			});
			</script>";
		}
		return join($fb_g_code);
	}
	public function get_userdata($orderdata) {
		$adw_enh_data = array();
		if(!empty($orderdata['email'])) { $adw_enh_data['sha256_email_address'] = hash('sha256', $orderdata['email']); }
		if(!empty($orderdata['telephone'])) { $adw_enh_data['sha256_phone_number'] = hash('sha256', $orderdata['telephone']); }
		if(!empty($orderdata['firstname'])) { $adw_enh_data['address']['sha256_first_name'] = hash('sha256', $orderdata['firstname']); }
		if(!empty($orderdata['lastname'])) { $adw_enh_data['address']['sha256_last_name'] = hash('sha256', $orderdata['lastname']); }
		if(!empty($orderdata['shipping_address_1'])) { $adw_enh_data['address']['street'] = $orderdata['shipping_address_1']; }
		if(!empty($orderdata['shipping_city'])) { $adw_enh_data['address']['city'] = $orderdata['shipping_city']; }
		if(!empty($orderdata['shipping_zone'])) { $adw_enh_data['address']['region'] = $orderdata['shipping_zone']; }
		if(!empty($orderdata['shipping_postcode'])) { $adw_enh_data['address']['postal_code'] = $orderdata['shipping_postcode']; }
		if(!empty($orderdata['shipping_country'])) { $adw_enh_data['address']['country'] = $orderdata['shipping_country']; }
		if($adw_enh_data) { 
			return "<script>gtag('set', 'user_data', ".json_encode($adw_enh_data, true).");</script>"; 
		}
	}
	public function get_varenh_data($orderdata) {
		$adw_enh_data = array();
		if(!empty($orderdata['email'])) { $adw_enh_data['email'] = $orderdata['email']; }
		if(!empty($orderdata['telephone'])) { $adw_enh_data['phone_number'] = $orderdata['telephone']; }
		if(!empty($orderdata['firstname'])) { $adw_enh_data['first_name'] = $orderdata['firstname']; }
		if(!empty($orderdata['lastname'])) { $adw_enh_data['last_name'] = $orderdata['lastname']; }
		if(!empty($orderdata['shipping_address_1'])) { $adw_enh_data['home_address']['street'] = $orderdata['shipping_address_1']; }
		if(!empty($orderdata['shipping_city'])) { $adw_enh_data['home_address']['city'] = $orderdata['shipping_city']; }
		if(!empty($orderdata['shipping_zone'])) { $adw_enh_data['home_address']['region'] = $orderdata['shipping_zone']; }
		if(!empty($orderdata['shipping_postcode'])) { $adw_enh_data['home_address']['postal_code'] = $orderdata['shipping_postcode']; }
		if(!empty($orderdata['shipping_country'])) { $adw_enh_data['home_address']['country'] = $orderdata['shipping_country']; }
		
		if($adw_enh_data) { 
			return "<script>var enhanced_conversion_data = ".json_encode($adw_enh_data, true).";</script>"; 
		}
		return '';			
	}
	public function get_convcartdata($orderdata) {
		$adwid = $this->setting['prch_adwid'];
		$adwlbl = $this->setting['prch_adwlbl'];
		
		$items = array();
		if($orderdata['order_products']) { 
			foreach ($orderdata['order_products'] as $pinfo) {
				if(isset($pinfo['tax_class_id'])) {
					$pinfo['price'] = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax'));
				}
				if(isset($pinfo['tax'])) {
					$pinfo['price'] = $pinfo['price'] + $pinfo['tax'];
				}				
				$items[] = array(
					'id' => $pinfo['product_id'],
					'price' => $this->getcurval($pinfo['price']),
					'quantity' => $pinfo['quantity'],
				);
			}
		}
		
		return "<script type=\"text/javascript\"> gtag('event', 'purchase', {'send_to': '$adwid/$adwlbl', 'transaction_id': '".$orderdata['order_id']."', 'value': '".$this->getcurval($orderdata['total'])."', 'currency': '".$this->session->data['currency']."', 'discount': '".(isset($orderdata['order_coupon']['discount']) ? $orderdata['order_coupon']['discount'] : 0)."', 'aw_merchant_id': '".$this->setting['aw_merchant_id']."', 'aw_feed_country': '".$this->setting['aw_feed_country']."', 'aw_feed_language': '".$this->setting['aw_feed_language']."', 'aw_feed_label': '".$this->setting['aw_feed_label']."', 'items': ".json_encode($items, true)." }); </script>";
	}
	
	public function get_page_url() {
		$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "https://";		 
		$url.= $_SERVER['HTTP_HOST'];
		$url.= $_SERVER['REQUEST_URI'];
		return $url;
	}
	public function set_ord_flg($order_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` set compgafad_ordflag = 1 where order_id = '" . (int)$order_id . "' ");		
	}
	public function getorderid() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE compgafad_ordflag = 0 and date(date_added) >= curdate() and order_status_id > 0 AND ip like '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' order by date_added desc limit 1");		
		if($query->num_rows) {
			return $query->row['order_id'];
		}
		return 0;
	}
	public function getProduct($pid) {
		if($pid) { 

			$query = $this->db->query("SELECT DISTINCT *, pd.name, pd.meta_description, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$pid . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
			
			if ($query->num_rows) {
				$query->row['price'] = $query->row['discount'] ? $query->row['discount'] : $query->row['price'];
				return $query->row;
			} else {
				return false;
			}
		}
		return false;
	}
	public function getstorename() {
		$stq = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '".(int)$this->config->get('config_store_id')."' ");
		return htmlspecialchars_decode(strip_tags(isset($stq->row['name']) ? $stq->row['name'] : $this->config->get('config_name')));
	}
	public function getcatname($product_id) {
		if($product_id) { 
			$query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description cd 
			INNER JOIN " . DB_PREFIX . "product_to_category pc ON pc.category_id = cd.category_id 
			WHERE 1 AND pc.product_id = '".$product_id."' AND cd.language_id = '". (int)$this->config->get('config_language_id') ."' limit 1");
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		} 
		return '';
	}
	public function getcatnamefromID($category_id) {
		if($category_id) { 
			$query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description cd
			WHERE 1 AND category_id = '".$category_id."' AND cd.language_id = '". (int)$this->config->get('config_language_id') ."' limit 1");
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		} 
		return '';
	}
	public function getbrandname($pid) {
		if($pid) { 
			$query = $this->db->query("SELECT name from " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "product p on m.manufacturer_id = p.manufacturer_id WHERE 1 AND p.product_id = ".$pid);
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		}
		return '';
	}
	public function getprorel($pid) {
		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr 
		LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		WHERE pr.product_id = '" . (int)$pid . "' AND p.status = '1' 
		AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		return $q->rows;
	}
	public function getcategory($category_id) {
		$sql = "SELECT p.product_id FROM " . DB_PREFIX . "product p 
		LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
		WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND p2c.category_id = '" . (int)$category_id . "'
		AND p.status = '1' AND p.date_available <= NOW() 
		AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		$sql .= " GROUP BY p.product_id LIMIT 5";
		
		$query = $this->db->query($sql);
			
		return $query->rows;
	}
	public function getsearchrs($srchstr) {
		$filter_data = array('filter_name' => $srchstr, 'start' => 0, 'limit' => 5);
		
		$sql = "SELECT p.product_id FROM " . DB_PREFIX . "product p 
		LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND p.status = '1' AND p.date_available <= NOW() 
		AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		$data['filter_name'] = $srchstr;
		if (!empty($data['filter_name'])) {
			$sql .= " AND ( pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
			$sql .= " OR LCASE(p.model) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";

			$sql .= ")";
		}
		$sql .= " GROUP BY p.product_id LIMIT 5";
		
		$query = $this->db->query($sql);
			
		return $query->rows;
	}
	public function getorderproduct($order_id) {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' ");
 		return $query->rows;
	}
	public function getordertax($order_id) {
 		$q = $this->db->query("SELECT sum(value) as taxval FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'tax'");
		if (isset($q->row['taxval']) && $q->row['taxval']) {
			return $this->getcurval($q->row['taxval']);
		} 
		return 0;
	}
	public function getordershipping($order_id) {
 		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'shipping'");
		if (isset($q->row['value']) && $q->row['value']) {
			return $this->getcurval($q->row['value']);
		} 
		return 0;
	}
	public function getordercoupon($order_id) {
 		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'coupon'");
		if (isset($q->row['value']) && abs($q->row['value'])) {
			$couponcode = explode("(", $q->row['title']);
			return array("couponcode" => str_replace(")","",$couponcode[1]), "discount" => $this->getcurval(abs($q->row['value'])));
		} 
		return false;
	}
	public function getcurval($taxprc) {
		return round($this->currency->format($taxprc, $this->session->data['currency'], false, false),2);
	}
		
	public function getSetting() {		
		$storeid = (int)$this->config->get('config_store_id');
		$langid = (int)$this->config->get('config_language_id');
 		$setting = $this->config->get($this->modname.'_setting');
				
 		$setting['status'] = (!isset($setting[$storeid]['status'])) ? false : $setting[$storeid]['status'];
		$setting['cmv2'] = (!isset($setting[$storeid]['cmv2'])) ? false : $setting[$storeid]['cmv2'];
		$setting['gmid'] = (!isset($setting[$storeid]['gmid'])) ? '' : $setting[$storeid]['gmid'];
		$setting['prch_adwid'] = (!isset($setting[$storeid]['prch_adwid'])) ? '' : $setting[$storeid]['prch_adwid'];
		$setting['prch_adwlbl'] = (!isset($setting[$storeid]['prch_adwlbl'])) ? '' : $setting[$storeid]['prch_adwlbl'];
		$setting['bgnchk_adwid'] = (!isset($setting[$storeid]['bgnchk_adwid'])) ? '' : $setting[$storeid]['bgnchk_adwid'];
		$setting['bgnchk_adwlbl'] = (!isset($setting[$storeid]['bgnchk_adwlbl'])) ? '' : $setting[$storeid]['bgnchk_adwlbl'];
		$setting['addtc_adwid'] = (!isset($setting[$storeid]['addtc_adwid'])) ? '' : $setting[$storeid]['addtc_adwid'];
		$setting['addtc_adwlbl'] = (!isset($setting[$storeid]['addtc_adwlbl'])) ? '' : $setting[$storeid]['addtc_adwlbl'];
		$setting['signup_adwid'] = (!isset($setting[$storeid]['signup_adwid'])) ? '' : $setting[$storeid]['signup_adwid'];
		$setting['signup_adwlbl'] = (!isset($setting[$storeid]['signup_adwlbl'])) ? '' : $setting[$storeid]['signup_adwlbl'];
		$setting['aw_merchant_id'] = (!isset($setting[$storeid]['aw_merchant_id'])) ? '' : $setting[$storeid]['aw_merchant_id'];
		$setting['aw_feed_country'] = (!isset($setting[$storeid]['aw_feed_country'])) ? '' : $setting[$storeid]['aw_feed_country'];
		$setting['aw_feed_language'] = (!isset($setting[$storeid]['aw_feed_language'])) ? '' : $setting[$storeid]['aw_feed_language'];
		$setting['aw_feed_label'] = (!isset($setting[$storeid]['aw_feed_label'])) ? '' : $setting[$storeid]['aw_feed_label'];
		
 		return $setting;		
	}
	public function loadjscss() {
		if($this->status) {
			$ocstr = substr(VERSION,0,3)=='4.0' ? 'extension/compgafad/' : '';
			$this->document->addScript($ocstr.'catalog/view/javascript/compgafad.js?vr='.rand());
			//$this->document->addStyle($ocstr.'catalog/view/javascript/compgafad.css?vr='.rand());			
		}			
	}
}
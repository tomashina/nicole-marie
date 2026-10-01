<?php  
#this file is common to all 2xxx and 3xxx
class ModelExtensionModuleHbLowstock extends Controller {	

	public function stock_validation($order_id){
		$alert_type = false;
		$threshold = (int)$this->config->get('hb_lowstock_qty');
		$eligible_order_statuses = $this->config->get('hb_lowstock_order_statuses') ? $this->config->get('hb_lowstock_order_statuses') : array();

		$order_status_id = $this->getOrderStatus($order_id);

		$ordered_products 	= $this->getOrderProducts($order_id);
		if ($ordered_products && in_array($order_status_id, $eligible_order_statuses)) {
			foreach ($ordered_products as $ordered_product){
				$product_id 		= $ordered_product['product_id'];
				$order_product_id 	= $ordered_product['order_product_id'];
				
				if ($this->config->get('hb_lowstock_opov_validation')) {
					/********** THIS CODE SENDS EMAIL BY EACH PRODUCT OPTION - CODE STARTS **********/
					//if this ordered product has any options ordered
					$order_product_options = $this->getOrderProductOption($order_product_id);

					if (!empty($order_product_options)){
						foreach ($order_product_options as $order_product_option){
							$product_option_value_id = $order_product_option['product_option_value_id'];
							
							if ($product_option_value_id != 0) {
								if ($this->getProductOptionValueQty($product_option_value_id)){
									//lets first check if any option qty is OOS
									if ($this->isAnyProductOptionValueQtyOos($product_option_value_id)){
										$alert_type = 'oos';
									}else{
										$alert_type = 'ls';
									}

									if ($alert_type == 'oos') {
										$alert_data = array(
											'product_id' 	=> $product_id,
											'template_id'	=> (int)$this->config->get('hb_lowstock_nostock_template'),
											'email_enabled'	=> ($this->config->get('hb_lowstock_nostock_status')) ? true:false,
											'to'			=> $this->config->get('hb_lowstock_nostock_to'),
											'preview'		=> false
										);
				
										$this->alert($alert_data);
									}else{
										$alert_data = array(
											'product_id' 	=> $product_id,
											'template_id'	=> (int)$this->config->get('hb_lowstock_template'),
											'email_enabled'	=> ($this->config->get('hb_lowstock_status')) ? true:false,
											'to'			=> $this->config->get('hb_lowstock_to'),
											'preview'		=> false
										);
										
										$this->alert($alert_data);
									}
								}
							}
						}
					}else{
						$product_data = $this->getProductData($product_id);
						$product_qty 	= (int)$product_data['quantity'];
						$product_threshold	= (int)$product_data['hb_p_threshold'];

						$product_threshold = ($product_threshold == 0) ? $threshold : $product_threshold;

						if ($product_qty > 0 && $product_qty <= $product_threshold){
							$alert_type = 'ls';
						}elseif ($product_qty <= 0) {
							$alert_type = 'oos';
						}else{
							$alert_type = false;
						}

						$this->disable_product($product_id);
					
						if ($alert_type) {
							if ($alert_type == 'oos') {
								$alert_data = array(
									'product_id' 	=> $product_id,
									'template_id'	=> (int)$this->config->get('hb_lowstock_nostock_template'),
									'email_enabled'	=> ($this->config->get('hb_lowstock_nostock_status')) ? true:false,
									'to'			=> $this->config->get('hb_lowstock_nostock_to'),
									'preview'		=> false
								);

								$this->alert($alert_data);
							}else{
								$alert_data = array(
									'product_id' 	=> $product_id,
									'template_id'	=> (int)$this->config->get('hb_lowstock_template'),
									'email_enabled'	=> ($this->config->get('hb_lowstock_status')) ? true:false,
									'to'			=> $this->config->get('hb_lowstock_to'),
									'preview'		=> false
								);
								
								$this->alert($alert_data);
							}
						}
					}	
					
					/********** THIS CODE SENDS EMAIL BY EACH PRODUCT OPTION - CODE ENDS **********/
				} else {	
					/********** THIS CODE SENDS EMAIL BY EACH PRODUCT IN THE ORDER - CODE STARTS **********/
					if ($this->getProductOptionQty($product_id, $threshold)){
						//lets first check if any option qty is OOS
						if ($this->isAnyProductOptionQtyOos($product_id)){
							$alert_type = 'oos';
						}else{
							$alert_type = 'ls';
						}
					}else{
						$product_data = $this->getProductData($product_id);
						$product_qty 	= (int)$product_data['quantity'];
						$product_threshold		= (int)$product_data['hb_p_threshold'];

						$product_threshold = ($product_threshold == 0) ? $threshold : $product_threshold;
						
						if ($product_qty > 0 && $product_qty <= $product_threshold){
							$alert_type = 'ls';
						}elseif ($product_qty <= 0) {
							$alert_type = 'oos';
						}else{
							$alert_type = false;
						}
					}
					

					$this->disable_product($product_id);
					
					if ($alert_type) {
						if ($alert_type == 'oos') {
							$alert_data = array(
								'product_id' 	=> $product_id,
								'template_id'	=> (int)$this->config->get('hb_lowstock_nostock_template'),
								'email_enabled'	=> ($this->config->get('hb_lowstock_nostock_status')) ? true:false,
								'to'			=> $this->config->get('hb_lowstock_nostock_to'),
								'preview'		=> false
							);

							$this->alert($alert_data);
						}else{
							$alert_data = array(
								'product_id' 	=> $product_id,
								'template_id'	=> (int)$this->config->get('hb_lowstock_template'),
								'email_enabled'	=> ($this->config->get('hb_lowstock_status')) ? true:false,
								'to'			=> $this->config->get('hb_lowstock_to'),
								'preview'		=> false
							);
							
							$this->alert($alert_data);
						}
					}

					/********** THIS CODE SENDS EMAIL BY EACH PRODUCT IN THE ORDER - CODE ENDS **********/
				}
			}
		}
	}

	public function getOrderStatus($order_id){
		$query = $this->db->query("SELECT order_status_id FROM `" . DB_PREFIX . "order` WHERE order_id = '".(int)$order_id."' LIMIT 1");
		return $query->row['order_status_id'];
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	} 

	public function getOrderProductOption($order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_product_id = '" . (int)$order_product_id . "'");
		return $query->rows;
	}

	public function getProductData($product_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE product_id = '".(int)$product_id."' LIMIT 1");
		return $query->row;
	}

	public function getProductOptionQty($product_id, $threshold){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '".(int)$product_id."' AND quantity <= hb_pov_threshold");
		if ($query->rows){
			return $query->rows;
		}else{
			return false;
		}
	}

	public function getProductOptionValueQty($product_option_value_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_value_id = '".(int)$product_option_value_id."' AND quantity <= hb_pov_threshold");
		if ($query->rows){
			return $query->rows;
		}else{
			return false;
		}
	}

	public function isAnyProductOptionQtyOos($product_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '".(int)$product_id."' AND quantity <= '0'");
		if ($query->rows){
			return true;
		}else{
			return false;
		}
	}

	public function isAnyProductOptionValueQtyOos($product_option_value_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_value_id = '".(int)$product_option_value_id."' AND quantity <= '0'");
		if ($query->rows){
			return true;
		}else{
			return false;
		}
	}

	public function getOptionQuantities($product_id){
		$query = $this->db->query("SELECT a.product_option_value_id, a.quantity, (SELECT name FROM ".DB_PREFIX."option_description WHERE option_id = a.option_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_name, (SELECT name FROM ".DB_PREFIX."option_value_description WHERE option_value_id = a.option_value_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_value_name FROM `".DB_PREFIX."product_option_value` a where a.product_id = '".(int)$product_id."' AND a.quantity <= '".(int)$this->config->get('hb_lowstock_qty')."'");
		if ($query->rows){
			return $query->rows;
		}else{
			return array();
		}
	}

	public function disable_product($product_id){
		if ($this->config->get('hb_lowstock_auto_disable') && $this->getProductData($product_id)['quantity'] <= 0) {
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET status = 0, date_modified = now() WHERE product_id = '".(int)$product_id."'");
		}
	}

	public function optionsHTML($product_id){
		$html = '';
		$options = $this->getOptionQuantities($product_id);
		if (!empty($options)){
			$html = '';
			$html .= '<table border="0" cellpadding="10" cellspacing="0">';
			foreach ($options as $option){
				$html .= '<tr>';
				$html .= '<td width="80%">';
				$html .= $option['option_name'].' - '.$option['option_value_name'];
				$html .= '</td>';
				$html .= '<td width="20%">';
				$html .= $option['quantity'];
				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}

		return $html;
	}

	public function alert($data){
		$product_id 	= $data['product_id'];
		$template_id 	= $data['template_id'];
		$email_enabled 	= $data['email_enabled'];
		$to 			= $data['to'];
		$preview 		= $data['preview'];

		$this->addlog('');//ADD EMPTY LINE
		$this->addlog('Initiated Alert for Product ID '.$product_id);

		if ($this->isExtensionInstalled('email_templates')){
			$this->load->model('extension/module/email_builder');
			$use_email_designer_app = true;
		}else{
			$use_email_designer_app = false;
		}

		//GET DATA
		$this->load->model('tool/image');

		$data['product_link'] = $this->url->link('product/product', 'product_id=' . $product_id);

		$product_info = $this->getProduct($product_id);

		if ($product_info){
			if ($product_info['image']) {
				$data['product_image'] = $this->model_tool_image->resize($product_info['image'], 200, 200);
			} else {
				$data['product_image'] = $this->model_tool_image->resize('placeholder.png', 200, 200);
			}

			$data['product_image'] = '<img src="'.$data['product_image'].'">';

			$data['product_options'] = $this->optionsHTML($product_id);

		}else{
			$this->addlog('No product data found for product ID '.$product_id);
			return false;
		}

		if ($email_enabled){
			if ($use_email_designer_app){
				$template_data 	= $this->model_extension_module_email_builder->builtTemplate($template_id);
			}else{
				$template_data 	= $this->getTemplateData($template_id);
			}

			$email_subject = $template_data['email_options']['email_subject'];
			$email_content = $template_data['email_content'];

			//replacing shortcode variables
			foreach ($data as $key => $value) {
				if (!is_array($value)) {
					$email_content 		= str_replace('{'.$key.'}',$value,$email_content);
					$email_subject 		= str_replace('{'.$key.'}',$value,$email_subject);
				}
			}

			//replacing shortcode variables
			foreach ($product_info as $key => $value) {
				if (!is_array($value)) {
					$email_content 		= str_replace('{'.$key.'}',$value,$email_content);
					$email_subject 		= str_replace('{'.$key.'}',$value,$email_subject);
				}
			}

			$email_data = array(          
				'to'              => $to,             
				'from'            => $template_data['email_options']['sender_email'],
				'store_name'      => $template_data['email_options']['sender_name'],
				'email_replyto'   => $template_data['email_options']['email_replyto'],
				'subject'         => $email_subject,
				'content'         => $email_content,
				'attachments'     => (isset($template_data['email_options']['email_attachments']))? $template_data['email_options']['email_attachments']: '',
				'bcc'             => $template_data['email_options']['email_bcc'],
				'template_id'     => $template_id,
				'store_id'        => $template_data['store_id'],
				'type'            => 'account', 
				'cron'            => false
			);
		
			if ($preview) {
				$this->addlog('Email Preview');
				return $email_content;
			}else{
				if ($use_email_designer_app){		
					$this->model_extension_module_email_builder->sendemail($email_data);
					$this->addlog("Email sent to ".$to." via template designer extn");
				}else{	 //else normal email
					$this->sendemail($email_data);
					$this->addlog("Email sent to ".$to);
				}
			}

		}else{
			$this->addlog('Template is disabled');
		}

	}

	public function addlog($text = ''){
		if ($this->config->get('hb_lowstock_worklog')){
			if (!file_exists(DIR_LOGS . 'huntbee_lowstock_logs')) {
				mkdir(DIR_LOGS . 'huntbee_lowstock_logs', 0777, true);
			}

			$file = DIR_LOGS . 'huntbee_lowstock_logs/lowstock_logs.txt';

			if (file_exists($file)) {
				$size = filesize($file);
				if ($size > 5242880){
					$handle = fopen($file, 'w+');
					fclose($handle);
				}
			}

			$fp = fopen($file, 'a');
			fwrite($fp, "\r\n".date('d-M-Y G:i:s A') . ' - ' .$text);
			fclose($fp);
		}
	}

	public function isExtensionInstalled($code){
		$query = $this->db->query("SELECT count(*) as total FROM `".DB_PREFIX."extension` WHERE `code` = '".$this->db->escape($code)."'");	
		if ($query->row['total'] > 0){
			return true;
		}else{
			return false;
		}
	}

	public function getStore($store_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . (int)$store_id . "'");
		return $query->row;
	}

	public function getTemplateData($template_id){
		$template['email_content'] = 'Template content not set!';
		$results = $this->db->query("SELECT * FROM `".DB_PREFIX."hb_build_template` WHERE `id` = '".(int)$template_id."'");
		$template_data = $results->row;
		
		if (!empty($template_data)) {
			//GET DATA
			$store_id 			= $template_data['store_id'];
			$draft_head 		= $template_data['draft_head'];
			$draft_body 		= $template_data['draft_body'];
			$loaded_template_id = $template_data['loaded_template_id'];
			$editor				= $template_data['editor'];
			
			$current_date 		= date('d-M-Y');
			$draft_body 		= str_replace('{server_date}',$current_date,$draft_body);
			
			$email_options 			= json_decode($template_data['email_options'], true);
			
			$this->load->model('setting/setting');
			$store_config 		= $this->model_setting_setting->getSetting('config', $store_id);
			$config_currency 	= isset($store_config['config_currency']) ? $store_config['config_currency'] : $this->config->get('config_currency');
			$config_tax 		= isset($store_config['config_tax']) ? $store_config['config_tax'] : $this->config->get('config_tax');
			$config_review_status = isset($store_config['config_review_status']) ? $store_config['config_review_status'] : $this->config->get('config_review_status');
			$config_seo_url 		= isset($store_config['config_seo_url']) ? $store_config['config_seo_url'] : $this->config->get('config_seo_url');
			
			$store_name 	= isset($store_config['config_name']) ? $store_config['config_name'] : $this->config->get('config_name');
			$store_ssl 		= isset($store_config['config_url']) ? $store_config['config_url'] : HTTPS_SERVER;
			$store_email 	= isset($store_config['config_email']) ? $store_config['config_email'] : $this->config->get('config_email');

			$draft_body 		= str_replace('{store_email}',$store_email,$draft_body);			
			$draft_body 		= str_replace('{store_name}',$store_name,$draft_body);
			$draft_body 		= str_replace('{store_url}',$store_ssl,$draft_body);
			
			$allowed_store_config = array('config_meta_title','config_meta_description','config_meta_keyword','config_name','config_owner','config_address','config_geocode','config_email','config_telephone','config_fax','config_image','config_open','config_comment');
			foreach ($store_config as $key => $value) {
				if (!is_array($value) & in_array($key,$allowed_store_config)) {
					$draft_body      					= str_replace('{'.$key.'}',$value,$draft_body);
					$draft_head      					= str_replace('{'.$key.'}',$value,$draft_head);
					$email_options['email_subject']     = str_replace('{'.$key.'}',$value,$email_options['email_subject']);
				}
			}
			
			$content = $draft_body;

			$template['store_id']      = $store_id;
			
			if ($email_options) {
				$content = str_replace('{email_subject}', $email_options['email_subject'], $content);
				$template['email_content'] = $content;
				$template['email_options'] = $email_options;
				$template['label']         = $template_data['template_label'];
			}
			
		}	
		return $template;
	}

	public function sendemail($data){
		error_reporting(0);
		$result = false;
		try{
			if (version_compare(VERSION,'2.0.1.1','<=' )) {
				$mail = new Mail($this->config->get('config_mail'));
				$mail->protocol = $this->config->get('config_mail_protocol');
			}else {
				if (version_compare(VERSION,'2.3.0.2','>' )) {
					$mail = new Mail($this->config->get('config_mail_engine'));
				}else{
					$mail = new Mail();
					$mail->protocol = $this->config->get('config_mail_protocol');
				}				
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');			
			}
						
			$mail->setTo($data['to']);
			$mail->setFrom($data['from']);
			$mail->setSender($data['store_name']);
			if ($data['email_replyto']){
				$mail->setReplyTo($data['email_replyto']);
			}
			$mail->setSubject(html_entity_decode($data['subject'], ENT_QUOTES, 'UTF-8'));
			$mail->setHtml(wordwrap($data['content'],50));

			$mail->send();
			if (!empty($data['bcc'])){
				$bccs = explode(',',$data['bcc']);
				foreach ($bccs as $bcc) {
					$mail->setTo($bcc);
					$mail->send();
				}
			}
			$result = 'Email sent to '.$data['to'];
		}catch (Exception $e){
			$this->addlog('Email failed sending to '.$data['to'].'. Issue: '.$e->getMessage());
			$result	= false;
		}

		return $result;
	}

	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'sku'              => $query->row['sku'],
				'upc'              => $query->row['upc'],
				'ean'              => $query->row['ean'],
				'jan'              => $query->row['jan'],
				'isbn'             => $query->row['isbn'],
				'mpn'              => $query->row['mpn'],
				'location'         => $query->row['location'],
				'quantity'         => $query->row['quantity'],
				'stock_status'     => $query->row['stock_status'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => $query->row['manufacturer_id'],
				'manufacturer'     => $query->row['manufacturer'],
				'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'special'          => $query->row['special'],
				'points'           => $query->row['points'],
				'length'           => $query->row['length'],
				'width'            => $query->row['width'],
				'height'           => $query->row['height'],
				'subtract'         => $query->row['subtract'],
				'minimum'          => $query->row['minimum'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}

}
?>
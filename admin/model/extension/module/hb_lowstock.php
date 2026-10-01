<?php
class ModelExtensionModuleHbLowstock extends Model {
	public function install(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."hb_build_template` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`template_label` varchar(250) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`loaded_template_id` int(11) NOT NULL,			
			`email_type_id` int(11) NOT NULL,
			`editor` VARCHAR(100) NOT NULL,
			`draft_head` text NOT NULL,
			`draft_body` mediumtext NOT NULL,
			`options` text NOT NULL,
			`email_options` text NOT NULL,
			`cross_selling_options` text NOT NULL,
			`date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`date_modified` datetime DEFAULT NULL,
			PRIMARY KEY (`id`)
		  ) DEFAULT CHARSET=utf8");

		$query_hb_pov_threshold = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_option_value` LIKE 'hb_pov_threshold'");
		if (!$query_hb_pov_threshold->num_rows){
			$this->db->query("ALTER TABLE `".DB_PREFIX."product_option_value` ADD `hb_pov_threshold` INT NOT NULL AFTER `weight_prefix`");
		}

		$query_hb_p_threshold = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'hb_p_threshold'");
		if (!$query_hb_p_threshold->num_rows){
			$this->db->query("ALTER TABLE `".DB_PREFIX."product` ADD `hb_p_threshold` INT NOT NULL AFTER `date_modified`");
		}

		$table_hb_email_types = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "hb_email_types'");
		if ($table_hb_email_types->num_rows){
			$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_email_types` WHERE email_type_id = 6");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "hb_email_types` (`email_type_id`, `label`, `mandatory`) VALUES ('6', 'Low Stock Alert', '1')");
			
			$this->load->model('localisation/language');
			$languages = $this->model_localisation_language->getLanguages();
			
			foreach ($languages as $language){
				$this->db->query("INSERT INTO `" . DB_PREFIX . "hb_email_types_description` (`email_type_id`,`description`,`language_id`) VALUES ('6', 'Low Stock Alert Emails', '".(int)$language['language_id']."')");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "modification WHERE `code` = 'huntbee_lowstock'");
		
		if ((version_compare(VERSION,'2.0.0.0','>=' )) and (version_compare(VERSION,'2.3.0.0','<' ))) {
			$ocmod_filename = 'ocmod_lowstock_2000_2200.txt';
			$ocmod_name = 'Low Stock Management [2000 - 2200]';
		}else if ((version_compare(VERSION,'2.3.0.0','>=' )) and (version_compare(VERSION,'3.0.0.0','<' ))) {
			$ocmod_filename = 'ocmod_lowstock_23xx.txt';
			$ocmod_name = 'Low Stock Management [23xx]';
		}else if (version_compare(VERSION,'3.0.0.0','>=' )) {
			$ocmod_filename = 'ocmod_lowstock_3xxx.txt';
			$ocmod_name = 'Low Stock Management [3.x.x.x]';
		}

		$ocmod_version = EXTENSION_VERSION;
		$ocmod_code = 'huntbee_lowstock';	
		$ocmod_author = 'HuntBee OpenCart Services';
		$ocmod_link = 'https://www.huntbee.com';

		$file = DIR_APPLICATION . 'view/template/extension/module/ocmod/'.$ocmod_filename;
		if (file_exists($file)) {
			$ocmod_xml = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
			$ocmod_xml = str_replace('{huntbee_version}',$ocmod_version,$ocmod_xml);
			$this->db->query("INSERT INTO " . DB_PREFIX . "modification SET code = '" . $this->db->escape($ocmod_code) . "', name = '" . $this->db->escape($ocmod_name) . "', author = '" . $this->db->escape($ocmod_author) . "', version = '" . $this->db->escape($ocmod_version) . "', link = '" . $this->db->escape($ocmod_link) . "', xml = '" . $this->db->escape($ocmod_xml) . "', status = '1', date_added = NOW()");
		}

		//events
		
		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'low_stock_alert'");
		if ((version_compare(VERSION,'2.0.0.0','>=' )) and (version_compare(VERSION,'2.2.0.0','<' ))) {	
			$this->db->query("INSERT INTO `" . DB_PREFIX . "event` (`code`, `trigger`, `action`) VALUES ('low_stock_alert', 'post.order.history.add', 'extension/module/hb_lowstock/event_lowstock_alert_21xx')");
		}else if ((version_compare(VERSION,'2.2.0.0','>=' )) and (version_compare(VERSION,'2.3.0.0','<' ))) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "event` (`code`, `trigger`, `action`, `status`) VALUES ('low_stock_alert', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/hb_lowstock/event_lowstock_alert_22xx', '1')");
		}else if ((version_compare(VERSION,'2.3.0.0','>=' )) and (version_compare(VERSION,'3.0.0.0','<' ))) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "event` (`code`, `trigger`, `action`, `status`, `date_added`) VALUES ('low_stock_alert', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/hb_lowstock/event_lowstock_alert', '1', now())");
		}else if (version_compare(VERSION,'3.0.0.0','>=' )) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "event` (`code`, `trigger`, `action`, `status`, `sort_order`) VALUES ('low_stock_alert', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/hb_lowstock/event_lowstock_alert', '1', '0')");
		}
		
		$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_build_template` WHERE email_type_id = 6");
		$this->install_sql();
		
		$this->db->query("INSERT INTO `".DB_PREFIX."setting` (`code`, `key`, `value`, `serialized`) VALUES ('module_hb_lowstock','module_hb_lowstock_status', '1','0')");

	}
	
	public function uninstall(){
		//$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "hb_build_template`"); 
		$this->db->query("DELETE FROM " . DB_PREFIX . "modification WHERE `code` = 'huntbee_lowstock'");
		
		$this->db->query("DELETE FROM `".DB_PREFIX."setting` WHERE `key` = 'module_hb_lowstock_status'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'low_stock_alert'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_email_types` WHERE email_type_id = 6");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_build_template` WHERE email_type_id = 6");

		$check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_option_value` LIKE 'hb_pov_threshold'");
		if ($check->num_rows){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` DROP COLUMN `hb_pov_threshold`");
		}

		$check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'hb_p_threshold'");
		if ($check->num_rows){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` DROP COLUMN `hb_p_threshold`");
		}
	}

	public function update(){
		$query_email_type_id = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "hb_build_template` LIKE 'email_type_id'");
		if (!$query_email_type_id->num_rows){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "hb_build_template` ADD `email_type_id` INT NOT NULL AFTER `loaded_template_id`");
		}

		$table_hb_email_types = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "hb_email_types'");
		if ($table_hb_email_types->num_rows){
			$this->db->query("DELETE FROM `" . DB_PREFIX . "hb_email_types` WHERE email_type_id = 6");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "hb_email_types` (`email_type_id`, `label`, `mandatory`) VALUES ('6', 'Low Stock Alert', '1')");
			
			$this->load->model('localisation/language');
			$languages = $this->model_localisation_language->getLanguages();
			
			foreach ($languages as $language){
				$this->db->query("INSERT INTO `" . DB_PREFIX . "hb_email_types_description` (`email_type_id`,`description`,`language_id`) VALUES ('6', 'Low Stock Alert Emails', '".(int)$language['language_id']."')");
			}
		}	
		
		$query_hb_pov_threshold = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_option_value` LIKE 'hb_pov_threshold'");
		if (!$query_hb_pov_threshold->num_rows){
			$this->db->query("ALTER TABLE `".DB_PREFIX."product_option_value` ADD `hb_pov_threshold` INT NOT NULL AFTER `weight_prefix`");
		}

		$query_hb_p_threshold = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'hb_p_threshold'");
		if (!$query_hb_p_threshold->num_rows){
			$this->db->query("ALTER TABLE `".DB_PREFIX."product` ADD `hb_p_threshold` INT NOT NULL AFTER `date_modified`");
		}

		$this->install_sql();

	}

	public function install_sql() {
		$file = DIR_APPLICATION . 'view/template/extension/module/sql/hb_lowstock.sql';

		if (file_exists($file)) {
			$lines = file($file);
	
			if ($lines) {
				$sql = '';
	
				foreach($lines as $line) {
					if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
						$sql .= $line;
	
						if (preg_match('/;\s*$/', $line)) {
							$sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . DB_PREFIX, $sql);
							$sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . DB_PREFIX, $sql);
							$sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . DB_PREFIX, $sql);
							$sql = str_replace("{config_email}", $this->config->get('config_email'), $sql);
							$sql = str_replace("{config_name}", $this->config->get('config_name'), $sql);
	
							$this->db->query($sql);
	
							$sql = '';
						}
					}
				}
			}
		}
	}

	public function check_updates(){
		$data =  array();
		$file = DIR_APPLICATION . 'view/template/extension/module/ocmod/updates_lowstock.json';
		if (file_exists($file)) {
			$data = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
			$data = json_decode($data, true);

			if ($data['version'] <= EXTENSION_VERSION) {
				$data =  array();
			}
			return $data;
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

	public function table_columns(){
		$columns = array('image','model','sku','upc','manufacturer','category','price','option_quantity','quantity_threshold','option_quantity_threshold','status');
		return $columns;
	}

	public function simple_layout_templates($template_folder_path){
		$files = array_diff(scandir($template_folder_path), array('.', '..'));
		$data['templates'] = array();
		foreach ($files as $file) {
			$file = str_replace('.txt','',$file);
			$data['templates'][] = array(
				'label' => str_replace('_',' ',$file),
				'value'	=> $file
			);
		}
		return $data['templates'];
	}

	public function addTemplateInstance($template_label, $store_id) {
		$email_options = array(
			'sender_name' 	=> $this->config->get('config_name'),
			'sender_email' 	=> $this->config->get('config_email'),
			'email_bcc' 	=> '',
			'email_replyto' => '',
			'email_subject' => 'Enter Subject Line!!!',
			'email_type_id' => '2',
		);
		$this->db->query("INSERT INTO " . DB_PREFIX . "hb_build_template (`template_label`,`store_id`,`loaded_template_id`, `email_type_id`,`editor`, `email_options`) VALUES ('" . $this->db->escape($template_label). "','".(int)$store_id."','9999', '6', 'ckeditor', '".$this->db->escape(json_encode($email_options))."')");
		return $this->db->getLastId();
	}

	public function getEmailTemplates($store_id){
		$results = $this->db->query("SELECT * FROM `".DB_PREFIX."hb_build_template` WHERE `store_id` = '".(int)$store_id."' AND email_type_id = 6 ORDER BY date_modified DESC");
		return $results->rows;
	}


	public function getTemplates($data){
		$sql = "SELECT * FROM `".DB_PREFIX."hb_build_template` WHERE store_id = '".(int)$data['store_id']."' AND email_type_id = 6";
		if (!empty($data['search'])) {
			$sql .= " AND `template_label` LIKE '%".$this->db->escape($data['search'])."%'";
		}
		$sql .= " ORDER BY date_added DESC";
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}			

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}	

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}	

		$query = $this->db->query($sql);
		return $query->rows;
	}
	
	public function getTotalTemplates($data){
		$sql = "SELECT count(*) as total FROM `".DB_PREFIX."hb_build_template` WHERE store_id = '".(int)$data['store_id']."' AND email_type_id = 6";
		if (!empty($data['search'])) {
			$sql .= " AND `template_label` LIKE '%".$this->db->escape($data['search'])."%'";
		}
		$sql .= " ORDER BY date_added DESC";
		$results = $this->db->query($sql);
		return $results->row['total'];
	}
	
	public function getTemplate($id){
		$results = $this->db->query("SELECT * FROM `".DB_PREFIX."hb_build_template` WHERE id = '".(int)$id."' LIMIT 1");
		return $results->row;
	}

	public function getProducts($data){
		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			$sql = "SELECT *, p.product_id as product_id, p.price as price, p.quantity as quantity, (SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = p.manufacturer_id) as manufacturer FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		}else{
			$sql = "SELECT *, p.product_id as product_id, p.price as price, p.quantity as quantity, pov.quantity as option_quantity, (SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = p.manufacturer_id) as manufacturer, (SELECT name FROM ".DB_PREFIX."option_description WHERE option_id = pov.option_id AND language_id = '" . (int)$this->config->get('config_language_id') . "') as option_name, (SELECT name FROM ".DB_PREFIX."option_value_description WHERE option_value_id = pov.option_value_id AND language_id = '" . (int)$this->config->get('config_language_id') . "') as option_value_name, (SELECT image FROM ".DB_PREFIX."option_value WHERE option_value_id = pov.option_value_id) as option_image FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_option_value pov ON (p.product_id = pov.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND (p.quantity <= p.hb_p_threshold OR pov.quantity <= pov.hb_pov_threshold)";
		}
		
		if (!empty($data['search'])) {
			$sql .= " AND (pd.name LIKE '%".$this->db->escape($data['search'])."%' OR p.product_id LIKE '%".$this->db->escape($data['search'])."%' OR p.model LIKE '%".$this->db->escape($data['search'])."%' OR p.sku LIKE '%".$this->db->escape($data['search'])."%' OR p.upc LIKE '%".$this->db->escape($data['search'])."%')";
		}

		if (!empty($data['search_manufacturer_id'])) {
			$sql .= " AND (p.manufacturer_id = '".(int)$data['search_manufacturer_id']."')";
		}

		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			if (!empty($data['search_category_id'])) {
				$sql .= " AND (p2c.category_id = '".(int)$data['search_category_id']."')";
			}
		}

		if (isset($data['search_status']) && $data['search_status'] != '') {
			$sql .= " AND (p.status = '".(int)$data['search_status']."')";
		}

		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			$sql .= " GROUP BY p.product_id";
		}

		$sql .= " ORDER BY date_added DESC";
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}			

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}	

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}	

		$query = $this->db->query($sql);
		//$this->log->write($sql);
		return $query->rows;
	}
	
	public function getTotalProducts($data){
		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		}else{
			$sql = "SELECT COUNT(*) AS total FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_option_value pov ON (p.product_id = pov.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND (p.quantity <= p.hb_p_threshold OR pov.quantity <= pov.hb_pov_threshold)";
		}

		if (!empty($data['search'])) {
			$sql .= " AND (pd.name LIKE '%".$this->db->escape($data['search'])."%' OR p.product_id LIKE '%".$this->db->escape($data['search'])."%' OR p.model LIKE '%".$this->db->escape($data['search'])."%' OR p.sku LIKE '%".$this->db->escape($data['search'])."%' OR p.upc LIKE '%".$this->db->escape($data['search'])."%')";
		}

		if (!empty($data['search_manufacturer_id'])) {
			$sql .= " AND (p.manufacturer_id = '".(int)$data['search_manufacturer_id']."')";
		}

		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			if (!empty($data['search_category_id'])) {
				$sql .= " AND (p2c.category_id = '".(int)$data['search_category_id']."')";
			}
		}

		if (isset($data['search_status']) && $data['search_status'] != '') {
			$sql .= " AND (p.status = '".(int)$data['search_status']."')";
		}

		$results = $this->db->query($sql);
		return $results->row['total'];
	}


	public function export_products($data){
		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			$sql = "SELECT p.product_id, pd.name, p.model, p.sku, p.upc, (SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = p.manufacturer_id) as manufacturer, p.price, p.quantity as overall_quantity, p.hb_p_threshold as overall_threshold, (SELECT name FROM ".DB_PREFIX."option_description WHERE option_id = pov.option_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_name, (SELECT name FROM ".DB_PREFIX."option_value_description WHERE option_value_id = pov.option_value_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_value_name, pov.quantity as option_quantity, pov.hb_pov_threshold as option_threshold, p.status, p.date_added, p.date_modified FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_to_category p2c ON (p.product_id = p2c.product_id) LEFT JOIN ".DB_PREFIX."product_option_value pov ON (p.product_id = pov.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND (p.quantity <= p.hb_p_threshold OR pov.quantity <= pov.hb_pov_threshold)";
		}else {
			$sql = "SELECT p.product_id, pd.name, p.model, p.sku, p.upc, (SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = p.manufacturer_id) as manufacturer, p.price, p.quantity as overall_quantity, p.hb_p_threshold as overall_threshold, (SELECT name FROM ".DB_PREFIX."option_description WHERE option_id = pov.option_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_name, (SELECT name FROM ".DB_PREFIX."option_value_description WHERE option_value_id = pov.option_value_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_value_name, pov.quantity as option_quantity, pov.hb_pov_threshold as option_threshold, p.status, p.date_added, p.date_modified FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) LEFT JOIN ".DB_PREFIX."product_option_value pov ON (p.product_id = pov.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND (p.quantity <= p.hb_p_threshold OR pov.quantity <= pov.hb_pov_threshold)";
		}
		
		if (!empty($data['search'])) {
			$sql .= " AND (pd.name LIKE '%".$this->db->escape($data['search'])."%' OR p.product_id LIKE '%".$this->db->escape($data['search'])."%' OR p.model LIKE '%".$this->db->escape($data['search'])."%' OR p.sku LIKE '%".$this->db->escape($data['search'])."%' OR p.upc LIKE '%".$this->db->escape($data['search'])."%')";
		}

		if (!empty($data['search_manufacturer_id'])) {
			$sql .= " AND (p.manufacturer_id = '".(int)$data['search_manufacturer_id']."')";
		}

		if ($this->config->get('hb_lowstock_dashboard_mode') == 'all') {
			if (!empty($data['search_category_id'])) {
				$sql .= " AND (p2c.category_id = '".(int)$data['search_category_id']."')";
			}
		}

		$sql .= " ORDER BY date_added DESC";	

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getOptionQuantities($product_id){
		$query = $this->db->query("SELECT *, (SELECT name FROM ".DB_PREFIX."option_description WHERE option_id = a.option_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_name, (SELECT name FROM ".DB_PREFIX."option_value_description WHERE option_value_id = a.option_value_id AND language_id = '".(int)$this->config->get('config_language_id')."') as option_value_name FROM `".DB_PREFIX."product_option_value` a where a.product_id = '".(int)$product_id."'");
		if ($query->rows){
			return $query->rows;
		}else{
			return array();
		}
	}

	public function isOptionAvailable($product_id){
		$query = $this->db->query("SELECT count(*) as total FROM `".DB_PREFIX."product_option_value` WHERE product_id = '".(int)$product_id."'");
		if ($query->row['total'] > 0){
			return true;
		}else{
			return false;
		}
	}

	public function optionQuantitySum($product_id){
		$query = $this->db->query("SELECT SUM(quantity) as total FROM `".DB_PREFIX."product_option_value` WHERE product_id = '".(int)$product_id."'");
		return $query->row['total'];
	}

	public function updateOptionQuantity($product_id, $product_option_value_id, $value, $add_mode) {
		if ($add_mode) {
			$this->db->query("UPDATE `".DB_PREFIX."product_option_value` SET quantity = (quantity + '".(int)$value."') WHERE product_id = '".(int)$product_id."' AND product_option_value_id = '".(int)$product_option_value_id."'");
			$this->db->query("UPDATE `".DB_PREFIX."product` SET quantity = (quantity + '".(int)$value."'), date_modified = now() WHERE product_id = '".(int)$product_id."'");
		}else{
			$this->db->query("UPDATE `".DB_PREFIX."product_option_value` SET quantity = '".(int)$value."' WHERE product_id = '".(int)$product_id."' AND product_option_value_id = '".(int)$product_option_value_id."'");
			
			$total_quantity = $this->optionQuantitySum($product_id);
			$this->db->query("UPDATE `".DB_PREFIX."product` SET quantity = '".(int)$total_quantity."', date_modified = now() WHERE product_id = '".(int)$product_id."'");
		}
		$this->cache->delete('product');
	}

	public function updateQuantity($product_id, $value, $add_mode) {
		if ($add_mode) {
			$this->db->query("UPDATE `".DB_PREFIX."product` SET quantity = (quantity + '".(int)$value."'), date_modified = now() WHERE product_id = '".(int)$product_id."'");
		}else{
			$this->db->query("UPDATE `".DB_PREFIX."product` SET quantity = '".(int)$value."', date_modified = now() WHERE product_id = '".(int)$product_id."'");
		}
		$this->cache->delete('product');
	}

	public function getQuantity($product_id){
		$query = $this->db->query("SELECT quantity FROM `".DB_PREFIX."product` WHERE product_id = '".(int)$product_id."'");
		return $query->row['quantity'];
	}

	public function updateThreshold($product_id, $value) {		
		$this->db->query("UPDATE `".DB_PREFIX."product` SET hb_p_threshold = '".(int)$value."', date_modified = now() WHERE product_id = '".(int)$product_id."'");
	}

	public function updateOptionThreshold($product_id, $product_option_value_id, $value) {
		$this->db->query("UPDATE `".DB_PREFIX."product_option_value` SET hb_pov_threshold = '".(int)$value."' WHERE product_id = '".(int)$product_id."' AND product_option_value_id = '".(int)$product_option_value_id."'");
	}
	
	public function getOptionQuantity($product_option_value_id){
		$query = $this->db->query("SELECT quantity FROM `".DB_PREFIX."product_option_value` WHERE product_option_value_id = '".(int)$product_option_value_id."'");
		return $query->row['quantity'];
	}

	public function updateStatus($product_id, $value) {
		$this->db->query("UPDATE `".DB_PREFIX."product` SET status = '".(int)$value."', date_modified = now() WHERE product_id = '".(int)$product_id."'");
		$this->cache->delete('product');
	}

	public function getCategory($category_id) {
		$query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&#47;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}


}
?>
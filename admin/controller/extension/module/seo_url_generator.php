<?php

/**
 * @category   OpenCart
 * @package    SEO URL Generator FREE
 * @copyright  © Serge Tkach, 2018, http://sergetkach.com/
 */

class ControllerExtensionModuleSeoUrlGenerator extends Controller {
	private $code = 'seo_url_generator';
	private $errors = array();
	private $stde; // StdE
	private $stdelog;

	function __construct($registry) {
		parent::__construct($registry);

    // StdE Require
		// $this->load->library('stde'); autoload
		$this->stde = new StdE($registry);
		$this->registry->set('stde', $this->stde);
		$this->stde->setCode($this->code);
		$this->stde->setType('extension_monolithic');

    // StdeLog require
		$this->stdelog = new StdeLog($this->code);
		$this->registry->set('stdelog', $this->stdelog);
		$this->stdelog->setDebug($this->config->get('module_' . $this->code . '_debug'));
	}


	public function install() {
		// -----------------------------------------------------------
		// Права на модуль
		// -----------------------------------------------------------
		
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->code);
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->code);

		
		// -----------------------------------------------------------
		// Создание страницы для хранение редиректов
		// -----------------------------------------------------------		
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "seo_url_generator_redirects` (
			`seo_url_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL,
			`language_id` int(11) NOT NULL,
      `seo_url_old` varchar(255) NOT NULL,
      `seo_url_actual` varchar(255) NOT NULL,
      `query` varchar(100) NOT NULL,
      PRIMARY KEY (`seo_url_id`),
      KEY `seo_url_old` (`seo_url_old`),
      KEY `seo_url_actual` (`seo_url_actual`),
      KEY `query` (`query`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$this->db->query($sql);
		
		
		// -----------------------------------------------------------
		// Автоматическое включение ЧПУ при наличии такой возможности
		// -----------------------------------------------------------
		// Автоматически включим человеку SEO URL :)
		$config_seo_url = $this->config->get('config_seo_url');

		if (!$config_seo_url) {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '1' WHERE `key` = 'config_seo_url'");
		}

		// Включаем SEO PRO, если такой присутствует
		$config_seo_pro = $this->config->get('config_seo_pro');

		if (!$config_seo_pro) {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '1' WHERE `key` = 'config_seo_pro'");
		}

		// Автоматически включим .htaccess, если есть права доступа к корню сайта
		$root_dir = str_replace('system/', '', DIR_SYSTEM);

		if (!is_file($root_dir . '.htaccess')) {
			if (is_file($root_dir . 'htaccess.txt')) {
				// ocStore
				@rename($root_dir . 'htaccess.txt', $root_dir . '.htaccess');
			} elseif (is_file($root_dir . '.htaccess.txt')) {
				// OpenCart.PRO & OpenCart pure
				@rename($root_dir . '.htaccess.txt', $root_dir . '.htaccess');
			} else {
				// if file wasn't uploaded on hosting
				if (is_file(DIR_SYSTEM . 'library/seo_url_generator/dist/.htaccess')) {
					$content = file_get_contents(DIR_SYSTEM . 'library/seo_url_generator/dist/.htaccess');
					@file_put_contents($root_dir . '.htaccess', $content, FILE_APPEND | LOCK_EX);
				}
			}
		}
	}

	public function uninstall() {
		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->code);
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->code);

		// Вроде как все чистится автоматом при деинсталляции
		// $this->load->model('setting/setting');
		// $this->model_setting_setting->editSetting($this->code, '');
	}




	/*
	------------------------------------------------------------------------------
	PARTS
	------------------------------------------------------------------------------
	*/

	/* Part Settings */
	public function index() {
		$this->load->language('extension/module/' . $this->code);
		$this->load->model('extension/module/' . $this->code);
		$this->load->model('setting/setting');

		// Save
		$data['text_success'] = '';

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateSettings()) {
			$data['text_success'] = $this->language->get('text_success'); // no redirects if success			
			
			# Magic for extension code in fields . Begin
			
			$pseudo_post = array();
			
			$post_temp = $this->request->post; // stores setting
			unset($post_temp['store_setting']); // stores setting
			
			// For common setting
			foreach ($post_temp as $key=> $value) {
				if (false === strpos($key, 'module_' . $this->code . '_')) {
					$pseudo_post['module_' . $this->code . '_' . $key] = $value;
				} else {
					$pseudo_post[$key] = $value; // no sense but...
				}
			}
			
			unset($post_temp); // stores setting
			
			// For each store setting
			$store_setting = array();
						
			foreach ($this->request->post['store_setting'] as $store_id => $store_item) {
				foreach ($store_item as $field => $value) {
					if (false === strpos($field, 'module_' . $this->code . '_')) {
						$store_setting[$store_id]['module_' . $this->code . '_' . $field] = $value;
					} else {
						$store_setting[$store_id][$field] = $value; // non sense but...
					}
				}
			}			
			
			# Magic for extension code in fields . End
			
			// Save setting for each store separately
			foreach ($store_setting as $store_id => $store_item) {
				if (0 == $store_id) {
					$store_item = array_merge($pseudo_post, $store_item);
				}
				
				$this->model_setting_setting->editSetting('module_' . $this->code, $store_item, $store_id);
			}
		}		

		// Errors
		if (isset($this->errors)) {
			$data['errors'] = $this->errors;
		}

		// Breadcrumbps & Links
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['action'] = $this->stde->link('action');
		$data['cancel'] = $this->stde->link('cancel');
		
		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_info'] = $this->stde->link('part_info');

		// Text
		$this->document->setTitle($this->language->get('heading_title'));

		// Data
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		$data['debug_levels'] = array(
			0=>$this->language->get('debug_0'),
			1=>$this->language->get('debug_1'),
			2=>$this->language->get('debug_2'),
			3=>$this->language->get('debug_3'),
			4=>$this->language->get('debug_4')
		);
		
		$data['translit_functions'] = $this->model_extension_module_seo_url_generator->getFunctionsList();
		
		$data['delimiter_chars'] = array(
			'underscore'=>$this->language->get('char_underscore'),
			'hyphen'=>$this->language->get('char_hyphen')
		);
		
		$data['change_delimiter_chars'] = array(
			'donot'=>$this->language->get('change_donot'),
			'underscore_to_hyphen'=>$this->language->get('change_underscore_to_hyphen'),
			'hyphen_to_underscore'=>$this->language->get('change_hyphen_to_underscore')
		);

		$data['licence'] = $this->stde->field('licence');
		
		$data['status'] = $this->stde->field('status');
		
		$data['debug'] = $this->stde->field('debug');		

		$data['limit'] = $this->stde->field('limit');
		
		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages()); // for icons universal
		
		$data['config_language_code'] = $this->config->get('config_language'); // ??
		
		# Setting for stores
		
		$data['store_setting'] = array();
		
		if (isset($this->request->post['store_setting'])) {
			// POST
			
			$data['store_setting'] = $this->request->post['store_setting'];			

			// Chars array . Begin
			foreach ($data['stores'] as $store) {
				$custom_replace_from = $this->request->post['store_setting'][$store['store_id']]['custom_replace_from'];
				$custom_replace_to = $this->request->post['store_setting'][$store['store_id']]['custom_replace_to'];
				
				foreach ($data['languages'] as $language) {
					$custom_replace_from[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_from[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_from_array'][$language['language_id']] = explode('<br>', $custom_replace_from[$language['language_id']]);

					$custom_replace_to[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_to[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_to_array'][$language['language_id']] = explode('<br>', $custom_replace_to[$language['language_id']]);
				}
			}
			// Chars array . End
			
		} else {
			// SAVED DATA
			
			foreach ($data['languages'] as $language) {
				$tmp_languages[$language['language_id']] = '';
			}

			$this->stde->languages4Setting($tmp_languages);			

			foreach ($data['stores'] as $store) {
				$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

				$this->stde->settingSet($store_setting);

				$data['store_setting'][$store['store_id']]['translit_function'] = $this->stde->fieldSetting('translit_function');
				$data['store_setting'][$store['store_id']]['delimiter_char'] = $this->stde->fieldSetting('delimiter_char');
				$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $this->stde->fieldSetting('change_delimiter_char');
				$data['store_setting'][$store['store_id']]['rewrite_on_save'] = $this->stde->fieldSetting('rewrite_on_save', 'donot');

				// Chars array . Begin
				$custom_replace_from = $this->stde->fieldSetting('custom_replace_from');
				$custom_replace_to = $this->stde->fieldSetting('custom_replace_to');

				foreach ($data['languages'] as $language) {
					$custom_replace_from[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_from[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_from_array'][$language['language_id']] = explode('<br>', $custom_replace_from[$language['language_id']]);

					$custom_replace_to[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_to[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_to_array'][$language['language_id']] = explode('<br>', $custom_replace_to[$language['language_id']]);
				}
				// Chars array . End

				$data['store_setting'][$store['store_id']]['category_formula'] = $this->stde->fieldSetting('category_formula', '[category_name]');
				$data['store_setting'][$store['store_id']]['product_formula'] = $this->stde->fieldSetting('product_formula', '[product_name]');
				$data['store_setting'][$store['store_id']]['manufacturer_formula'] = $this->stde->fieldSetting('manufacturer_formula', '[manufacturer_name]');
				$data['store_setting'][$store['store_id']]['information_formula'] = $this->stde->fieldSetting('information_formula', '[information_title]');
			}
			
		}

		// Common
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Output
		$this->response->setOutput($this->load->view('extension/module/' . $this->code, $data));
	}
	
	/* Part Info */
	public function part_info() {
		$this->load->language('extension/module/' . $this->code);
		
		// Breadcrumbps & Links
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['action'] = $this->stde->link('action');
		$data['cancel'] = $this->stde->link('cancel');
		
		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_info'] = $this->stde->link('part_info');

		// Text
		$this->document->setTitle($this->language->get('heading_title'));
		
		// Common
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Output
		$this->response->setOutput($this->stde->view('extension/module/' . $this->code. '_info', $data));
		
		return false;
	}
	
	
	


	/*
	 * Validate Settings
	 *
	 * A!
	 * Not need follow extension_code in post-fields thanking StdE Library & Magic :)
	 */
	protected function validateSettings() {
		if (!$this->user->hasPermission('modify', 'extension/module/' . $this->code)) {
			$this->errors['warning'] = $this->language->get('error_permission');
		}
		
		// A! Fields of $this->request->post is without extension prefix if follow StdE Library (!)
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages());
	
		
		### SETTING BY STORE ID . BEGIN
		foreach ($this->request->post['store_setting'] as $store_id => $store_item) {
			foreach ($data['languages'] as $language) {
				
				// Custom Replace
				$custom_replace_from_array = array();

				if ($store_item['custom_replace_from'][$language['language_id']]) {
					$store_item['custom_replace_from'][$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $store_item['custom_replace_from'][$language['language_id']]);

					$custom_replace_from_array = explode('<br>', $store_item['custom_replace_from'][$language['language_id']]);

					// Remove empty values from $this->request->post['custom_replace_from_array']
					foreach ($custom_replace_from_array as $key=> $value) {
						$value0 = trim($value);
						if (!$value0) {
							unset($custom_replace_from_array[$key]);
						}
					}

					$this->request->post['store_setting'][$store_id]['custom_replace_from'][$language['language_id']] = implode("\r\n", $custom_replace_from_array); // Need POST
				}

				$custom_replace_to_array = array();
				
				if ($store_item['custom_replace_to'][$language['language_id']]) {
					$store_item['custom_replace_to'][$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $store_item['custom_replace_to'][$language['language_id']]);

					$custom_replace_to_array = explode('<br>', $store_item['custom_replace_to'][$language['language_id']]);

					// Remove empty values from $store_item['custom_replace_to']
					foreach ($custom_replace_to_array as $key=> $value) {
						$value0 = trim($value);
						if (!$value0) {
							unset($custom_replace_to_array[$key]);
						}
					}

					$this->request->post['store_setting'][$store_id]['custom_replace_to'][$language['language_id']] = implode("\r\n", $custom_replace_to_array); // Need POST
				}				

				// Check Up Custom Replace
				if (count($custom_replace_from_array) > 0) {
					
					// If no values at the right column
					if (count($custom_replace_to_array) < 1) {
							$this->request->post['store_setting'][$store_id]['custom_replace_to'][$language['language_id']] = 'underscore' ==	$this->request->post['store_setting'][$store_id]['delimiter_char'][$language['language_id']] ? '_' : '-';
					}

					// Corelation between chars from the left and from the right
					if (count($custom_replace_from_array) != count($custom_replace_to_array) & count($custom_replace_to_array) > 1) {
						$this->errors[$store_id]['custom_replace_to'][$language['language_id']] = $this->language->get('error_custom_replace_to_not_1_char');
					}
					
				}
				
				// Category Formula
				$tmp['category_formula'] = $this->request->post['store_setting'][$store_id]['category_formula'][$language['language_id']] = trim($store_item['category_formula'][$language['language_id']]);
				
				if (empty($tmp['category_formula'])) {
					$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['category_formula'], '[category_name]') && false === strstr($tmp['category_formula'], '[category_id]')) {
						$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[category_name]',
							'[category_id]'
							), array(
							'',
							''
							), $tmp['category_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+$/", $str_without_vars)) {
								$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}
				
				// Product Formula
				$tmp['product_formula'] = $this->request->post['store_setting'][$store_id]['product_formula'][$language['language_id']] = trim($store_item['product_formula'][$language['language_id']]);

				if (empty($tmp['product_formula'])) {
					$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['product_formula'], '[product_name]') && false === strstr($tmp['product_formula'], '[product_id]') && false === strstr($tmp['product_formula'], '[model]') && false === strstr($tmp['product_formula'], '[sku]')) {

						$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[product_name]',
							'[product_id]',
							'[model]',
							'[sku]',
							'[manufacturer_name]'
							), array(
							'',
							'',
							'',
							''
							), $tmp['product_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+$/", $str_without_vars)) {
								$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}				
				
				// Manufacturer Formula
				$tmp['manufacturer_formula'] = $this->request->post['store_setting'][$store_id]['manufacturer_formula'][$language['language_id']] = trim($store_item['manufacturer_formula'][$language['language_id']]);

				if (empty($tmp['manufacturer_formula'])) {
					$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['manufacturer_formula'], '[manufacturer_name]') && false === strstr($tmp['manufacturer_formula'], '[manufacturer_id]')) {
						$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[manufacturer_name]',
							'[manufacturer_id]'
							), array(
							'',
							''
							), $tmp['manufacturer_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+$/", $str_without_vars)) {
								$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}
				
				// Information Formula
				$tmp['information_formula'] = $this->request->post['store_setting'][$store_id]['information_formula'][$language['language_id']] = trim($store_item['information_formula'][$language['language_id']]);

				if (empty($tmp['information_formula'])) {
					$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['information_formula'], '[information_title]') && false === strstr($tmp['information_formula'], '[information_id]')) {
						$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[information_title]',
							'[information_id]'
							), array(
							'',
							''
							), $tmp['information_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+$/", $str_without_vars)) {
								$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}				
				
			}
		}
		### SETTING BY STORE ID . END		



		// If is any errors : common warning
		if ($this->errors && !isset($this->errors['warning'])) {
			$this->errors['warning'] = $this->language->get('error_warning');
		}

		return !$this->errors;
	}



	
	/*
	------------------------------------------------------------------------------
	Generation by AJAX
	------------------------------------------------------------------------------
	*/

	/*
	 * For all SEO URL - on add
	 */

	public function generateSeoUrlByAjax4All() {
		$this->stdelog->write(2, 'generateSeoUrlByAjax4All() is called');
		$this->stdelog->write(3, $this->request->post, 'generateSeoUrlByAjax4All() : $this->request->post');

		$json = array(
			'result'=>''
		);
		
		if (!isset($this->request->post)) {
			return false;
		}

		### SETTING
		$a_data['setting'] = array();

		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('setting/setting');
		
		$data['store_setting'] = array();
		
		foreach ($data['stores'] as $store) {
			$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

			$this->stdelog->write(4, $store_setting, 'generateSeoUrlByAjax4All() : $store_setting for $store["store_id"] = ' . $store['store_id']);

			if (in_array($this->request->post['essence'], array('category', 'product', 'manufacturer', 'information'))) {
				$data['store_setting'][$store['store_id']]['formula'] = $store_setting['module_seo_url_generator_' . $this->request->post['essence'] . '_formula'];
			} else {
				$this->stdelog->write(1, 'generateSeoUrlByAjax4All() : is not system essence');

				foreach ($data['languages'] as $language) {
					$data['store_setting'][$store['store_id']]['formula'][$language['language_id']] = '';
				}				
			}
	
			$data['store_setting'][$store['store_id']]['translit_function']     = $store_setting['module_seo_url_generator_translit_function'];
			$data['store_setting'][$store['store_id']]['delimiter_char']        = $store_setting['module_seo_url_generator_delimiter_char'];	
			$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
			$data['store_setting'][$store['store_id']]['rewrite_on_save']       = $store_setting['module_seo_url_generator_rewrite_on_save'];			
			$data['store_setting'][$store['store_id']]['custom_replace_from']   = $store_setting['module_seo_url_generator_custom_replace_from'];			
			$data['store_setting'][$store['store_id']]['custom_replace_to']     = $store_setting['module_seo_url_generator_custom_replace_to'];
		}

		$this->stdelog->write(4, $data["store_setting"], 'generateSeoUrlByAjax4All() : $data["store_setting"]');

		$keywords = array();

		foreach ($data['stores'] as $store) {
			foreach ($data['languages'] as $language) {
				
				if (strpos($data['store_setting'][$store['store_id']]['formula'][$language['language_id']], $this->request->post['primary_key']) && !$this->request->post['essence_id']) {
					$this->stdelog->write(1, 'generateSeoUrlByAjax4All() : formule contain $this->request->post["primary_key"]-value! But we still haven"t essence_id in reallity');

					$keywords[$store['store_id']][$language['language_id']] = '';

					continue;
				}

				$a_data = array();

				$a_data['name']        = $this->request->post['a_lang_data']['name'][$language['language_id']];
				$a_data['primary_key'] = $this->request->post['primary_key'];
				$a_data['essence']     = $this->request->post['essence'];
				$a_data['essence_id']  = $this->request->post['essence_id'];

				if (isset($this->request->post['model'])) $a_data['model'] = $this->request->post['model'];
				if (isset($this->request->post['manufacturer_id'])) $a_data['manufacturer_id'] = $this->request->post['manufacturer_id'];
				if (isset($this->request->post['sku'])) $a_data['sku'] = $this->request->post['sku'];

				$a_data['setting'] = array(
					'translit_function'     => $data['store_setting'][$store['store_id']]['translit_function'][$language['language_id']],
					'formula'               => $data['store_setting'][$store['store_id']]['formula'][$language['language_id']],
					'delimiter_char'        => $data['store_setting'][$store['store_id']]['delimiter_char'][$language['language_id']],
					'change_delimiter_char' => $data['store_setting'][$store['store_id']]['change_delimiter_char'][$language['language_id']],
					'rewrite_on_save'       => $data['store_setting'][$store['store_id']]['rewrite_on_save'][$language['language_id']],
					'custom_replace_from'   => $data['store_setting'][$store['store_id']]['custom_replace_from'][$language['language_id']],
					'custom_replace_to'     => $data['store_setting'][$store['store_id']]['custom_replace_to'][$language['language_id']],
				);

				$keywords[$store['store_id']][$language['language_id']] = $this->generateSeoUrl($a_data);
			}
		}

		// Выдать значения для каждого магазина и языка!

		$this->stdelog->write(4, 'generateSeoUrlByAjax4All() : call $this->generateSeoUrl()');

		if (is_array($keywords)) {
			$json['result'] = $keywords;
		} else {
			$json['result'] = 'error';
		}

		$this->stdelog->write(4, $json, 'generateSeoUrlByAjax4All() : $json defore json_encode($json)');

		header('Content-type:application/json;charset=utf-8');
		echo json_encode($json);
		exit;
	}


	/*
	 * For one SEO URL - on edit
	 */

	public function generateSeoUrlByAjax4One() {
		$this->stdelog->write(2, 'generateSeoUrlByAjax4One() is called');
		$this->stdelog->write(3, $this->request->post, 'generateSeoUrlByAjax4One() : $this->request->post');

		$json = array(
			'result'=>''
		);
		
		if (!isset($this->request->post)) {
			return false;
		}

		### SETTING
		$setting = array();

		$this->load->model('setting/setting');
		
		$store_setting = $this->model_setting_setting->getSetting('module_seo_url_generator', $this->request->post['store_id']);

		$this->stdelog->write(4, $store_setting, 'generateSeoUrlByAjax4One() : $store_setting for $this->request->post["store_id"] = ' . $this->request->post['store_id']);

		if (in_array($this->request->post['essence'], array('category', 'product', 'manufacturer', 'information'))) {
			$setting['formula'] = $store_setting['module_seo_url_generator_' . $this->request->post['essence'] . '_formula'];
		} else {
			$this->stdelog->write(1, 'generateSeoUrlByAjax4One() : is not system essence');

			$setting['formula'][$this->request->post['language_id']] = '';
		}

		$setting['translit_function']     = $store_setting['module_seo_url_generator_translit_function'];
		$setting['delimiter_char']        = $store_setting['module_seo_url_generator_delimiter_char'];
		$setting['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
		$setting['rewrite_on_save']       = $store_setting['module_seo_url_generator_rewrite_on_save'];
		$setting['custom_replace_from']   = $store_setting['module_seo_url_generator_custom_replace_from'];
		$setting['custom_replace_to']     = $store_setting['module_seo_url_generator_custom_replace_to'];

		$this->stdelog->write(3, $setting, 'generateSeoUrlByAjax4One() : $setting');

		$keyword = false;
				
		if (strpos($setting['formula'][$this->request->post['language_id']], $this->request->post['primary_key']) && !$this->request->post['essence_id']) {
			$this->stdelog->write(1, 'generateSeoUrlByAjax4One() : formule contain $this->request->post["primary_key"]-value! But we still haven"t essence_id in reallity');

			// $keyword = false; // by default

		} else {
			$a_data = array();

			$a_data['name']        = $this->request->post['name'];
			$a_data['primary_key'] = $this->request->post['primary_key'];
			$a_data['essence']     = $this->request->post['essence'];
			$a_data['essence_id']  = $this->request->post['essence_id'];

			if (isset($this->request->post['model'])) $a_data['model'] = $this->request->post['model'];
			if (isset($this->request->post['manufacturer_id'])) $a_data['manufacturer_id'] = $this->request->post['manufacturer_id'];
			if (isset($this->request->post['sku'])) $a_data['sku'] = $this->request->post['sku'];

			$a_data['setting'] = array(
					'translit_function'     => $setting['translit_function'][$this->request->post['language_id']],
					'formula'               => $setting['formula'][$this->request->post['language_id']],
					'delimiter_char'        => $setting['delimiter_char'][$this->request->post['language_id']],
					'change_delimiter_char' => $setting['change_delimiter_char'][$this->request->post['language_id']],
					'rewrite_on_save'       => $setting['rewrite_on_save'][$this->request->post['language_id']],
					'custom_replace_from'   => $setting['custom_replace_from'][$this->request->post['language_id']],
					'custom_replace_to'     => $setting['custom_replace_to'][$this->request->post['language_id']],
				);
			
			$keyword = $this->generateSeoUrl($a_data);
		}

		$this->stdelog->write(4, $keyword, 'generateSeoUrlByAjax4One() : $this->generateSeoUrl() returned $keyword');

		if ($keyword) {
			$json['result'] = $keyword;
		} else {
			$json['result'] = 'error';
		}

		$this->stdelog->write(4, $json, 'generateSeoUrlByAjax4One() : $json defore json_encode($json)');

		header('Content-type:application/json;charset=utf-8');
		echo json_encode($json);
		exit;
	}


	/*
	 * Base method
	 * Это у нас основой метод, который генерирует SEO URL
	 *
	 * Ему все равно, откуда поступают данные о товаре - из формы в админке или из базы при массовом редактировании
	 *
	 * Определить сущность
	 * Определить, какие переменные есть в формуле
	 * Вырезать из формулы лишние - (транслит сам это сделает)
	 * Транлитировать
	 * Запросить уникальность
	 * Если URL не уникален, то использовать индекс N - причем, это не зависит от того, есть ли в формуле генерации доп переменные или нет
	 */

	public function generateSeoUrl($a_data) {
		$this->stdelog->write(3, 'generateSeoUrl() is called');
		
		$this->stdelog->write(4, $a_data, 'generateSeoUrl() : $a_data');

		$this->load->model('extension/module/' . $this->code);
		
		// Setting
		// Нельзя, чтобы запрос настроек шел при каждой иттерации
		// В 3-ке надо, чтобы в данные этого метода передавались и настройки
		
		$setting = $a_data['setting'];
		
		$this->stdelog->write(4, $setting, 'generateSeoUrl() : $setting');
		
		$this->stdelog->write(3, $a_data['essence'], 'generateSeoUrl() : $a_data["essence"]');

		$name = $this->model_extension_module_seo_url_generator->essenceNameFilter($a_data['name'], $a_data['essence'], $setting);

		$this->stdelog->write(4, $name, 'generateSeoUrl() : $name after $this->model_extension_module_seo_url_generator->essenceNameFilter()');

		$keyword = '';
		
		$this->stdelog->write(4, $a_data['essence'], 'generateSeoUrl() : $a_data["essence"]');

		if (isset($a_data['essence']) && $a_data['essence']) {
			if ('category' == $a_data['essence']) {			
				$this->stdelog->write(4, 'generateSeoUrl() : prepare to call to generateOtherSystemsEssenceKeyword() in category essence');
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data, $setting);
			} elseif ('product' == $a_data['essence']) {
				$this->stdelog->write(4, 'generateSeoUrl() : prepare to call to generateProductKeyword() in product essence');
				$keyword = $this->model_extension_module_seo_url_generator->generateProductKeyword($a_data, $setting);
			} elseif ('manufacturer' == $a_data['essence']) {
				$this->stdelog->write(4, 'generateSeoUrl() : prepare to call to generateOtherSystemsEssenceKeyword() in manufacturer essence');
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data, $setting);
			} elseif ('information' == $a_data['essence']) {
				$this->stdelog->write(4, 'generateSeoUrl() : prepare to call to generateOtherSystemsEssenceKeyword() in information essence');
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data, $setting);
			} else {
				$this->stdelog->write(4, 'generateSeoUrl() : prepare to call to generateProductKeyword() in nonsystem essence');
				$keyword = $this->model_extension_module_seo_url_generator->generateNotSystemsEssenceKeyword($a_data, $setting);
			}

			$this->stdelog->write(4, $keyword, 'generateSeoUrl() : $keyword returned from generate function()');
		} else {
			$this->stdelog->write(1, 'generateSeoUrl() : $a_data["essence"] is empty');
		}

		$this->stdelog->write(4, $keyword, 'generateSeoUrl() : call to $this->model_extension_module_seo_url_generator->translit()');

		$keyword = $this->model_extension_module_seo_url_generator->translit($keyword, $setting);

		$this->stdelog->write(4, $keyword, 'generateSeoUrl() : $keyword after $this->model_extension_module_seo_url_generator->translit()');
		
		if (!$keyword) {
			$this->stdelog->write(1, $keyword, 'generateSeoUrl() : $keyword');
		} else {
			$this->stdelog->write(3, $keyword, 'generateSeoUrl() : return $keyword');
		}

		return $keyword;
	}

}

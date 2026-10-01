<?php
//==============================================================================
// Auto-Add Products to Cart Pro v303.3
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

class ModelExtensionModuleAutoAddProductsPro extends Model {
	private $type = 'module';
	private $name = 'auto_add_products_pro';
	private $testing_mode;
	private $row;
	
	public function trigger() {
		$settings = $this->cache->get($this->name . '.settings');
		if (empty($settings)) {
			$settings = $this->getSettings();
			$this->cache->set($this->name . '.settings', $settings);
		}
		
		$this->testing_mode = $settings['testing_mode'];
		$this->logMessage("\n" . '------------------------------ Starting Test ' . date('Y-m-d G:i:s') . ' ------------------------------');
		
		if (empty($settings['status'])) {
			$this->logMessage('Extension is disabled');
			return;
		}
		
		// Set address info
		$addresses = array();
		$this->load->model('account/address');
		foreach (array('shipping', 'payment', 'geoiptools') as $address_type) {
			if ($address_type == 'geoiptools' && !empty($this->session->data['geoip_data']['location'])) {
				$address = $this->session->data['geoip_data']['location'];
			} elseif (($address_type == 'shipping' && empty($address)) || $address_type == 'payment') {
				$address = array();
				
				if ($this->customer->isLogged()) 										$address = $this->model_account_address->getAddress($this->customer->getAddressId());
				if (!empty($this->session->data['country_id']))							$address['country_id'] = $this->session->data['country_id'];
				if (!empty($this->session->data['zone_id']))							$address['zone_id'] = $this->session->data['zone_id'];
				if (!empty($this->session->data['postcode']))							$address['postcode'] = $this->session->data['postcode'];
				if (!empty($this->session->data['city']))								$address['city'] = $this->session->data['city'];
				
				if (!empty($this->session->data[$address_type . '_country_id']))		$address['country_id'] = $this->session->data[$address_type . '_country_id'];
				if (!empty($this->session->data[$address_type . '_zone_id']))			$address['zone_id'] = $this->session->data[$address_type . '_zone_id'];
				if (!empty($this->session->data[$address_type . '_postcode']))			$address['postcode'] = $this->session->data[$address_type . '_postcode'];
				if (!empty($this->session->data[$address_type . '_city']))				$address['city'] = $this->session->data[$address_type . '_city'];
				
				if (!empty($this->session->data['guest'][$address_type]))				$address = $this->session->data['guest'][$address_type];
				if (!empty($this->session->data[$address_type . '_address_id']))		$address = $this->model_account_address->getAddress($this->session->data[$address_type . '_address_id']);
				if (!empty($this->session->data[$address_type . '_address']))			$address = $this->session->data[$address_type . '_address'];
			}
			
			if (empty($address['company']))		$address['company'] = '';
			if (empty($address['address_1']))	$address['address_1'] = '';
			if (empty($address['address_2']))	$address['address_2'] = '';
			if (empty($address['city']))		$address['city'] = '';
			if (empty($address['postcode']))	$address['postcode'] = '';
			if (empty($address['country_id']))	$address['country_id'] = $this->config->get('config_country_id');
			if (empty($address['zone_id']))		$address['zone_id'] =  $this->config->get('config_zone_id');
			
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$address['country_id']);
			$address['country'] = (isset($country_query->row['name'])) ? $country_query->row['name'] : '';
			$address['iso_code_2'] = (isset($country_query->row['iso_code_2'])) ? $country_query->row['iso_code_2'] : '';
			
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$address['zone_id']);
			$address['zone'] = (isset($zone_query->row['name'])) ? $zone_query->row['name'] : '';
			$address['zone_code'] = (isset($zone_query->row['code'])) ? $zone_query->row['code'] : '';
			
			$addresses[$address_type] = $address;
			
			$addresses[$address_type]['geo_zones'] = array();
			$geo_zones_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = " . (int)$address['country_id'] . " AND (zone_id = 0 OR zone_id = " . (int)$address['zone_id'] . ")");
			if ($geo_zones_query->num_rows) {
				foreach ($geo_zones_query->rows as $geo_zone) {
					$addresses[$address_type]['geo_zones'][] = $geo_zone['geo_zone_id'];
				}
			} else {
				$addresses[$address_type]['geo_zones'] = array(0);
			}
		}
		
		// Record testing mode info
		if ($this->customer->isLogged()) {
			$this->logMessage('CUSTOMER: ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (customer_id: ' . $this->customer->getId() . ', ip: ' . $this->request->server['REMOTE_ADDR'] . ')');
		} else {
			$this->logMessage('CUSTOMER: Guest (' . $this->request->server['REMOTE_ADDR'] . ')');
		}
		
		if ($this->type != 'shipping') {
			$billing_address = array(
				$addresses['payment']['address_1'],
				$addresses['payment']['address_2'],
				$addresses['payment']['city'],
				$addresses['payment']['zone'],
				$addresses['payment']['postcode'],
				$addresses['payment']['country'],
			);
			$this->logMessage('BILLING ADDRESS: ' . implode(', ', array_filter($billing_address)));
		}
		
		$shipping_address = array(
			$addresses['shipping']['address_1'],
			$addresses['shipping']['address_2'],
			$addresses['shipping']['city'],
			$addresses['shipping']['zone'],
			$addresses['shipping']['postcode'],
			$addresses['shipping']['country'],
		);
		$this->logMessage('SHIPPING ADDRESS: ' . implode(', ', array_filter($shipping_address)));
		
		$this->logMessage('EVALUATING RULES:');
		
		// Set order totals if necessary
		if ($this->type != 'total') {
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
			
			$order_totals_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'total' ORDER BY `code` ASC");
			$order_totals = $order_totals_query->rows;
			
			$sort_order = array();
			foreach ($order_totals as $key => $value) {
				$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
			}
			array_multisort($sort_order, SORT_ASC, $order_totals);
			
			$total_data = array();
			$order_total = 0;
			$taxes = $this->cart->getTaxes();
			$total_array = array('totals' => &$total_data, 'total' => &$order_total, 'taxes' => &$taxes);
			
			foreach ($order_totals as $ot) {
				if ($ot['code'] == $this->name || ($ot['code'] == 'shipping' && $this->type == 'shipping')) break;
				if (!$this->config->get($prefix . $ot['code'] . '_status') || $ot['code'] == 'intermediate_order_total') continue;
				if (version_compare(VERSION, '2.2', '<')) {
					$this->load->model('total/' . $ot['code']);
					$this->{'model_total_' . $ot['code']}->getTotal($total_data, $order_total, $taxes);
				} elseif (version_compare(VERSION, '2.3', '<')) {
					$this->load->model('total/' . $ot['code']);
					$this->{'model_total_' . $ot['code']}->getTotal($total_array);
				} else {
					$this->load->model('extension/total/' . $ot['code']);
					$this->{'model_extension_total_' . $ot['code']}->getTotal($total_array);
				}
			}
		}
		
		// Set shipping/payment info
		$shipping_method = (isset($this->session->data['shipping_method']['code'])) ? substr($this->session->data['shipping_method']['code'], 0, strpos($this->session->data['shipping_method']['code'], '.')) : '';
		$shipping_rate = (isset($this->session->data['shipping_method']['title'])) ? strtolower($this->session->data['shipping_method']['title']) : '';
		$shipping_cost = (isset($this->session->data['shipping_method']['cost'])) ? $this->session->data['shipping_method']['cost'] : 0;
		
		if (isset($this->session->data['payment_method']['code'])) {
			$payment_method = $this->session->data['payment_method']['code'];
		} elseif (isset($this->request->post['payment_code'])) {
			$payment_method = $this->request->post['payment_code'];
		} else {
			$payment_method = '';
		}
		
		// Set cart and order data
		$this->load->model('catalog/product');
		
		$cart_products = $this->cart->getProducts();
		if (version_compare(VERSION, '2.1', '>=')) {
			foreach ($cart_products as &$cart_product) {
				$cart_product['key'] = $cart_product['product_id'] . json_encode($cart_product['option']) . json_encode($cart_product['recurring'] ? $cart_product['recurring'] : array());
			}
		}
		
		$cumulative_total_value = $order_total;
		$currency = $this->session->data['currency'];
		$customer_id = (int)$this->customer->getId();
		$customer_group_id = (int)$this->customer->getGroupId();
		$language = (isset($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$store_id = (isset($this->session->data['store_id'])) ? (int)$this->session->data['store_id'] : (int)$this->config->get('config_store_id');
		
		$this->load->model('account/reward');
		$coupon = (isset($this->session->data['coupon'])) ? $this->session->data['coupon'] : '';
		$reward_points = (isset($this->session->data['reward'])) ? $this->session->data['reward'] : 0;
		$reward_points_in_account = $this->model_account_reward->getTotalPoints();
		$voucher = (isset($this->session->data['voucher'])) ? $this->session->data['voucher'] : '';
		
		$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = " . (int)$customer_id);

		$customer_custom_fields = array();
		if ($customer_id) {
			if (!empty($customer->row['custom_field'])) {
				$customer_custom_fields = (version_compare(VERSION, '2.1', '<')) ? unserialize($customer->row['custom_field']) : json_decode($customer->row['custom_field'], true);
			}
		} else {
			if (!empty($this->session->data['guest']['custom_field'])) {
				$customer_custom_fields = $this->session->data['guest']['custom_field'];
			} elseif (!empty($this->request->post['order_data']['custom_field'])) {
				// Journal compatibility
				$customer_custom_fields = $this->request->post['order_data']['custom_field'];
			}
		}
		
		// extension-specific
		$auto_add_products = array();
		$auto_remove_products = array();
		$this->session->data['auto_added_free_pro'] = array();
		$auto_added_removable = array();
		
		if (empty($cart_products)) {
			$this->session->data['manually_removed_products_pro'] = array();
		}
		
		// Loop through rows
		foreach ($settings['trigger'] as $row) {
			$this->row = $row;
			if (!isset($row['name']) || substr(trim($row['name']), 0, 1) == '-') continue;
			
			// Compile rules and rule sets
			$rule_list = (!empty($row['rule'])) ? $row['rule'] : array();
			$rule_sets = array();
			
			foreach ($rule_list as $rule) {
				if (isset($rule['type']) && $rule['type'] == 'rule_set') {
					$rule_sets[] = $settings['rule_set'][$rule['value']]['rule'];
				}
			}
			
			foreach ($rule_sets as $rule_set) {
				$rule_list = array_merge($rule_list, $rule_set);
			}
			
			$rules = array();
			foreach ($rule_list as $rule) {
				if (empty($rule['type'])) continue;
				
				if (isset($rule['value'])) {
					if (in_array($rule['type'], array('attribute_group', 'category', 'filter', 'manufacturer', 'product', 'zone'))) {
						$value = substr($rule['value'], strrpos($rule['value'], '[') + 1, -1);
					} else {
						$value = $rule['value'];
					}
				} else {
					$value = 1;
				}
				
				if (!isset($rule['comparison'])) $rule['comparison'] = '';
				if (in_array($rule['type'], array('attribute', 'custom_field', 'option', 'quantity_of_product'))) {
					$comparison = substr($rule['comparison'], strrpos($rule['comparison'], '[') + 1, -1);
				} else {
					$comparison = $rule['comparison'];
				}
				$rules[$rule['type']][$comparison][] = $value;
			}
			$this->row['rules'] = $rules;
			
			// extension-specific
			$product_ids_to_add = array();
			
			if (empty($row['product'])) {
				continue;
			}
			
			foreach ($row['product'] as $auto_add_product) {
				$bracket = strrpos($auto_add_product, '[');
				$colon = strrpos($auto_add_product, ':');
				$product_id = substr($auto_add_product, $colon + 1, -1);
				
				// Find option to apply
				$options_to_add = array();
				
				foreach (explode("\n", $row['option']) as $option) {
					if (empty($option)) continue;
					
					$name_and_value = explode('=', $option);
					$option_name = trim($name_and_value[0]);
					$option_values = array_map('trim', explode(';', $name_and_value[1]));
					
					$option_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_description WHERE `name` = '" . $this->db->escape($option_name) . "'");
					
					foreach ($option_description_query->rows as $od) {
						$option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option` WHERE option_id = " . (int)$od['option_id']);
						
						foreach ($option_values as $option_value) {
							$option_value_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value_description WHERE `name` = '" . $this->db->escape($option_value) . "' AND option_id = " . (int)$od['option_id']);
							
							if ($option_value_description_query->num_rows) {
								foreach ($option_value_description_query->rows as $ovd) {
									$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_id = " . (int)$product_id . " AND option_id = " . (int)$od['option_id'] . " AND option_value_id = " . (int)$ovd['option_value_id']);
									
									foreach ($product_option_value_query->rows as $pov) {
										if ($option_query->row['type'] == 'checkbox') {
											$options_to_add[$pov['product_option_id']][] = $pov['product_option_value_id'];
										} else {
											$options_to_add[$pov['product_option_id']] = $pov['product_option_value_id'];
										}
									}
								}
							} else {
								$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option WHERE product_id = " . (int)$product_id . " AND option_id = " . (int)$od['option_id']);
								
								if ($product_option_query->num_rows) {
									$options_to_add[$product_option_query->row['product_option_id']] = $option_value;
								}
							}
						}
					}
				}
				
				// Add product + options to array
				$product_ids_to_add[$product_id] = $options_to_add;
				
				if ($row['auto_remove_products']) {
					$auto_remove_products[$product_id] = $options_to_add;
				}
			}
			
			// Check date/time criteria
			if ($this->ruleViolation('day', strtolower(date('l'))) ||
				$this->ruleViolation('date', date('Y-m-d H:i')) ||
				$this->ruleViolation('time', date('H:i'))
			) {
				continue;
			}
			
			// Check discount criteria
			if (isset($rules['coupon'])) {
				$this->commaMerge($rules['coupon']);
				$this->row['rules']['coupon'] = $rules['coupon'];
				$coupon_value = 0;
				
				if ($coupon) {
					foreach ($total_data as $ot) {
						if ($ot['code'] == 'coupon') $coupon_value = -$ot['value'];
					}
					
					if (!$coupon_value) {
						$temp_total_data = array();
						$temp_total = 1000000;
						$temp_taxes = $this->cart->getTaxes();
						$temp_totals = array(
							'totals'	=> &$temp_total_data,
							'total'		=> &$temp_total,
							'taxes'		=> &$temp_taxes,
						);
						
						if (version_compare(VERSION, '2.2', '<')) {
							$this->load->model('total/coupon');
							$this->model_total_coupon->getTotal($temp_total_data, $temp_total, $temp_taxes);
						} elseif (version_compare(VERSION, '2.3', '<')) {
							$this->load->model('total/coupon');
							$this->model_total_coupon->getTotal($temp_totals);
						} else {
							$this->load->model('extension/total/coupon');
							$this->model_extension_total_coupon->getTotal($temp_totals);
						}
						
						$coupon_value = 1000000 - $temp_total;
					}
				}
				
				foreach ($rules['coupon'] as $comparison => $rule_coupons) {
					if ($comparison == 'discount') {
						if (!$this->inRange($coupon_value, $rule_coupons, 'coupon value = ')) {
							continue 2;
						}
					} else {
						if (in_array('', $rule_coupons)) {
							if (($comparison == 'is' && !$coupon) || ($comparison == 'not' && $coupon)) {
								continue 2;
							}
						} else {
							if ($this->ruleViolation('coupon', strtolower($coupon))) {
								continue 2;
							}
						}
					}
				}
			}
			
			if (isset($rules['gift_voucher'])) {
				foreach ($rules['gift_voucher'] as $comparison => $rule_vouchers) {
					if ($comparison == 'applied') {
						$voucher_value = 0;
						if ($voucher) {
							foreach ($total_data as $ot) {
								if ($ot['code'] == 'voucher') $voucher_value = -$ot['value'];
							}
							if (!$voucher_value) {
								$temp_total_data = array();
								$temp_total = 1000000;
								$temp_taxes = $this->cart->getTaxes();
								$temp_totals = array(
									'totals'	=> &$temp_total_data,
									'total'		=> &$temp_total,
									'taxes'		=> &$temp_taxes,
								);
								
								if (version_compare(VERSION, '2.2', '<')) {
									$this->load->model('total/voucher');
									$this->model_total_voucher->getTotal($temp_total_data, $temp_total, $temp_taxes);
								} elseif (version_compare(VERSION, '2.3', '<')) {
									$this->load->model('total/voucher');
									$this->model_total_voucher->getTotal($temp_totals);
								} else {
									$this->load->model('extension/total/voucher');
									$this->model_extension_total_voucher->getTotal($temp_totals);
								}
								
								$voucher_value = 1000000 - $temp_total;
							}
						}
						if (!$this->inRange($voucher_value, $rule_vouchers, 'gift voucher applied to cart')) {
							continue 2;
						}
					} elseif ($comparison == 'purchased') {
						$qualifying_voucher_being_purchased = false;
						$vouchers = (!empty($this->session->data['vouchers'])) ? $this->session->data['vouchers'] : array(array('amount' => 0));
						foreach ($vouchers as $voucher) {
							if ($this->inRange($voucher['amount'], $rule_vouchers, 'gift voucher being purchased', true)) {
								$qualifying_voucher_being_purchased = true;
							}
						}
						if (!$qualifying_voucher_being_purchased) {
							$this->logMessage('"' . $row['name'] . '" disabled for violating "Gift Voucher being purchased" rule(s)');
							continue 2;
						}
					}
				}
			}
			
			if (isset($rules['reward_points'])) {
				$cart_reward_points = 0;
				foreach ($cart_products as $product) {
					$cart_reward_points += $product['reward'];
				}
				foreach ($rules['reward_points'] as $comparison => $rule_reward_points) {
					if ($comparison == 'applied') {
						if (!$this->inRange($reward_points, $rule_reward_points, 'reward points ' . $comparison)) {
							continue 2;
						}
					} elseif ($comparison == 'products') {
						if (!$this->inRange($cart_reward_points, $rule_reward_points, 'reward points of ' . $comparison)) {
							continue 2;
						}
					} elseif ($comparison == 'customer') {
						if (!$this->inRange($reward_points_in_account, $rule_reward_points, 'reward points of ' . $comparison)) {
							continue 2;
						}
					}
				}
			}
			
			// Check order criteria
			if ($this->ruleViolation('currency', $currency) ||
				$this->ruleViolation('customer_group', $customer_group_id) ||
				$this->ruleViolation('language', $language) ||
				$this->ruleViolation('payment_extension', $payment_method) ||
				$this->ruleViolation('shipping_extension', $shipping_method) ||
				$this->ruleViolation('store', $store_id)
			) {
				continue;
			}
			
			if (isset($rules['custom_field'])) {
				$this->commaMerge($rules['custom_field']);
				
				$custom_fields = $customer_custom_fields;
				if (!empty($address['custom_field'])) {
					$custom_fields += $address['custom_field'];
				}
				
				foreach ($rules['custom_field'] as $comparison => $values) {
					foreach ($custom_fields as $custom_field_id => $custom_field_value) {
						$custom_field_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_field_value_description WHERE custom_field_id = " . (int)$custom_field_id . " AND custom_field_value_id = " . (int)$custom_field_value);
						if ($custom_field_value_query->num_rows) {
							$custom_field_value = $custom_field_value_query->row['name'];
						}
						if ($comparison == $custom_field_id) {
							if (empty($custom_field_value) && empty($values[0]) || !empty($custom_field_value) && $this->inRange(strtolower($custom_field_value), $values, 'custom_field')) {
								continue 2;
							}
						}
					}
					continue 2;
				}
			}
			
			if (isset($rules['customer_data'])) {
				$this->commaMerge($rules['customer_data']);
				
				if ($this->customer->isLogged()) {
					$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = " . (int)$this->customer->getId())->row;
				} elseif (isset($this->session->data['guest'])) {
					$customer = $this->session->data['guest'];
				} else {
					$customer = array();
				}
				
				$customer['company'] = $address['company'];
				
				foreach ($rules['customer_data'] as $comparison => $values) {
					if (!isset($customer[$comparison])) $customer[$comparison] = '';
					
					if (empty($values[0])) {
						if (empty($customer[$comparison])) {
							$this->logMessage('"' . $row['name'] . '" ignored for violating rule "' . $comparison . ' must be filled in"');
							continue 2;
						}
					} else {
						$pass = false;
						
						foreach ($values as $value) {
							if (substr($value, 0, 1) == '!') {
								$value = trim(substr($value, 1));
								$negate = true;
							} else {
								$negate = false;
							}
							
							if ($negate) {
								if ($this->inRange($customer[$comparison], array($value), 'customer_data ' . $comparison . ' not')) {
									continue 3;
								} else {
									$pass = true;
								}
							} else {
								if ($this->inRange($customer[$comparison], array($value), 'customer_data ' . $comparison)) {
									$pass = true;
								}
							}
						}
						
						if (!$pass) {
							continue 2;
						}
					}
				}
			}
			
			if (isset($rules['past_orders'])) {
				$this->commaMerge($rules['past_orders']);
				
				$coupon_sql = "";
				$dates_sql = "";
				$days_sql = "";
				$order_status_sql = " AND o.order_status_id > 0";
				$product_sql = "";
				$total_table = "o.";
				
				foreach ($rules['past_orders'] as $comparison => $values) {
					if ($comparison == 'coupon_used' || $comparison == 'coupon_unused') {
						$this->db->query("SET group_concat_max_len = 9999");
					}
					
					if ($comparison == 'date') {
						$value = array_pop($values);
						$dates = explode('::', $value);
						
						$dates_sql = " AND o.date_added >= '" . $this->db->escape($dates[0]) . "'";
						if (isset($dates[1])) {
							$dates_sql .= " AND o.date_added <= '" . $this->db->escape($dates[1]) . "'";
						}
					}
					
					if ($comparison == 'days') {
						$value = array_pop($values);
						$days = explode('-', $value);
						if ($days[0] - 1 <= 0) {
							$days_sql = " AND o.date_added <= NOW()";
						} else {
							$days_sql = " AND o.date_added <= (CURDATE() - INTERVAL " . ($days[0] - 1) . " DAY)";
						}
						if (isset($days[1])) {
							$days_sql .= " AND o.date_added >= (CURDATE() - INTERVAL " . $days[1] . " DAY)";
						}
					}
					
					$values = array_map('intval', $values);
					
					if ($comparison == 'order_status') {
						$order_status_sql = " AND o.order_status_id IN (" . implode(",", $values) . ")";
					}
					
					if ($comparison == 'category') {
						$category_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE category_id IN (" . implode(",", $values) . ")");
						$product_ids = array(0);
						foreach ($category_query->rows as $row) {
							$product_ids[] = (int)$row['product_id'];
						}
						$product_sql .= " AND op.product_id IN (" . implode(",", $product_ids) . ")";
						$total_table = "op.";
					}
					
					if ($comparison == 'manufacturer') {
						$manufacturer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE manufacturer_id IN (" . implode(",", $values) . ")");
						$product_ids = array(0);
						foreach ($manufacturer_query->rows as $row) {
							$product_ids[] = (int)$row['product_id'];
						}
						$product_sql .= " AND op.product_id IN (" . implode(",", $product_ids) . ")";
						$total_table = "op.";
					}
					
					if ($comparison == 'product') {
						$product_sql .= " AND op.product_id IN (" . implode(",", $values) . ")";
						$total_table = "op.";
					}
				}
				
				$past_orders_query = $this->db->query("SELECT IFNULL(GROUP_CONCAT(DISTINCT(LCASE(ch.coupon_id)) SEPARATOR ','), '') AS coupons, IFNULL(MIN(ROUND((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(o.date_added)) / 86400)), 0) AS days, IFNULL(COUNT(*), 0) AS quantity, IFNULL(AVG(" . $total_table . "total), 0) AS average, IFNULL(SUM(" . $total_table . "total), 0) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (op.order_id = o.order_id) LEFT JOIN " . DB_PREFIX . "coupon_history ch ON (ch.order_id = o.order_id) WHERE o.customer_id = " . (int)$customer_id . " AND o.customer_id != 0 " . $coupon_sql . $dates_sql . $days_sql . $order_status_sql . $product_sql);
				
				$coupons = explode(',', $past_orders_query->row['coupons']);
				
				foreach ($rules['past_orders'] as $comparison => $values) {
					if (in_array($comparison, array('date', 'order_status', 'category', 'manufacturer', 'product'))) {
						continue;
					}
					
					if ($comparison == 'coupon_used') {
						if (!array_intersect($values, $coupons)) {
							$this->logMessage('"' . $row['name'] . '" disabled for violating rule "past order ' . $comparison . ' = ' . implode(', ', $values) . '"');
							continue 2;
						}
					} elseif ($comparison == 'coupon_unused') {
						if (array_intersect($values, $coupons)) {
							$this->logMessage('"' . $row['name'] . '" disabled for violating rule "past order ' . $comparison . ' = ' . implode(', ', $values) . '"');
							continue 2;
						}
					} elseif ($comparison == 'order_amount') {
						$skip = true;
						$single_orders_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` o WHERE o.customer_id = " . (int)$customer_id . " AND o.customer_id != 0 " . $dates_sql . $days_sql . $order_status_sql);
						
						foreach ($single_orders_query->rows as $order) {
							$order_query = $this->db->query("SELECT SUM(op.total) AS order_amount FROM " . DB_PREFIX . "order_product op WHERE op.order_id = " . (int)$order['order_id'] . $product_sql);
							if ($this->inRange($order_query->row[$comparison], $values, 'past order ' . $comparison, true)) {
								$skip = false;
								break;
							}
						}
						
						if ($skip) {
							continue 2;
						}
					} elseif (!$this->inRange($past_orders_query->row[$comparison], $values, 'past order ' . $comparison)) {
						continue 2;
					}
				}
			}
						
			if (isset($rules['shipping_cost'])) {
				$this->commaMerge($rules['shipping_cost']);
				
				foreach ($rules['shipping_cost'] as $comparison => $brackets) {
					$in_range = $this->inRange($shipping_cost, $brackets, 'shipping_cost' . ($comparison == 'not' ? ' not' : ''));
					
					if (($comparison == 'is' && !$in_range) || ($comparison == 'not' && $in_range)) {
						continue 2;
					}
				}
			}
			
			if (isset($rules['shipping_rate'])) {
				$this->commaMerge($rules['shipping_rate']);
				$is_rule_passed = empty($rules['shipping_rate']['is']);
				$not_rule_violation = false;
				$skip_message = '';
				
				foreach ($rules['shipping_rate'] as $comparison => $values) {
					foreach ($values as $value) {
						if ($comparison == 'is') {
							if (strpos($shipping_rate, $value) !== false) {
								$is_rule_passed = true;
							} else {
								$skip_message = '"' . $row['name'] . '" disabled for violating rule "shipping_rate ' . $comparison . ' ' . $value . '"';
							}
						}
						if ($comparison == 'not') {
							if (strpos($shipping_rate, $value) !== false) {
								$not_rule_violation = true;
								$skip_message = '"' . $row['name'] . '" disabled for violating rule "shipping_rate ' . $comparison . ' ' . $value . '"';
							}
						}
					}
				}
				
				if (!$is_rule_passed || $not_rule_violation) {
					$this->logMessage($skip_message);
					continue;
				}
			}
			
			// Generate comparison values
			$cart_criteria = array(
				'length',
				'width',
				'height',
				'lwh',
				'price',
				'quantity',
				'stock',
				'total',
				'volume',
				'weight',
			);
			
			foreach ($cart_criteria as $spec) {
				${$spec.'s'} = array();
				if (isset($rules[$spec])) {
					$this->commaMerge($rules[$spec]);
				}
			}
			
			$attributes = array();
			$attribute_groups = array();
			$attribute_values = array();
			$categorys = array();
			$filters = array();
			$manufacturers = array();
			$options = array();
			$option_values = array();
			$option_array = array();
			$products = array();
			
			$other_product_data_charges = array();
			$product_keys = array();
			$total_value = $cumulative_total_value;
			
			foreach ($cart_products as $product) {
				if ($this->type == 'shipping' && !$product['shipping']) {
					$total_value -= $product['total'];
					$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') does not require shipping and was ignored');
					continue;
				}
				
				// extension-specific
				if (in_array($product['product_id'], array_keys($product_ids_to_add))) {
					continue;
				}
				
				// check if Special and Discount products should be ignored
				if (isset($rules['ignore_specials'])) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);
					
					if ($product_info['special']) {
						$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') has a qualifying Special price, so was ignored');
						continue;
					}
					
					//$related_options_special_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "relatedoptions ro LEFT JOIN " . DB_PREFIX . "relatedoptions_special ros ON (ro.relatedoptions_id = ros.relatedoptions_id) WHERE ro.product_id = " . (int)$product['product_id'] . " AND ros.customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " ORDER BY ros.priority ASC, ros.price ASC LIMIT 1");
					//$related_options_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "relatedoptions ro LEFT JOIN " . DB_PREFIX . "relatedoptions_discount rod ON (ro.relatedoptions_id = rod.relatedoptions_id) WHERE ro.product_id = " . (int)$product['product_id'] . " AND rod.customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND rod.quantity <= " . (int)$product['quantity'] . " ORDER BY rod.quantity DESC, rod.priority ASC, rod.price ASC LIMIT 1");
					
					$product_id_quantity = 0;
					foreach ($cart_products as $p) {
						if ($p['product_id'] == $product['product_id']) {
							$product_id_quantity += $product['quantity'];
						}
					}
					
					$product_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = " . (int)$product['product_id'] . " AND customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND quantity <= " . (int)$product_id_quantity . " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
					
					if ($product_discount_query->num_rows) {
						$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') has a qualifying Discount price, so was ignored');
						continue;
					}
				}
				
				// get extra product data
				$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id']);
				
				// dimensions
				$length_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class WHERE length_class_id = " . (int)$product['length_class_id']);
				if ($length_class_query->num_rows) {
					$lengths[$product['key']] = $this->length->convert($product['length'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$widths[$product['key']] = $this->length->convert($product['width'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$heights[$product['key']] = $this->length->convert($product['height'], $product['length_class_id'], $this->config->get('config_length_class_id'));
					$lwhs[$product['key']] = $lengths[$product['key']] + $widths[$product['key']] + $heights[$product['key']];
				} else {
					$message = $product['name'] . ' (product_id: ' . $product['product_id'] . ') does not have a valid length class, which causes a "Division by zero" error, and means it cannot be used for dimension/volume calculations. You can fix this by re-saving the product data.';
					$this->log->write($message);
					$this->logMessage($message);
					
					$lengths[$product['key']] = 0;
					$widths[$product['key']] = 0;
					$heights[$product['key']] = 0;
					$lwhs[$product['key']] = 0;
				}
				
				// price
				$prices[$product['key']] = $product['price'];
				
				// quantity
				$quantitys[$product['key']] = $product['quantity'];
				
				// stock
				$stocks[$product['key']] = $product_query->row['quantity'] - $product['quantity'];
				
				foreach ($product['option'] as $option) {
					$option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = " . (int)$option['product_option_value_id']);
					if ($option_query->num_rows) {
						$stocks[$product['key']] = min($stocks[$product['key']], $option_query->row['quantity'] - $product['quantity']);
					}
				}
				
				// total
				if (isset($rules['total_value'])) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);
					$product_price = ($product_info['special']) ? $product_info['special'] : $product_info['price'];
					
					if (in_array('prediscounted', $rules['total_value'][''])) {
						$totals[$product['key']] = $product['total'] + ($product['quantity'] * ($product_query->row['price'] - $product_price));
					} elseif (in_array('nondiscounted', $rules['total_value'][''])) {
						$product_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = " . (int)$product['product_id'] . " AND customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND quantity <= " . (int)$product['quantity'] . " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
						$totals[$product['key']] = ($product_info['special'] || $product_discount_query->num_rows) ? 0 : $product['total'];
					} elseif (in_array('taxed', $rules['total_value'][''])) {
						$totals[$product['key']] = $this->tax->calculate($product['total'], $product['tax_class_id']);
					} elseif (in_array('ignoreoptions', $rules['total_value'][''])) {
						$totals[$product['key']] = $product_price * $product['quantity'];
					}
				}
				if (!isset($totals[$product['key']])) {
					$totals[$product['key']] = $product['total'];
				}
				
				// volume
				$volumes[$product['key']] = $lengths[$product['key']] * $widths[$product['key']] * $heights[$product['key']] * $product['quantity'];
				
				// weight
				$weight_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class WHERE weight_class_id = " . (int)$product['weight_class_id']);
				if ($weight_class_query->num_rows) {
					$weights[$product['key']] = $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
				} else {
					$message = $product['name'] . ' (product_id: ' . $product['product_id'] . ') does not have a valid weight class, which causes a "Division by zero" error, and means it cannot be used for weight calculations. You can fix this by re-saving the product data.';
					$this->log->write($message);
					$this->logMessage($message);
					
					$weights[$product['key']] = 0;
				}
				
				// attributes
				$attribute_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (pa.attribute_id = a.attribute_id) WHERE pa.product_id = " . (int)$product['product_id']);
				if ($attribute_query->num_rows) {
					foreach ($attribute_query->rows as $attribute) {
						$attributes[$product['key']][] = $attribute['attribute_id'];
						$attribute_groups[$product['key']][] = $attribute['attribute_group_id'];
						foreach (explode(',', $attribute['text']) as $attribute_value) {
							$attribute_values[$product['key']][$attribute['attribute_id']][] = trim($attribute_value);
						}
					}
				} else {
					$attributes[$product['key']][] = 0;
					$attribute_groups[$product['key']][] = 0;
					$attribute_values[$product['key']][0][] = 0;
				}
				
				// categories
				$category_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = " . (int)$product['product_id']);
				if ($category_query->num_rows) {
					foreach ($category_query->rows as $category) {
						$categorys[$product['key']][] = $category['category_id'];
					}
				} else {
					$categorys[$product['key']][] = 0;
				}
				
				// filters
				if (version_compare(VERSION, '1.5.5', '>=')) {
					$filter_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "filter_description fd LEFT JOIN " . DB_PREFIX . "product_filter pf ON (fd.filter_id = pf.filter_id) WHERE pf.product_id = " . (int)$product['product_id']);
					if ($filter_query->num_rows) {
						foreach ($filter_query->rows as $filter) {
							$filters[$product['key']][] = $filter['filter_id'];
						}
					} else {
						$filters[$product['key']][] = 0;
					}
				}
				
				// manufacturer
				$manufacturers[$product['key']][] = $product_query->row['manufacturer_id'];
				
				// options
				if (!empty($product['option'])) {
					foreach ($product['option'] as $option) {
						$options[$product['key']][] = $option['option_id'];
						$option_values[$product['key']][] = $option['option_value_id'];
						$option_array[$product['key']][$option['option_id']][] = $option['value'];
					}
				} else {
					$options[$product['key']][] = 0;
					$option_values[$product['key']][] = 0;
					$option_array[$product['key']][0][] = 0;
				}
				
				// products
				$products[$product['key']][] = $product['product_id'];
				
				// Check item criteria (entire cart comparisons)
				foreach ($cart_criteria as $spec) {
					/* extension-specific
					if (isset($rules['adjust']['item_' . $spec])) {
						foreach ($rules['adjust']['item_' . $spec] as $adjustment) {
							${$spec.'s'}[$product['key']] += (strpos($adjustment, '%')) ? ${$spec.'s'}[$product['key']] * (float)$adjustment / 100 : (float)$adjustment;
						}
					}
					*/
					
					$spec_value = ${$spec.'s'}[$product['key']];
					if ($spec == 'weight' || $spec == 'length' || $spec == 'width' || $spec == 'height') $spec_value /= $product['quantity'];
					
					if (isset($rules[$spec]['entire_any'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['entire_any'], $spec . ' of any item in entire cart', true)) {
							continue 2;
						}
					}
					
					if (isset($rules[$spec]['entire_every'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['entire_every'], $spec . ' of every item in entire cart', true)) {
							continue 3;
						}
					}
				}
				
				// Check product criteria
				if (isset($rules['attribute'])) {
					$this->commaMerge($rules['attribute']);
					
					foreach ($rules['attribute'] as $attribute_id => $values) {
						$attribute_rule_text = 'attribute_id ' . $attribute_id . ' = ' . implode(', ', $values);
						if (empty($values[0]) && isset($attribute_values[$product['key']][$attribute_id])) {
							continue;
						} elseif (isset($attribute_values[$product['key']][$attribute_id])) {
							foreach ($attribute_values[$product['key']][$attribute_id] as $attribute_value) {
								if ($this->inRange(strtolower($attribute_value), $values, 'attribute', true)) {
									continue 2;
								}
							}
						}
						$this->logMessage('Product "' . $product['name'] . ' (product_id: ' . $product['product_id'] . ') is not eligible for charge "' . $row['name'] . '" because it violates rule "' . $attribute_rule_text . '"');
						continue 2;
					}
				}
				
				foreach (array('attribute_group', 'category') as $criteria) {
					if (isset($rules[$criteria])) {
						if ($this->ruleViolation($criteria, ${$criteria . 's'}[$product['key']], $product['name'] . ' (product_id: ' . $product['product_id'] . ')')) {
							continue 2;
						}
					}
				}
				
				if (isset($rules['option'])) {
					$fail = false;
					
					foreach ($rules['option'] as $option_id => $values) {
						if (empty($values[0]) && isset($option_array[$product['key']][$option_id])) {
							continue;
						} else {
							$pass = false;
							
							foreach ($values as $value) {
								$value = strtolower(trim($value));
								if (substr($value, 0, 1) == '!') {
									$value = substr($value, 1);
									$negate = true;
								} else {
									$negate = false;
								}
								
								if (!isset($option_array[$product['key']][$option_id])) {
									$in_range = false;
								} else {
									foreach ($option_array[$product['key']][$option_id] as $option_value) {
										$in_range = $this->inRange(strtolower($option_value), array($value), 'option', true);
										if ($in_range) break;
									}
								}
								
								if ($negate) {
									if ($in_range) {
										$fail = true;
									} else {
										$pass = true;
									}
								} elseif ($in_range) {
									$pass = true;
								}
							}
							
							if (!$pass || $fail) {
								$fail = true;
								$option_rule_text = 'option_id ' . $option_id . ' = ' . implode('; ', $values);
							}
						}
					}
					
					if ($fail) {
						$this->logMessage('Product "' . $product['name'] . ' (product_id: ' . $product['product_id'] . ') is not eligible for charge "' . $row['name'] . '" because it violates rule "' . $option_rule_text . '"');
						continue;
					}
				}
				
				if (isset($rules['filter']) && $this->ruleViolation('filter', $filters[$product['key']], $product['name'] . ' (product_id: ' . $product['product_id'] . ')')) {
					continue;
				}
				
				if (isset($rules['manufacturer']) && $this->ruleViolation('manufacturer', $product_query->row['manufacturer_id'], $product['name'] . ' (product_id: ' . $product['product_id'] . ')')) {
					continue;
				}
				
				if (isset($rules['product']) && $this->ruleViolation('product', $product['product_id'], $product['name'] . ' (product_id: ' . $product['product_id'] . ')')) {
					continue;
				}
				
				if (isset($rules['recurring_profile']) && $this->ruleViolation('recurring_profile', $product['recurring']['recurring_id'], $product['name'] . ' (product_id: ' . $product['product_id'] . ')')) {
					continue;
				}
				
				// Check item criteria (eligible item comparisons)
				foreach ($cart_criteria as $spec) {
					$spec_value = ${$spec.'s'}[$product['key']];
					if ($spec == 'weight' || $spec == 'length' || $spec == 'width' || $spec == 'height') $spec_value /= $product['quantity'];
					
					if (isset($rules[$spec]['any'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['any'], $spec . ' of any item', true)) {
							continue 2;
						}
					}
					
					// rules with "of every item" comparisons are checked after Product Group rules below
				}
				
				// Check other product data
				if (isset($rules['other_product_data'])) {
					$this->commaMerge($rules['other_product_data']);
					foreach ($rules['other_product_data'] as $comparison => $values) {
						/* extension-specific
						if ($values[0] == '') {
							if ($charge['type'] == 'flat') {
								$other_product_data_charges[] = (float)$product_query->row[$comparison];
							} elseif ($charge['type'] == 'peritem') {
								$other_product_data_charges[] = (float)($product_query->row[$comparison] * $product['quantity']);
							} else {
								$brackets = array_filter(explode(',', $product_query->row[$comparison]));
								$other_product_data_charges[] = (float)$this->calculateBrackets($brackets, $row['type'], ${$row['type'].'s'}[$product['key']], $product['quantity'], $product['total']);
							}
							continue;
						}
						*/
						if (!$this->inRange(strtolower($product_query->row[$comparison]), $values, 'other product data')) {
							continue 2;
						}
					}
				}
				
				// product passed all rules and is eligible for charge
				$product_keys[] = $product['key'];
			}
			
			// Check "Quantity of Product" rules
			if (isset($rules['quantity_of_product'])) {
				$this->commaMerge($rules['quantity_of_product']);
				
				foreach ($rules['quantity_of_product'] as $product_id => $quantity_ranges) {
					$pass = false;
					
					foreach ($cart_products as $product) {
						if ($product['product_id'] == $product_id && $this->inRange($product['quantity'], $quantity_ranges, 'quantity_of_product', true)) {
							$pass = true;
						}
					}
					
					if (!$pass) {
						$product_name = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = " . (int)$product_id . " AND language_id = " . (int)$this->config->get('config_language_id'))->row['name'];
						$this->logMessage('"' . $this->row['name'] . '" disabled for violating rule "quantity of ' . $product_name . ' [' . $product_id . '] = ' . implode(',', $quantity_ranges) . '"');
						continue 2;
					}
				}
			}
			
			// Check "Quantity of Group" rules
			if (isset($rules['quantity_of_group'])) {
				$this->commaMerge($rules['quantity_of_group']);
				
				foreach ($rules['quantity_of_group'] as $product_group_id => $quantity_ranges) {
					$pass = false;
					$members_array = array();
					
					foreach ($settings['product_group'][$product_group_id]['member'] as $member) {
						$bracket = strrpos($member, '[');
						$colon = strrpos($member, ':');
						$member_type = substr($member, $bracket + 1, $colon - $bracket - 1);
						$member_id = substr($member, $colon + 1, -1);
						$members_array[$member_type][] = $member_id;
						
						if ($member_type == 'category' && $settings['product_group'][$product_group_id]['subcategories']) {
							$child_category_ids = $this->getChildCategoryIds($member_id);
							foreach ($child_category_ids as $child_category_id) {
								$members_array[$member_type][] = $child_category_id;
							}
						}
					}
					
					$group_quantity = 0;
					
					foreach ($cart_products as $product) {
						foreach ($members_array as $type => $members) {
							if (!empty(${$type.'s'}[$product['key']]) && array_intersect(${$type.'s'}[$product['key']], $members)) {
								$group_quantity += $product['quantity'];
								break;
							}
						}
					}
					
					if (!$this->inRange($group_quantity, $quantity_ranges, 'quantity_of_group', true)) {
						$this->logMessage('"' . $this->row['name'] . '" disabled for violating rule "quantity of ' . $settings['product_group'][$product_group_id]['name'] . ' = ' . implode(',', $quantity_ranges) . '"');
						continue 2;
					}
				}
			}
			
			// Check product group rules
			$row_disabled_text = '"' . $this->row['name'] . '" ignored';
			
			if (isset($rules['product_group'])) {
				$list_types = array(
					'attribute',
					'attribute_group',
					'category',
					'filter',
					'manufacturer',
					'option',
					'option_value',
					'product',
				);
				
				foreach ($list_types as $list_type) {
					${$list_type . 's_array'} = array();
					foreach (${$list_type . 's'} as $list) {
						${$list_type . 's_array'} = array_merge(${$list_type . 's_array'}, $list);
					}
				}
				
				$eligible_products = array();
				$ineligible_products = array();
				
				foreach ($rules['product_group'] as $comparison => $product_group_ids) {
					$rule_satisfied = false;
					
					foreach ($product_group_ids as $product_group_id) {
						if (empty($settings['product_group'][$product_group_id]['member'])) continue;
						
						$product_group_rule_text = 'cart has items from ' . ($comparison == 'none' ? 'none of the' : $comparison) . ' members of ' . $settings['product_group'][$product_group_id]['name'];
						unset($members_array);
						
						foreach ($settings['product_group'][$product_group_id]['member'] as $member) {
							$bracket = strrpos($member, '[');
							$colon = strrpos($member, ':');
							$member_type = substr($member, $bracket + 1, $colon - $bracket - 1);
							$member_id = substr($member, $colon + 1, -1);
							$members_array[$member_type][] = $member_id;
							
							if ($member_type == 'category' && $settings['product_group'][$product_group_id]['subcategories']) {
								$child_category_ids = $this->getChildCategoryIds($member_id);
								foreach ($child_category_ids as $child_category_id) {
									$members_array[$member_type][] = $child_category_id;
								}
							}
						}
						
						foreach ($members_array as $type => $members) {
							// Check "all", "onlyall", and "none" comparisons
							if (($comparison == 'all' || $comparison == 'onlyall') && array_diff($members, ${$type.'s_array'})) {
								$this->logMessage($row_disabled_text . ' for violating product group rule "' . $product_group_rule_text . '", due to missing ' . $type . '_id(s) "' . implode(', ', array_diff($members, ${$type.'s_array'})) . '"');
								continue 4;
							}
							
							if (($comparison == 'not' || $comparison == 'none') && empty($cart_products)) {
								$rule_satisfied = true;
							}
							
							// Check product eligibility
							foreach ($cart_products as $product) {
								if ($this->type == 'shipping' && !$product['shipping']) {
									continue;
								}
								
								// extension-specific
								if (empty(${$type.'s'}[$product['key']])) continue;
								// end
								
								if ($type == 'category') {
									if (($comparison == 'onlyany' || $comparison == 'onlyall') && array_intersect(${$type.'s'}[$product['key']], $members)) {
										$rule_satisfied = true;
										$eligible_products[] = $product['key'];
										continue;
									}
									if ($comparison == 'not' && array_intersect(${$type.'s'}[$product['key']], $members)) {
										$ineligible_products[] = $product['key'];
										continue;
									}
								}
								
								if ((($comparison == 'onlyany' || $comparison == 'onlyall') && array_diff(${$type.'s'}[$product['key']], $members)) ||
									($comparison == 'none' && array_intersect(${$type.'s'}[$product['key']], $members))
								) {
									$this->logMessage($row_disabled_text . ' for violating product group rule "' . $product_group_rule_text . '"');
									continue 5;
								} elseif (($comparison != 'not' && $comparison != 'none' && !array_intersect(${$type.'s'}[$product['key']], $members)) ||
									(($comparison == 'not' || $comparison == 'none') && !array_diff(${$type.'s'}[$product['key']], $members))
								) {
									$ineligible_products[] = $product['key'];
								} else {
									$rule_satisfied = true;
									if ($comparison != 'not' && $comparison != 'none') {
										$eligible_products[] = $product['key'];
									}
								}
							}
						}
					}
					
					// Check that rule has at least one matching product
					if (!$rule_satisfied) {
						$this->logMessage($row_disabled_text . ' for having no eligible products');
						continue 2;
					}
				}
				
				// Remove ineligible products
				foreach ($ineligible_products as $ineligible_key) {
					if (in_array($ineligible_key, $eligible_products)) continue;
					foreach ($product_keys as $index => $product_key) {
						if ($product_key == $ineligible_key) {
							$total_value -= $totals[$product_key];
							unset($product_keys[$index]);
						}
					}
				}
			}
			
			// Check "of every item" rules
			foreach ($product_keys as $index => $product_key) {
				foreach ($cart_criteria as $spec) {
					$spec_value = ${$spec.'s'}[$product_key];
					if ($spec == 'weight' || $spec == 'length' || $spec == 'width' || $spec == 'height') $spec_value /= $product['quantity'];
					
					if (isset($rules[$spec]['every'])) {
						if (!$this->inRange($spec_value, $rules[$spec]['every'], $spec . ' of every item')) {
							continue 3;
						}
					}
				}
			}
			
			// Check for empty product list
			if (empty($product_keys)) {
				$disable_charge = true;
				
				if (!empty($this->session->data['vouchers'])) {
					$disable_charge = false;
					foreach ($rules as $type => $value) {
						if (in_array($type, array('attribute', 'attribute_group', 'category', 'manufacturer', 'option', 'product', 'product_group', 'other_product_data'))) {
							$disable_charge = true;
						}
					}
				}
				
				if ($disable_charge) {
					$this->logMessage($row_disabled_text . ' for having no eligible products');
					continue;
				}
			}
			
			// Check cart criteria and generate total comparison values
			$single_foreign_currency = (isset($rules['currency']['is']) && count($rules['currency']['is']) == 1 && $main_currency != $currency) ? $rules['currency']['is'][0] : '';
			
			foreach ($cart_criteria as $spec) {
				// note: cart_comparison to be added here if requested
				if ($spec == 'total' && isset($rules['total_value']) && in_array('total', $rules['total_value'][''])) {
					$total = $total_value;
					$cart_total = $total_value;
				} else {
					${$spec} = 0;
					foreach ($product_keys as $product_key) {
						${$spec} += ${$spec.'s'}[$product_key];
					}
					${'cart_'.$spec} = array_sum(${$spec.'s'});
				}
				
				if ($spec == 'total' && $single_foreign_currency) {
					$total = $this->currency->convert($total, $main_currency, $single_foreign_currency);
				}
				
				/* extension-specific
				if (isset($rules['adjust']['cart_' . $spec])) {
					foreach ($rules['adjust']['cart_' . $spec] as $adjustment) {
						${$spec} += (strpos($adjustment, '%')) ? ${$spec} * (float)$adjustment / 100 : (float)$adjustment;
						${'cart_'.$spec} += (strpos($adjustment, '%')) ? ${'cart_'.$spec} * (float)$adjustment / 100 : (float)$adjustment;
					}
				}
				*/
				
				if (isset($rules[$spec]['cart'])) {
					if (!$this->inRange(${$spec}, $rules[$spec]['cart'], $spec . ' of cart')) {
						continue 2;
					}
				}
				
				if (isset($rules[$spec]['entire_cart'])) {
					if (!$this->inRange(${'cart_'.$spec}, $rules[$spec]['entire_cart'], $spec . ' of entire cart')) {
						continue 2;
					}
				}
			}
			
			// All rules have been passed
			$this->logMessage("\n" . '"' . $row['name'] . '" passed all rules, so the following products will be auto-added: ' . substr(print_r($row['product'], true), 5));
			
			if (!empty($row['option'])) {
				$this->logMessage('with the following options:' . "\n\n" . $row['option'] . "\n");
			}
			
			// Calculate quantity to add
			if ($row['quantity_add_type'] == 'item') {
				$quantity_to_add = $row['quantity_to_add'];
			} else {
				$quantity_to_add = 0;
				foreach ($product_keys as $product_key) {
					$quantity_to_add += $quantitys[$product_key];
				}
				
				$fraction = explode('/', $row['quantity_to_add']);
				
				if (empty($fraction[1])) {
					$quantity_to_add = $quantity_to_add * $fraction[0];
				} else {
					$quantity_to_add = $quantity_to_add * $fraction[0] / $fraction[1];
				}
				
				$quantity_to_add = floor($quantity_to_add);
			}
			
			// Get product_ids to add
			foreach ($product_ids_to_add as $product_id => $options) {
				$product_to_add = array(
					'product_id'	=> $product_id,
					'option'		=> $options,
					'quantity'		=> $quantity_to_add,
				);
				
				$auto_add_products[] = $product_to_add;
				
				if ($row['auto_added_free']) {
					$this->session->data['auto_added_free_pro'][] = array(
						'product_id'	=> $product_id,
						'option'		=> $options,
					);
				}
				
				if ($row['auto_added_removable']) {
					$auto_added_removable[] = $product_to_add;
				}
			}
			
		} // end row loop
		
		// Remove products from cart
		foreach ($auto_remove_products as $product_id => $options) {
			if (version_compare(VERSION, '2.1', '<')) {
				if ($options) {
					$key = base64_encode(serialize(array('product_id' => $product_id, 'option' => $options)));
				} else {
					$key = base64_encode(serialize(array('product_id' => $product_id)));
				}
			} else {
				$api_sql = (version_compare(VERSION, '2.3', '<')) ? "" : " AND api_id = " . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0);
				$key = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE product_id = " . (int)$product_id . " AND `option` = '" . $this->db->escape(json_encode($options)) . "' AND customer_id = " . (int)$customer_id . " AND session_id = '" . $this->db->escape($this->session->getId()) . "'" . $api_sql);
				
				if (isset($key->row['cart_id'])) {
					$key = $key->row['cart_id'];
				} else {
					continue;
				}
			}
			
			$this->cart->remove($key);
		}
		
		// Add products to cart
		foreach ($auto_add_products as $product) {
			$product_without_quantity = array(
				'product_id'	=> $product['product_id'],
				'option'		=> $product['option'],
			);
			
			if (in_array($product, $auto_added_removable) && isset($this->session->data['manually_removed_products_pro']) && in_array($product_without_quantity, $this->session->data['manually_removed_products_pro'])) {
				continue;
			}
			
			if (version_compare(VERSION, '2.1', '<')) {
				$key = base64_encode(serialize(array('product_id' => $product['product_id'], 'option' => $product['option'])));
				$current_quantity = (isset($this->session->data['cart'][$key])) ? $this->session->data['cart'][$key] : 0;
			} else {
				$api_sql = (version_compare(VERSION, '2.3', '<')) ? "" : " AND api_id = " . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0);
				$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE product_id = " . (int)$product['product_id'] . $api_sql . " AND `option` = '" . $this->db->escape(json_encode($product['option'])) . "' AND customer_id = " . (int)$customer_id . " AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
				$current_quantity = ($cart_query->num_rows) ? $cart_query->row['quantity'] : 0;
			}
			
			$this->cart->add($product['product_id'], $product['quantity'] - $current_quantity, $product['option']);
		}
		
		// End
		$this->session->data['auto_add_products_pro'] = false;
	}
	
	//------------------------------------------------------------------------------
	// Private functions
	//------------------------------------------------------------------------------
	private function getSettings() {
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		$settings = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			$split_key = preg_split('/_(\d+)_?/', str_replace($code . '_', '', $setting['key']), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
				if (count($split_key) == 1)	$settings[$split_key[0]] = $value;
			elseif (count($split_key) == 2)	$settings[$split_key[0]][$split_key[1]] = $value;
			elseif (count($split_key) == 3)	$settings[$split_key[0]][$split_key[1]][$split_key[2]] = $value;
			elseif (count($split_key) == 4)	$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]] = $value;
			else 							$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]][$split_key[4]] = $value;
		}
		
		return $settings;
	}
	
	private function curlRequest($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $response;
	}
	
	private function logMessage($message) {
		if ($this->testing_mode) {
			file_put_contents(DIR_LOGS . $this->name . '.messages', print_r($message, true) . "\n", FILE_APPEND|LOCK_EX);
		}
	}
	
	private function commaMerge(&$rule) {
		$merged_rule = array();
		foreach ($rule as $comparison => $values) {
			$merged_rule[$comparison] = array();
			foreach ($values as $value) {
				$merged_rule[$comparison] = array_merge($merged_rule[$comparison], array_map('trim', explode(',', strtolower($value))));
			}
		}
		$rule = $merged_rule;
	}
	
	private function ruleViolation($rule, $value, $product_name = '') {
		$violation = false;
		$rules = $this->row['rules'];
		$function = (is_array($value)) ? 'array_intersect' : 'in_array';
		
		if (isset($rules[$rule]['after']) && strtotime($value) < min(array_map('strtotime', $rules[$rule]['after']))) {
			$violation = true;
			$comparison = 'after';
		}
		if (isset($rules[$rule]['before']) && strtotime($value) > max(array_map('strtotime', $rules[$rule]['before']))) {
			$violation = true;
			$comparison = 'before';
		}
		if (isset($rules[$rule]['is']) && !$function($value, $rules[$rule]['is'])) {
			$violation = true;
			$comparison = 'is';
		}
		if (isset($rules[$rule]['not']) && $function($value, $rules[$rule]['not'])) {
			$violation = true;
			$comparison = 'not';
		}
		
		if ($violation && $rule != 'category' && $rule != 'manufacturer' && $rule != 'product') {
			if ($product_name) {
				$this->logMessage($product_name . ' violates "' . $this->row['name'] . '" rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '" and so was ignored');
			} else {
				$this->logMessage('"' . $this->row['name'] . '" ignored for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
			}
		}
		
		return $violation;
	}
	
	private function inRange($value, $range_list, $charge_type = '', $skip_testing = false) {
		$in_range = false;
		
		foreach ($range_list as $range) {
			if ($range == '') continue;
			
			$range = (strpos($range, '::')) ? explode('::', $range) : explode('-', $range);
			
			if (strpos($charge_type, 'distance') === 0) {
				if (empty($range[1])) {
					array_unshift($range, 0);
				}
				if ($value >= (float)$range[0] && $value <= (float)$range[1]) {
					$in_range = true;
				}
			} elseif (strpos($charge_type, 'postcode') === 0) {
				$postcode = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
				$from = preg_replace('/[^A-Z0-9]/', '', strtoupper($range[0]));
				$to = (isset($range[1])) ? preg_replace('/[^A-Z0-9]/', '', strtoupper($range[1])) : $from;
				
				if (strlen($from) < 3 && !preg_match('/[0-9]/', $from)) $from .= '1';
				if (strlen($to) < 3 && !preg_match('/[0-9]/', $to)) $to .= '99';
				
				if (strlen($from) < strlen($postcode)) $from = str_pad($from, max(strlen($postcode), strlen($from) + 3), ' ');
				if (strlen($to) < strlen($postcode)) $to = str_pad($to, max(strlen($postcode), strlen($to) + 3), preg_match('/[A-Z]/', $postcode) ? 'Z' : '9');
				
				$postcode = substr_replace(substr_replace($postcode, ' ', -3, 0), ' ', -2, 0);
				$from = substr_replace(substr_replace($from, ' ', -3, 0), ' ', -2, 0);
				$to = substr_replace(substr_replace($to, ' ', -3, 0), ' ', -2, 0);
				
				if (strnatcasecmp($postcode, $from) >= 0 && strnatcasecmp($postcode, $to) <= 0) {
					$in_range = true;
				}
			} else {
				if (!isset($range[1]) && $charge_type != 'attribute' && $charge_type != 'custom_field' && strpos($charge_type, 'customer_data') !== 0 && $charge_type != 'option' && $charge_type != 'other product data') {
					$range[1] = 999999999;
				}
				
				if ((count($range) > 1 && $value >= $range[0] && $value <= $range[1]) || (count($range) == 1 && $value == $range[0])) {
					$in_range = true;
				}
			}
		}
		
		if (empty($value) && empty($range_list[0])) {
			$in_range = true;
		}
		
		if (!$skip_testing) {
			if (strpos($charge_type, ' not') ? $in_range : !$in_range) {
				$this->logMessage('"' . $this->row['name'] . '" ignored for violating rule "' . $charge_type . (strpos($charge_type, ' not') ? ' ' : ' is ') . implode(', ', $range_list) . '" with value "' . $value . '"');
			}
		}
		
		return $in_range;
	}
	
	private function getChildCategoryIds($parent_id) {
		$child_ids = array();
		$child_categories = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE parent_id = " . (int)$parent_id)->rows;
		foreach ($child_categories as $child_category) {
			$child_ids[] = $child_category['category_id'];
			$child_ids = array_merge($child_ids, $this->getChildCategoryIds($child_category['category_id']));
		}
		return array_unique($child_ids);
	}
}
?>
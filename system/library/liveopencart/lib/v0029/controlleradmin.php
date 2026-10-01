<?php

// the file names this way (without underscores) because of the standard opencart autoloader limitations

namespace liveopencart\lib\v0029;

class ControllerAdmin extends \Controller {
	
	use traits\language;
	use traits\simple_db;
	use traits\resource;
	use traits\html_decode;
	use traits\debug;
	
	protected $route_base      = '';
	protected $route           = '';
	protected $route_home_page = 'common/dashboard';
	protected $error           = [];
	
	public function __construct() {
		call_user_func_array( parent::class.'::'.__FUNCTION__ , func_get_args());
		$this->init();
		$this->selfControl();
	}
	
	protected function init() { 
	}
	
	protected function selfControl() {
		if (!$this->route) {
			throw new \Exception('Error: Class property "route" should be defined!');
		}
	}
	
	protected function getToken() {
		if ( VERSION >= '3.0.0.0' ) {
			return $this->session->data['user_token'];
		} else {
			return $this->session->data['token'];
		}
	}
	
	protected function getIndexDataErrors($data = []) {
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}
		
		return $data;
	}
	
	protected function getIndexData($data = []) {
		
		$data = array_merge($data, $this->getLinks());
		
		$data['user_token']            = $this->getToken();
		$data['config_admin_language'] = $this->config->get('config_admin_language');
		
		return $data;
	}

	protected function getTokenURLParam() {
		return ( VERSION >= '3.0.0.0' ? 'user_token' : 'token' ).'='.$this->getToken();
	}
	
	protected function getRouteHomePage() {
		return $this->route_home_page;
	}
	
	protected function getRoute($sub_route = '') {
		return $this->route.($sub_route ? '/'.$sub_route : '');
	}
	
	public function index() {
		return $this->indexStandard();
	}
	
	protected function indexStandard($data = [] , $sub_route = '', $custom_route = '') {

		$this->loadLanguage();
		
		$data = $this->getIndexData($data);
		$data = $this->getIndexDataErrors($data);

		$this->document->setTitle($this->getTitle());

		$this->setOutputStandard($data, $sub_route ?: ($data['sub_route'] ?? ''), $custom_route);

	}
	
	protected function setOutputStandard($data = [], $sub_route = '', $custom_route = '') {
		
		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->loadView($data, $sub_route, $custom_route));
	}
	
	protected function loadView($data = [], $sub_route = '', $custom_route = '') {
		
		if ($custom_route) {
			$route = $custom_route;
		} else {
			$route = $this->getRoute($sub_route);
		}
		
		return $this->load->view($route, $data);
	}
	
	protected function getLinks() {
	
		$data = [];

		$data['breadcrumbs'] = $this->getBreadcrumbs();
		$data['action']      = $this->getLinkWithToken($this->getRoute());

		return $data;

	}
	
	protected function getTitle() {
		return $this->language->get('heading_title');
	}
	
	protected function getLinkWithToken($route, $params = '') {
		$current_params = $params;
		if ($current_params && substr($current_params, 0, 1) != '&') {
			$current_params = '&' . $current_params;
		}
		return $this->url->link($route, $this->getTokenURLParam() . $current_params, true);
	}
	
	protected function getBreadcrumbs() {

		$breadcrumbs = [];

		$breadcrumbs[] = [
			'text' => $this->language->get('text_home') ,
			'href' => $this->getLinkWithToken($this->getRouteHomePage())
		];
		
		$breadcrumbs = array_merge($breadcrumbs, $this->getBreadcrumbsPath());

		//$breadcrumbs[] = array(
		//	'text' => $this->language->get('text_module') ,
		//	'href' => $this->getLinkWithToken($this->getRouteExtensions() , '&type=' . $this->extension_type)
		//);

		$breadcrumbs[] = $this->getBreadcrumbSelf();
		//$breadcrumbs[] = array(
		//	'text' => $this->language->get('module_name') ,
		//	'href' => $this->getLinkWithToken($this->getRouteExtension())
		//);

		return $breadcrumbs;
	}
	
	protected function getBreadcrumbsPath() {
		return []; // not path by default
	}
	
	protected function getBreadcrumbSelf() {
		$breadcrumb = [
			'text' => $this->getTitle(),
			'href' => $this->getLinkWithToken($this->getRoute()),
		];
		return $breadcrumb;
	}
	
	protected function validatePermission($permission_type = 'modify') {
		if (!$this->user->hasPermission('modify', $this->getRoute())) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}
		return true;
	}
	
	protected function setOutputJSON($json) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function validatePermissionModify() {
		return $this->validatePermission('modify');
	}
	
	protected function validatePermissionAccess() {
		return $this->validatePermission('access');
	}
}

<?php
namespace Template;
final class Twig {
	private $data = array();

	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render($filename, $code = '') {
		if (!$code) {
			$file = DIR_TEMPLATE . $filename . '.twig';

			if (is_file($file)) {
				$code = file_get_contents($file);
			} else {
				throw new \Exception('Error: Could not load template ' . $file . '!');
				exit();
			}
		}

		// initialize Twig environment
		$config = array(
			'autoescape'  => false,
			'debug'       => false,
			'auto_reload' => true,
			'cache'       => DIR_CACHE . 'template/'
		);

		try {
			$loader = new \Twig\Loader\ArrayLoader(array($filename . '.twig' => $code));


			// << LIVEOPENCART: Product Option Image Ultimate: twig-include fix
				if (version_compare(VERSION, '3.0.3.5') >= 0 && !empty($loader) && !($loader instanceof \Twig\Loader\ChainLoader)) {
                //if ((VERSION === '3.0.3.5' || VERSION === '3.0.3.6' || VERSION === '3.0.3.7' || VERSION === '3.0.3.8') && !empty($loader) && !($loader instanceof \Twig\Loader\ChainLoader)) {
				
					if (class_exists('\Twig\Loader\FilesystemLoader')) {
						$loader_fs = new \Twig\Loader\FilesystemLoader();
					} else {
						$loader_fs = new \Twig_Loader_Filesystem();
					}
					if (defined('DIR_CATALOG') && is_dir(DIR_MODIFICATION . 'admin/view/template/')) {
						$loader_fs->addPath(DIR_MODIFICATION . 'admin/view/template/');
					} elseif (is_dir(DIR_MODIFICATION . 'catalog/view/theme/')) {
						$loader_fs->addPath(DIR_MODIFICATION . 'catalog/view/theme/');
					}
					$loader_fs->addPath(DIR_TEMPLATE);
	
					$loader = new \Twig\Loader\ChainLoader(array($loader, $loader_fs));
                }  
                // >> LIVEOPENCART: Product Option Image Ultimate: twig-include fix

			$twig = new \Twig\Environment($loader, $config);

			return $twig->render($filename . '.twig', $this->data);
		} catch (Exception $e) {
			trigger_error('Error: Could not load template ' . $filename . '!');
			exit();
		}	
	}	
}

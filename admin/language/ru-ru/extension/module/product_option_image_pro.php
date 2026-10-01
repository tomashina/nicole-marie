<?php

//  Product Option Image Ultimate / Изображения опций Ultimate
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

$_['module_name'] = 'Изображения опций Ultimate';
$_['module_page'] = '
<a href="https://liveopencart.ru/opencart-moduli-shablony/moduli/opcii/izobrajeniya-optsiy-pro-3" target="_blank">'.$_['module_name'].' на liveopencart.ru</a>
';

$_lang_file = __DIR__.'/product_option_image_pro_common.php';
//if ( file_exists($_lang_file) ) {
//	require($_lang_file);
//}
if (substr($_lang_file, 0, strlen(DIR_MODIFICATION)) == DIR_MODIFICATION) {
	$_lang_file = dirname(DIR_APPLICATION).'/'.substr($_lang_file, strlen(DIR_MODIFICATION));
}
include(modification($_lang_file));

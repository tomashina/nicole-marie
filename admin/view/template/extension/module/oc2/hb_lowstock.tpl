<?php echo $header; ?><?php echo $column_left; ?>

<div id="content">
  <!--Header Start-->
  <div class="page-header">
    <div class="container-fluid">
		<?php if ($update_info) { ?>
			<div class="alert alert-warning"><i class="fa fa-fire" aria-hidden="true"></i>&nbsp;<?php echo $update_info; ?></div>
		<?php } ?>
		
      <div class="pull-right"> 
		  <button type="submit" form="form-setting" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;<?php echo $button_save; ?></button>
		  <a onclick="export2csv();" class="btn btn-success"><i class="fa fa-file-excel-o"></i>&nbsp;<?php echo $text_export; ?></a> 
		  <a href="https://www.huntbee.com/documentation/docs/low-stock-management/" target="_blank" class="btn btn-default"><i class="fa fa-book"></i>&nbsp;<?php echo $button_docs; ?></a>
          <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a> 
	  </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <!--Header End-->
  <div class="container-fluid">
	<!--Start - Error / Success Message if any -->
	<?php if ($error_warning) { ?>
		<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
	<?php } ?>
	<?php if ($success) { ?>
		<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
	<?php } ?>
	<!--End - Error / Success Message if any -->
	
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-battery-quarter"></i> <?php echo $heading_title; ?></h3>
      </div>
      <div class="panel-body">
        <div id="output-console"></div>

        <!--Tabs UL Starts-->
        <ul class="nav nav-tabs" id="tabs">
          <li class="active"><a href="#tab-products" onclick="loadBlock('products');" data-toggle="tab"><i class="fa fa-list"></i>&nbsp;<?php echo $tab_report; ?></a></li>
          <li><a href="#tab-setting" data-toggle="tab"><i class="fa fa-gear"></i>&nbsp;<?php echo $tab_setting; ?></a></li>
		  <li><a href="#tab-template" onclick="loadBlock('templates');" data-toggle="tab"><i class="fa fa-newspaper-o" aria-hidden="true"></i>&nbsp;<?php echo $tab_template; ?></a></li>
          <li><a href="#tab-logs" onclick="loadBlock('logs');" data-toggle="tab"><i class="fa fa-book"></i>&nbsp;<?php echo $tab_log; ?></a></li>
        </ul>
        <!--Tabs UL Ends-->
        <div class="tab-content">
          <!--UL TAB MAIN CONTAINER-->
          <!--LIST CONTAINER-->
          <div class="tab-pane active" id="tab-products">
			<div class="row" style="margin-bottom:10px;">
				<div class="col-sm-4">
					<div class="input-group">
						<input type="text" id="search-product-value" onkeyup="searchProduct();" class="form-control" placeholder="<?php echo $text_search_product; ?>">
						<span class="input-group-addon btn" id="search-product-button" onclick="searchProduct();"><i class="fa fa-search"></i></span> 
					</div>
				</div>

				<div class="col-sm-3">
					<?php if ($hb_lowstock_dashboard_mode == 'all') { ?>
					<div class="input-group">
						<span class="input-group-addon"><?php echo $text_category; ?></span>
						<input type="text" id="category_lookup" class="form-control" autocomplete="off" placeholder="<?php echo $text_category_lookup; ?>">
						<input type="hidden" id="search_category_id">
					</div>
					<?php } ?>
				</div>

				<div class="col-sm-3">
					<div class="input-group">
						<span class="input-group-addon"><?php echo $text_manufacturer; ?></span>
						<input type="text" id="manufacturer_lookup" class="form-control" autocomplete="off" placeholder="<?php echo $text_manufacturer_lookup; ?>">
						<input type="hidden" id="search_manufacturer_id">
					</div>
				</div>

				<div class="col-sm-2">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-filter"></i>&nbsp;<?php echo $text_status; ?></span>
						<select id="search_status" class="form-control" onchange="searchProduct();">
							<option value=""><?php echo $text_all; ?></option>
							<option value="1"><?php echo $text_enable; ?></option>
							<option value="0"><?php echo $text_disable; ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				
			</div>

			<div id="products-block"></div>
          </div>

          <div class="tab-pane" id="tab-setting"> 
				<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-setting" class="form-horizontal">
				
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_lowstock_nostock_template; ?></label>
					<div class="col-sm-4">
						<select class="form-control" name="hb_lowstock_nostock_template">
							<option value="0"> No Templates </option>
							<?php if ($email_templates) { ?>
								<?php foreach ($email_templates as $template) { ?>
									<option value="<?php echo $template['id']; ?>" <?php echo ($hb_lowstock_nostock_template == $template['id'])?'selected':'' ?>><?php echo $template['template_label']; ?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>	
					<div class="col-sm-3">
						<div class="input-group">
							<span class="input-group-addon"><i class="fa fa-envelope"></i>&nbsp;<?php echo $text_lowstock_to; ?></span>
							<input type="text" name="hb_lowstock_nostock_to" class="form-control" value="<?php echo $hb_lowstock_nostock_to; ?>">
						</div>
					</div>	
					<div class="col-sm-2">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_nostock_status" class="form-control" value="1" <?php echo ($hb_lowstock_nostock_status == 1)? 'checked':''; ?> />
					</div>		
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_lowstock_template; ?></label>
					<div class="col-sm-4">
						<select class="form-control" name="hb_lowstock_template">
							<option value="0"> No Templates </option>
							<?php if ($email_templates) { ?>
								<?php foreach ($email_templates as $template) { ?>
									<option value="<?php echo $template['id']; ?>" <?php echo ($hb_lowstock_template == $template['id'])?'selected':'' ?>><?php echo $template['template_label']; ?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>	
					<div class="col-sm-3">
						<div class="input-group">
							<span class="input-group-addon"><i class="fa fa-envelope"></i>&nbsp;<?php echo $text_lowstock_to; ?></span>
							<input type="text" name="hb_lowstock_to" class="form-control" value="<?php echo $hb_lowstock_to; ?>">
						</div>
					</div>	
					<div class="col-sm-2">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_status" class="form-control" value="1" <?php echo ($hb_lowstock_status == 1)? 'checked':''; ?> />
					</div>		
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_lowstock_qty; ?></label>
					<div class="col-sm-3">
						<input type="number" name="hb_lowstock_qty" class="form-control" value="<?php echo $hb_lowstock_qty; ?>" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_trigger_orderstatus; ?></label>
					<div class="col-sm-9">
					  <div class="well well-sm" style="height: 150px; overflow: auto;">
						<?php foreach ($order_statuses as $order_status) { ?>
						<div class="checkbox">
						  <label>
							<?php if (in_array($order_status['order_status_id'], $hb_lowstock_order_statuses)) { ?>
							<input type="checkbox" name="hb_lowstock_order_statuses[]" value="<?php echo $order_status['order_status_id']; ?>" checked="checked" />
							<?php echo $order_status['name']; ?>
							<?php } else { ?>
							<input type="checkbox" name="hb_lowstock_order_statuses[]" value="<?php echo $order_status['order_status_id']; ?>" />
							<?php echo $order_status['name']; ?>
							<?php } ?>
						  </label>
						</div>
						<?php } ?>
					  </div>
					</div>
				</div>
				
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_oopv_validation; ?></label>
					<div class="col-sm-9">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_opov_validation" class="form-control" value="1" <?php echo ($hb_lowstock_opov_validation == 1)? 'checked':''; ?> />
					</div>
				</div>
				
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_alternate_trigger; ?></label>
					<div class="col-sm-9">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_alt_trigger" class="form-control" value="1" <?php echo ($hb_lowstock_alt_trigger == 1)? 'checked':''; ?> />
					</div>
				</div>
				
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_lowstock_nostock_color; ?></label>
					<div class="col-sm-3">
						<input type="text" name="hb_lowstock_nostock_color" class="form-control color" value="<?php echo $hb_lowstock_nostock_color; ?>" />
					</div>				
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_lowstock_edit_mode; ?></label>
					<div class="col-sm-3">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_add_mode" class="form-control" value="1" <?php echo ($hb_lowstock_add_mode == 1)? 'checked':''; ?> />
					</div>				
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_auto_disable; ?></label>
					<div class="col-sm-1">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_auto_disable" class="form-control" value="1" <?php echo ($hb_lowstock_auto_disable == 1)? 'checked':''; ?> />
					</div>	
					<div class="col-sm-2">
						<select name="hb_oosn_stock_status"  class="form-control">
							<?php foreach ($stock_statuses as $stock_status) { ?>
								<option value="<?php echo $stock_status['stock_status_id']; ?>" <?php echo ($hb_lowstock_auto_disable_ss ==  $stock_status['stock_status_id'])? 'selected':''; ?> ><?php echo $stock_status['name']; ?></option>
							<?php }?>
						</select>
					</div>			
				</div>
					
				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_list_columns; ?></label>
					<div class="col-sm-3">
						<table class="table table-bordered table-hover">
							<?php foreach ($product_columns as $column) { ?>
								<tr>
									<td><?php echo $column['column_name']; ?></td>
									<td><input type="checkbox" data-size="small" data-style="android" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_col_<?php echo $column['column_id']; ?>" class="form-control" value="1" <?php echo ($hb_lowstock_col[$column['column_id']] == 1)? 'checked':''; ?> /></td>
								</tr>
							<?php } ?>
						</table>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_report_mode; ?></label>
					<div class="col-sm-3">
						<select name="hb_lowstock_dashboard_mode" class="form-control">
							<option value="low" <?php echo ($hb_lowstock_dashboard_mode == 'low')? 'selected':''; ?>><?php echo $text_low_stock_products; ?></option>
							<option value="all" <?php echo ($hb_lowstock_dashboard_mode == 'all')? 'selected':''; ?>><?php echo $text_all_products; ?></option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_enable_logs; ?></label>
					<div class="col-sm-9">
						<input type="checkbox" data-toggle="toggle" data-onstyle="success" name="hb_lowstock_worklog" class="form-control" value="1" <?php echo ($hb_lowstock_worklog == 1)? 'checked':''; ?> />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php echo $text_optional_apps; ?></label>
					<div class="col-sm-9">
						<?php if ($et_extn_linked) { ?>
							<div class="alert alert-success"><i class="fa fa-check"></i>&nbsp;<?php echo $text_et_connected; ?></div><br>
						<?php } else { ?>
							<a class="btn btn-default" href="https://www.huntbee.com/email-template-designer-pro-for-opencart&utm_medium=extension&utm_campaign=add-feature" target="_blank"><i class="fa fa-download"></i>&nbsp;<?php echo $text_install_et; ?></a>
						<?php } ?>
					</div>
				</div>
				
				</form>
		  </div><!--end tab setting div-->

		<div class="tab-pane" id="tab-template">
		<div class="col-sm-6">
			<div class="row">
				<div class="col-sm-8">
					<div class="input-group">
						<input type="text" id="search-value" onkeyup="searchTemplate();" class="form-control" placeholder="<?php echo $text_search_label; ?>">
						<span class="input-group-addon btn" id="search-template-button" onclick="searchTemplate();"><i class="fa fa-search"></i></span>
					</div>
				</div>
				<div class="col-sm-4">
						<a href="<?php echo $create_template; ?>" class="btn btn-success col-sm-12"><i class="fa fa-plus"></i>&nbsp;<?php echo $button_create_template; ?></a>
				</div>
			</div>
			
			<div class="row" style="margin-top:10px;">
				<div class="col-sm-12">
					<div id="templates-block">LIST</div>
				</div>
			</div>
		</div>
		
		<div class="col-sm-6">
			<div id="template-preview">
				<center><iframe id="iframe" width="100%" height="800px"></iframe></center>
			</div>
		</div>
		 
		</div><!--end tab template div-->
		  
          
          <!--LOGS TAB-->
          <div class="tab-pane" id="tab-logs">
			<div id="logs-block"></div>

			<div class="pull-right" style="margin-top:10px;">
				<a onclick="confirm('Are you sure?') ? location.href='<?php echo $clear; ?>' : false;" data-toggle="tooltip" title="Clear Logs" class="btn btn-danger"><i class="fa fa-eraser"></i>&nbsp;<?php echo $button_clear_logs; ?></a>
			</div>
		</div>
          <!--end tab logs-->
          
        </div>
        <!--END UL TAB MAIN CONTAINER-->
        
      </div>
    </div>
  </div>
  <div class="container-fluid">
    <!--Huntbee copyrights-->
    <center>
      <span class="help"><?php echo $heading_title; ?> - <?php echo $extension_version; ?> &copy; <a href="https://www.huntbee.com/">WWW.HUNTBEE.COM</a> | <a href="https://www.huntbee.com/get-support" target="_blank">SUPPORT</a></span>
    </center>
  </div>
  <!--Huntbee copyrights end-->
</div>

<script type="text/javascript" src="view/javascript/jscolor/jscolor.js"></script>

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

<script type="text/javascript" src="view/javascript/bootstrap-notify.min.js"></script>

<style type="text/css">
<?php foreach ($show as $key => $value) { ?>
	<?php if ($value == false ) { ?>
	.hbp_<?php echo $key; ?>{display: none !important;}
	<?php } ?>
<?php } ?>

.loaddiv{margin:100px;color:#0099CC;}
body{font-family: 'PT Sans', sans-serif; font-size: 13px;}
li a{cursor: pointer;}

.table-category td{
	padding: 0px 3px;
}

.hbp_quantity_threshold, .hbp_option_quantity_threshold{
	/*border: #ff9800 1px solid !important;*/
	background-color: #ff980020;
}

.updated_green{
	color: green;
}

.threshold_group{
	margin-top: 0px;
}

/*.quantity_group> .input-group-addon{
	background-color: #4caf5022;
	border-color: #2f853322;
}

.quantity_group > .form-control{
	border-color: #2f853322;
}


.threshold_group > .input-group-addon{
	background-color: #ffc1071f;
	border-color: #c493001f;
}

.threshold_group > .form-control{
	border-color: #c493001f;
}*/

</style>

<script type="text/javascript">
$(document).ready(function() {
	loadBlock('products');
});
</script>

<script type="text/javascript">
function loadBlock(name){
	$('#'+name+'-block').html('<center><i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i></center>');
	$('#'+name+'-block').load('index.php?route=<?php echo $base_route; ?>/hb_lowstock/'+name+'&token=<?php echo $token; ?>');
}

$('#products-block').delegate('.pagination a', 'click', function(e) {
	e.preventDefault();
	$('#products-block').load(this.href);
});

$('#templates-block').delegate('.pagination a', 'click', function(e) {
	e.preventDefault();
	$('#templates-block').load(this.href);
});

function searchTemplate() {
	$('#templates-block').html('<center><div class="loaddiv"><i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i></div></center>');
	var search_value = $('#search-value').val();
	$('#templates-block').load('index.php?route=<?php echo $base_route; ?>/hb_lowstock/templates&token=<?php echo $token; ?>&search='+encodeURIComponent(search_value));
};

function delete_template(id){
	$('#output-console').html('<center><i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i></center>');
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/delete_template&token=<?php echo $token; ?>',
		data: {id : id},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				  $('#output-console').html('<div class="alert alert-success"><i class="fa fa-check"></i> '+json['success']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				  loadBlock('templates');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function preview(id) {
	document.getElementById('iframe').src='<?php echo $preview_link ?>&template_id='+id;
}

function searchProduct() {
	var searchvalue = $('#search-product-value').val();
	var search_category_id 		= $('#search_category_id').val();
	var search_manufacturer_id 	= $('#search_manufacturer_id').val();
	var search_status 			= $('#search_status').val();

	if (search_category_id == '' || search_category_id == 0){
		search_category_id = '';
	}

	if (search_manufacturer_id == '' || search_manufacturer_id == 0){
		search_manufacturer_id = '';
	}

	$('#products-block').load('index.php?route=<?php echo $base_route; ?>/hb_lowstock/products&token=<?php echo $token; ?>&search='+encodeURIComponent(searchvalue)+'&search_category_id='+search_category_id+'&search_manufacturer_id='+search_manufacturer_id+'&search_status='+search_status);
}

function export2csv() {
	var searchvalue 			= $('#search-product-value').val();
	var search_category_id 		= $('#search_category_id').val();
	var search_manufacturer_id 	= $('#search_manufacturer_id').val();
	var search_status 			= $('#search_status').val();

	if (search_category_id == '' || search_category_id == 0){
		search_category_id = '';
	}

	if (search_manufacturer_id == '' || search_manufacturer_id == 0){
		search_manufacturer_id = '';
	}

	var url = 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/export2csv&token=<?php echo $token; ?>&search='+encodeURIComponent(searchvalue)+'&search_category_id='+search_category_id+'&search_manufacturer_id='+search_manufacturer_id+'&search_status='+search_status;			
    location.href = url;
}

function saveProductQuantity(product_id){
	var qty = $('#product_quantity'+product_id).val();
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_quantity&token=<?php echo $token; ?>',
		data: {product_id : product_id, value: qty},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
				$('#qty'+product_id).html('<span class="updated_green">'+json['qty']+'</span>');
			}
			if (json['warning']) {
				  $('#output-console').html('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+json['warning']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function saveProductThreshold(product_id){
	var threshold = $('#product_threshold'+product_id).val();
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_threshold&token=<?php echo $token; ?>',
		data: {product_id : product_id, value: threshold},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
			}
			if (json['warning']) {
				  $('#output-console').html('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+json['warning']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function saveProductOptionQuantity(product_id, product_option_value_id){
	var qty = $('#product_option_qty'+product_option_value_id).val();
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_option_quantity&token=<?php echo $token; ?>',
		data: {product_id : product_id, product_option_value_id: product_option_value_id, value: qty},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
				$('#qty'+product_id).html('<span class="updated_green">'+json['qty']+'</span>');

				if (!json['hb_lowstock_add_mode']) {
					$('#product_quantity'+product_id).val(json['qty']);
				}

				$('#option_qty'+product_option_value_id).html('<span class="updated_green">'+json['option_qty']+'</span>');
			}
			if (json['warning']) {
				  $('#output-console').html('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+json['warning']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function saveProductOptionThreshold(product_id, product_option_value_id){
	var threshold = $('#product_option_threshold'+product_option_value_id).val();
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_option_threshold&token=<?php echo $token; ?>',
		data: {product_id : product_id, product_option_value_id: product_option_value_id, value: threshold},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});

				$('#option_qty'+product_option_value_id).html('<span class="updated_green">'+json['option_qty']+'</span>');
			}
			if (json['warning']) {
				  $('#output-console').html('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+json['warning']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function saveProductStatus(product_id){
	var status = $('#product_status'+product_id).val();
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_status&token=<?php echo $token; ?>',
		data: {product_id : product_id, value: status},
		dataType: 'json',
		success: function(json) {
			if (json['success']) {
				$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
			}
			if (json['warning']) {
				  $('#output-console').html('<div class="alert alert-danger"><i class="fa fa-exclamation"></i> '+json['warning']+'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
			}
		},			
		error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

//lookup
$('#category_lookup').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=catalog/category/autocomplete&token=<?php echo $token; ?>&filter_name=' + encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				json.unshift({
					category_id: 0,
					name: '--NONE--'
				});
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['category_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#search_category_id').val(item['value']);
      	$('#category_lookup').val(item['label']);
		searchProduct();
	}
});

$('#manufacturer_lookup').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=catalog/manufacturer/autocomplete&token=<?php echo $token; ?>&filter_name=' +  encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				json.unshift({
					manufacturer_id: 0,
					name: '--NONE--'
				});

				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['manufacturer_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('#search_manufacturer_id').val(item['value']);
      	$('#manufacturer_lookup').val(item['label']);
		searchProduct();
	}
});

</script>

<?php echo $footer; ?>
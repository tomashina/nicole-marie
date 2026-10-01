<?php echo $header; ?><?php echo $column_left; ?>

<div id="content">
<!--Header Start-->
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <a onclick="save();" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></a>
		<a href="https://www.huntbee.com/documentation/docs/low-stock-management/short-codes/" target="_blank" class="btn btn-default"><i class="fa fa-code"></i>&nbsp;<?php echo $button_short_codes; ?></a>
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
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $heading_title_edit; ?> : <?php echo $label; ?></h3>
      </div>
      <div class="panel-body">
	  	  <div id="output-console"></div>		
		     <ul class="nav nav-tabs" id="types">
                <li class="active"><a href="#tab-template" data-toggle="tab"><i class="fa fa-newspaper-o" aria-hidden="true"></i>&nbsp;<?php echo $tab_email_template; ?></a></li>
				<li><a href="#tab-options" data-toggle="tab"><i class="fa fa-paperclip" aria-hidden="true"></i>&nbsp;<?php echo $tab_email_option; ?></a></li>
				<li><a href="#tab-preview" data-toggle="tab"><i class="fa fa-binoculars"></i>&nbsp;<?php echo $tab_email_preview; ?></a></li>
	          </ul>
			  <div class="tab-content">
					<div class="tab-pane active" id="tab-template">
					<form id="form-basic" class="form-horizontal">
						<div class="form-group">
							<label class="control-label col-sm-4"><?php echo $text_label; ?></label>
							<div class="col-sm-8">
								<input type="text" name="label" id="label" value="<?php echo $label; ?>" onchange="updateLabel();" class="form-control">
							</div>
						</div>
						<hr />
					</form>
	
					 <form id="form-template" class="form-horizontal">
					  
					  <div class="form-group">
						<div class="col-sm-4">
							<label class="col-sm-12 control-label"><?php echo $text_email_content; ?></label>
							<div class="col-sm-12" style="margin-top:20px;">
								<div class="table-responsive">
									<table class="table table-hover table-bordered">
										<tr class="no_master">
											<td class="text-right"><?php echo $text_layouts; ?></td>
											<td class="text-left">
												<div class="input-group">
													<label class="input-group-addon"><i class="fa fa-file-text" aria-hidden="true"></i></label>
													<select id="simple_layouts" class="form-control">
														<?php if ($simple_layouts) { ?>
															<?php foreach ($simple_layouts as $template) { ?>
																<option value="<?php echo $template['value']; ?>"><?php echo $template['label']; ?></option>
															<?php } ?>
														<?php } ?>
													</select>	
												</div>
											</td>
										</tr>
										<tr class="no_master">
											<td class="text-right"><?php echo $text_width; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-arrows-h"></i></label>
												<input type="text" id="width" class="form-control" value="600" />
											</div>
											</td>
										</tr>
										<tr class="no_master">
											<td class="text-right"><?php echo $text_color1; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-paint-brush"></i></label>
												<input type="text" id="color-1" class="color form-control" value="F7F9FB" />
											</div>
											</td>
										</tr>
										<tr class="no_master">
											<td class="text-right"><?php echo $text_color2; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-paint-brush"></i></label>
												<input type="text" id="color-2" class="color form-control" value="00887A" />
											</div>
											</td>
										</tr>
										<tr class="no_master">
											<td class="text-right"><?php echo $text_color3; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-paint-brush"></i></label>
												<input type="text" id="color-3" class="color form-control" value="61892F" />
											</div>
											</td>
										</tr>
										<tr class="no_master">
											<td class="text-right"><?php echo $text_color4; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-paint-brush"></i></label>
												<input type="text" id="color-4" class="color form-control" value="FFFFFF" />
											</div>
											</td>
										</tr>
										<tr>
											<td class="text-right"><?php echo $text_content_sample; ?></td>
											<td class="text-left">
											<div class="input-group">
												<label class="input-group-addon"><i class="fa fa-edit"></i></label>
												<select id="email-type" class="form-control">
													<?php if ($simple_layouts) { ?>
														<?php foreach ($simple_contents as $template) { ?>
															<option value="<?php echo $template['value']; ?>"><?php echo $template['label']; ?></option>
														<?php } ?>
													<?php } ?>
												</select>
											</div>
											</td>
										</tr>
										
										<tr>
											<td class="text-right"></td>
											<td class="text-left"><a class="btn btn-primary" id="load-layout-btn" onclick="loadSimpleLayout();"><i class="fa fa-play-circle"></i>&nbsp;<?php echo $button_generate; ?></a></td>
										</tr>
									</table>
								</div>	
							</div>
						
						</div>
						<div class="col-sm-8">
						  <textarea name="htmlbody" id="htmlbody" data-toggle="ckeditor" ><?php echo $email_body; ?></textarea>
						</div>
					  </div>
					  
					  </form>
					</div><!--end tab template div-->
					
					<div class="tab-pane" id="tab-options">
						<form class="form-horizontal" id="form-email-options">
							<div class="form-group">
								<label class="control-label col-sm-3"><?php echo $text_sender_name; ?></label>
								<div class="col-sm-9">
									<input type="text" name="sender_name" value="<?php echo $sender_name; ?>" class="form-control">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-3"><?php echo $text_sender_email; ?></label>
								<div class="col-sm-9">
									<input type="text" name="sender_email" value="<?php echo $sender_email; ?>" class="form-control">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-3"><?php echo $text_bcc; ?></label>
								<div class="col-sm-9">
									<input type="text" name="email_bcc" value="<?php echo $email_bcc; ?>" class="form-control">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-3"><?php echo $text_reply_to; ?></label>
								<div class="col-sm-9">
									<input type="text" name="email_replyto" value="<?php echo $email_replyto; ?>" class="form-control">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-sm-3"><?php echo $text_subject; ?></label>
								<div class="col-sm-9">
									<input type="text" name="email_subject" value="<?php echo $email_subject; ?>" class="form-control">
									<input type="hidden" name="email_type_id" value="2" class="form-control">
								</div>
							</div>
						</form>
						
					</div><!--end tab options div-->
					
					<!--CONTENT PREVIEW-->
					<div class="tab-pane" id="tab-preview">
						<div class="panel-heading">
								<h3><i class="fa fa-eye"></i>&nbsp;<?php echo $text_email_preview; ?></h3>
								<div class="pull-right">
								<a onclick="$('#iframe')[0].contentWindow.location.reload(true);;" class="btn btn-default" title="<?php echo $button_refresh; ?>"> <i class="fa fa-refresh"></i>  </a> | 
								<a onclick="$('#iframe').animate({width: '100%'});" class="btn btn-default" title="<?php echo $button_desktop; ?>"> <i class="fa fa-desktop"></i>  </a> 
								<a onclick="$('#iframe').animate({width: '768px'});" class="btn btn-default" title="<?php echo $button_tablet; ?>"> <i class="fa fa-tablet"></i>  </a> 
								<a onclick="$('#iframe').animate({width: '414px'});" class="btn btn-default" title="<?php echo $button_mobile; ?>"> <i class="fa fa-mobile"></i>  </a>
								</div>
						  </div>
						  <center><iframe id="iframe" width="100%" height="800px" src="<?php echo $preview_link; ?>"></iframe></center>
						
					</div>
			  </div>
          
      </div>
    </div>
  </div>
  <div class="container-fluid"> <!--Huntbee copyrights-->
 <center>
    <span class="help"><?php echo $heading_title; ?> &copy; <a href="https://www.huntbee.com/">HUNTBEE.COM</a></span></center>
</div><!--Huntbee copyrights end-->
</div>



<style type="text/css">
	
body{font-family: 'PT Sans', sans-serif; font-size: 13px;}
.pr_error,.pr_info,.pr_infos,.pr_success,.pr_warning{margin:10px 0;padding:12px}.pr_info{color:#00529B;background-color:#BDE5F8}.pr_success{color:#4F8A10;background-color:#DFF2BF}.pr_warning{color:#9F6000;background-color:#FEEFB3}.pr_error{color:#D8000C;background-color:#FFBABA}.pr_error i,.pr_info i,.pr_success i,.pr_warning i{margin:10px 0;vertical-align:middle}
</style>

<script type="text/javascript">
 var access_key = '<?php echo $token; ?>';
 var token = '<?php echo $token; ?>';
 var base_route = '<?php echo $base_route; ?>';
 var store_id = '<?php echo $store_id; ?>';
</script>

<script type="text/javascript" src="view/javascript/jscolor/jscolor.js"></script>
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

<script type="text/javascript" src="view/javascript/bootstrap-notify.min.js"></script>

<script type="text/javascript" src="view/javascript/hb_ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="view/javascript/hb_ckeditor.js"></script>  


<script type="text/javascript">

function save(){
	savecontents();
	saveemailoptions();
}

function updateLabel(){
	$.post('index.php?route=<?php echo $base_route; ?>/hb_lowstock/update_label&token=<?php echo $token; ?>&id=<?php echo $template_id; ?>', {label: $('#label').val()},function(json) {
	  	if (json['success']) {
			$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
		}
	},'json');
}

function savecontents(){
	$.ajax({
		  type: 'post',
		  url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/savecontents&token=<?php echo $token; ?>&template_id=<?php echo $template_id; ?>',
		  data: {draft_body: CKEDITOR.instances['htmlbody'].getData()},
		  dataType: 'json',
		  success: function(json) {
				if (json['success']) {
					$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
				}
				if (json['warning']) {
					$.notify({icon: 'fa fa-exclamation', message: json['warning']},{type: 'warning'});
				}
		  },			
		  error: function(xhr, ajaxOptions, thrownError) { alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText); }
	 });
}

function saveemailoptions(){
	$.post('index.php?route=<?php echo $base_route; ?>/hb_lowstock/saveemailoptions&token=<?php echo $token; ?>&template_id=<?php echo $template_id; ?>', $('#form-email-options').serialize(),function(json) {
	  	if (json['success']) {
			$.notify({icon: 'fa fa-check', message: json['success']},{type: 'success'});
		}
		if (json['warning']) {
			$.notify({icon: 'fa fa-exclamation', message: json['warning']},{type: 'warning'});
		}
	},'json');
}

function loadSimpleLayout(){
	$('#load-layout-btn').html('<center><i class="fa fa-cog fa-spin fa-fw"></i></center>');
	$.ajax({
		type: 'post',
		url: 'index.php?route=<?php echo $base_route; ?>/hb_lowstock/loadSimpleLayout&token=<?php echo $token; ?>',
		data: {
			store_id: '<?php echo $store_id; ?>', 
			selected_template: $('#simple_layouts').val(), 
			color1: $('#color-1').val(), 
			color2: $('#color-2').val(), 
			color3: $('#color-3').val(),
			color4: $('#color-4').val(),
			width: $('#width').val(),
			email_type: $('#email-type').val()
		},
		dataType: 'json',
		success: function(json) {
			if (json['layout']) {
				  CKEDITOR.instances.htmlbody.setData(json['layout']);
			}
			$('#load-layout-btn').html('<i class="fa fa-play-circle"></i> Generate');
		},
		error: function(xhr, ajaxOptions, thrownError) { $('#load-layout-btn').html('<i class="fa fa-exclamation"></i> Failed'); }		
	 });
}

</script>

<?php echo $footer; ?>
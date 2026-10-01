 <div class="table-responsive">
	<table class="table table-bordered table-hover">
	<thead>
		<tr>
		<td class="text-center"><?php echo $text_id; ?></td>
		<td class="text-left"><?php echo $text_label; ?></td>
		<td class="text-right"><?php echo $text_date_added; ?></td>
		<td class="text-center"><?php echo $text_action; ?></td>
	  </tr>
	</thead>
	<tbody>
		<?php if ($records) { ?>
		<?php foreach ($records as $record) { ?>
		<tr>
		<td class="text-center"><?php echo $record['id']; ?></td>
		<td class="text-left"><?php echo $record['label']; ?></td>
		<td class="text-right"><?php echo $record['date_added']; ?></td>
		<td class="text-center"> 
			<a href="<?php echo $record['edit']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i>&nbsp;<?php echo $button_edit; ?></a>
			<a onclick="preview('<?php echo $record['id']; ?>');" class="btn btn-sm btn-default"><i class="fa fa-eye"></i>&nbsp;<?php echo $button_preview; ?></a>
			<a onclick="confirm('Are you sure?') ? delete_template('<?php echo $record['id']; ?>') : false;"class="btn btn-sm btn-danger"><i class="fa fa-trash"></i>&nbsp;<?php echo $button_delete; ?></a></td>
		</tr>
		<?php } ?>
		<?php } else { ?>
		<tr><td class="text-center" colspan="4"><?php echo $text_no_records; ?></td></tr>
		<?php } ?>				
	</tbody>
	</table>
</div>

<div class="row">
  <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
  <div class="col-sm-6 text-right"><?php echo $results; ?></div>
</div>


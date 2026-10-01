<div class="table-responsive">
	<table class="table table-bordered table-hover">
		<thead>
			<tr>
			<td class="text-center"><?php echo $text_product_id; ?></td>
			<td class="text-center hbp_image"><?php echo $text_image; ?></td>
			<td class="text-left"><?php echo $text_name; ?></td>
			
			<?php foreach ($column_set_1 as $col) {?>
				<td class="text-<?php echo $col[2]; ?> hbp_<?php echo $col[1]; ?>"><?php echo $col[0]; ?></td>
			<?php } ?>

			<td class="text-left hbp_category"><?php echo $text_category; ?></td>
			<td class="text-right hbp_price"><?php echo $text_price; ?></td>
			<td class="text-center hbp_quantity <?php if ($hb_lowstock_add_mode) { ?>col-sm-2<?php } else { ?>col-sm-1<?php } ?>"><?php echo $text_quantity; ?></td>
			<td class="text-center hbp_quantity_threshold col-sm-1"><?php echo $text_threshold; ?></td>
			<td class="text-center hbp_option_quantity col-sm-2"><?php echo $text_option_quantity; ?></td>
			<td class="text-center hbp_option_quantity_threshold col-sm-2"><?php echo $text_threshold; ?></td>
			<td class="text-center hbp_status"><?php echo $text_status; ?></td>
			<td class="text-center"><?php echo $text_action; ?></td>
			</tr>
		</thead>
		<tbody>
			<?php if ($products) { ?>
			<?php foreach ($products as $product) { ?>

				<?php if ($product['quantity'] < 1 ) {
					$row_color_class = 'outofstock';
				}else{
					$row_color_class = 'lowstock';
				} ?>
			<tr class="<?php echo $row_color_class; ?>">
			<td class="text-center"><?php echo $product['product_id']; ?></td>
			<td class="text-center hbp_image"><?php if ($product['image']) { ?>
				<img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-thumbnail" />
				<?php } else { ?>
				<span class="img-thumbnail list"><i class="fa fa-camera fa-2x"></i></span>
				<?php } ?></td>
			<td class="text-left"><a href="<?php echo $product['edit']; ?>" target="_blank"><?php echo $product['name']; ?></a></td>

			<?php foreach ($column_set_1 as $col) {?>
				<td class="text-<?php echo $col[2]; ?> hbp_<?php echo $col[1]; ?>"><?php echo $product[$col[1]]; ?></td>
			<?php } ?>

			<td class="text-left hbp_category">
				<table class="table-category">
					<?php foreach ($product['product_categories'] as $product_category) { ?>
					<tr>
						<td><i class="fa fa-angle-double-right"></i></td>
						<td><?php echo $product_category['name']; ?></td>
					</tr>
					<?php } ?>
				</table>
			</td>

			<td class="text-right hbp_price"><?php if ($product['special']) { ?>
				<span style="text-decoration: line-through;"><?php echo $product['price']; ?></span><br/>
				<div class="text-danger"><?php echo $product['special']; ?></div>
				<?php } else { ?>
				<?php echo $product['price']; ?>
				<?php } ?></td>

			<td class="text-left hbp_quantity">
				<?php if ($hb_lowstock_add_mode) { ?>
					<div class="input-group">
						<span class="input-group-addon" id="qty<?php echo $product['product_id']; ?>" data-toggle="tooltip" data-original-title="<?php echo $text_quantity; ?>"><?php echo $product['quantity']; ?></span>
						<span class="input-group-addon"><i class="fa fa-plus"></i></span>
						<input type="text" id="product_quantity<?php echo $product['product_id']; ?>" value="" class="form-control">
						<span class="input-group-addon btn" onclick="saveProductQuantity('<?php echo $product['product_id']; ?>');"><i class="fa fa-save"></i></span>
					</div>
				<?php } else { ?>
					<div class="input-group quantity_group">
						<input type="text" id="product_quantity<?php echo $product['product_id']; ?>" value="<?php echo $product['quantity']; ?>" class="form-control">
						<span class="input-group-addon btn" onclick="saveProductQuantity('<?php echo $product['product_id']; ?>');"><i class="fa fa-save"></i></span>
					</div>
				<?php } ?>

				<?php if (!$product['validation_qty']) { ?>
					<div id="validation_qty<?php echo $product['product_id']; ?>">
						<div class="alert alert-warning" style="margin-top:3px;"><i class="fa fa-exclamation-triangle">&nbsp;</i><?php echo $text_qty_validation_error; ?></div>
					</div>
				<?php } ?>
			</td>

			<td class="text-left hbp_quantity_threshold">
				<div class="input-group threshold_group">
					<input type="text" id="product_threshold<?php echo $product['product_id']; ?>" value="<?php echo $product['hb_p_threshold']; ?>" class="form-control">
					<span class="input-group-addon btn" onclick="saveProductThreshold('<?php echo $product['product_id']; ?>');"><i class="fa fa-check"></i></span>
				</div>
			</td>

			<td class="text-left hbp_option_quantity">
				<table class="table table-bordered table-hover">
				<?php foreach ($product['option_quantity'] as $option_quantity) { ?>
					<tbody>
						<tr>
							<td class="col-sm-3"><?php echo $option_quantity['option_name']; ?> - <?php echo $option_quantity['option_value_name']; ?></td>
							<td class="col-sm-3">
								<?php if ($hb_lowstock_add_mode) { ?>
								<div class="input-group">
									<span class="input-group-addon" id="option_qty<?php echo $option_quantity['product_option_value_id']; ?>"><?php echo $option_quantity['quantity']; ?></span>
									<span class="input-group-addon"><i class="fa fa-plus"></i></span>
									<input type="text" id="product_option_qty<?php echo $option_quantity['product_option_value_id']; ?>" value="" class="form-control">
									<span class="input-group-addon btn" onclick="saveProductOptionQuantity('<?php echo $product['product_id']; ?>','<?php echo $option_quantity['product_option_value_id']; ?>');"><i class="fa fa-save"></i></span>
								</div>
								<?php } else { ?>
									<div class="input-group">
										<input type="text" id="product_option_qty<?php echo $option_quantity['product_option_value_id']; ?>" value="<?php echo $option_quantity['quantity']; ?>" class="form-control">
										<span class="input-group-addon btn" onclick="saveProductOptionQuantity('<?php echo $product['product_id']; ?>','<?php echo $option_quantity['product_option_value_id']; ?>');"><i class="fa fa-save"></i></span>
									</div>
								<?php } ?>
							</td>
						</tr>
					</tbody>	
				<?php } ?>
				</table>
			</td>

			<td class="text-left hbp_option_quantity_threshold">
				<table class="table table-bordered table-hover">
				<?php foreach ($product['option_quantity'] as $option_quantity) { ?>
					<tbody>
						<tr>
							<td class="col-sm-6"><?php echo $option_quantity['option_name']; ?> - <?php echo $option_quantity['option_value_name']; ?></td>
							<td>
								<div class="input-group">
									<input type="text" id="product_option_threshold<?php echo $option_quantity['product_option_value_id']; ?>" value="<?php echo $option_quantity['hb_pov_threshold']; ?>" class="form-control">
									<span class="input-group-addon btn" onclick="saveProductOptionThreshold('<?php echo $product['product_id']; ?>','<?php echo $option_quantity['product_option_value_id']; ?>');"><i class="fa fa-check"></i></span>
								</div>
							</td>
						</tr>
					</tbody>	
				<?php } ?>
				</table>
			</td>

			<td class="text-center hbp_status">
				<select id="product_status<?php echo $product['product_id']; ?>" onchange="saveProductStatus('<?php echo $product['product_id']; ?>');" class="form-control">
					<option value="1" <?php echo ($product['status'] == 1) ? 'selected':''; ?> ><?php echo $text_enable; ?></option>
					<option value="0" <?php echo ($product['status'] == 0) ? 'selected':''; ?> ><?php echo $text_disable; ?></option>
				</select>
			</td>

			<td class="text-center"> 
				<a href="<?php echo $product['edit']; ?>" target="_blank" class="btn btn-sm btn-primary" style="margin:2px;"><i class="fa fa-pencil"></i></a>
				<a href="<?php echo $product['view']; ?>" target="_blank" class="btn btn-sm btn-default" style="margin:2px;"><i class="fa fa-eye"></i></a>
			</td>
			</tr>
			<?php } ?>
			<?php } else { ?>
			<tr><td class="text-center" colspan="15"><?php echo $text_no_records; ?></td></tr>
			<?php } ?>				
		</tbody>
	</table>
</div>

<div class="row">
	<div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
	<div class="col-sm-6 text-right"><?php echo $results; ?></div>
</div>

<style type="text/css">
.outofstock{
	color: #<?php echo $hb_lowstock_nostock_color; ?>;
	background-color: #<?php echo $hb_lowstock_nostock_color; ?>1A;
}
.outofstock > .hbp_quantity{
	background-color: #<?php echo $hb_lowstock_nostock_color; ?>1A;
}

</style>
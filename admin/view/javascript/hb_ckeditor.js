$(document).ready(function() {
	// CKEditor
	$('[data-toggle=\'ckeditor\']').each(function() {
		CKEDITOR.replace($(this).attr('id'), {
            filebrowserBrowseUrl : 'view/javascript/hb_filemanager/dialog.php?type=2&editor=ckeditor&akey='+access_key,
        	filebrowserUploadUrl : 'view/javascript/hb_filemanager/dialog.php?type=2&editor=ckeditor&akey='+access_key,
        	filebrowserImageBrowseUrl : 'view/javascript/hb_filemanager/dialog.php?type=2&editor=ckeditor&akey='+access_key,
            height: '1200px',
		});
	})
	// Image File Manager
	$(document).on('click', 'a[data-toggle=\'filemanager\']', function(e) {
		var $element = $(this);
		var $popover = $element.data('bs.popover'); // element has bs popover?

		e.preventDefault();

		// destroy all image popovers
		$('a[data-toggle="filemanager"]').popover('destroy');

		// remove flickering (do not re-add popover when clicking for removal)
		if ($popover) {
			return;
		}

		$element.popover({
			html: true,
			placement: 'right',
			trigger: 'manual',
			content: function() {
				return '<a data-fancybox data-type="iframe" data-src="view/javascript/hb_filemanager/dialog.php?type=2&relative_url=1&field_id='+$element.parent().find('input').attr('id')+'&akey='+access_key+'" href="javascript:;" class="btn btn-primary"><i class="fa fa-pencil"></i></a> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
			}
		});

		$element.popover('show');

		$('#button-clear').on('click', function() {
			$element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

			$element.parent().find('input').val('');

			$element.popover('destroy');
		});
	});
})

function responsive_filemanager_callback(field_id){
    var url=$('#'+field_id).val();
    console.log(url);
   
} 
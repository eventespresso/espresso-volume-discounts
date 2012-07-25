(function($) {
	
	$('#vlm-dscnt-factor-slct').change( function(){
		if ( $(this).val() == 'fctr_meta_field' ){
			$('.meta-field-option').parents('tr').show();
		} else {
			$('.meta-field-option').parents('tr').hide();
		}
	});
	
	$('#vlm-dscnt-factor-slct').trigger('change');
	
})(jQuery);

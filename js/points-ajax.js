jQuery(document).ready(function($){
		$('.people').blur(function() {
		var data = {
					'action': 'points_action'
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post(ajaxurl, data, function(response) {
				var searchCount = $('.people').filter(function(){
					return $(this).val();
				}).length;
				$('#ppl_amount').val(searchCount);
				});
		});

});
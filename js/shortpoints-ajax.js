jQuery(document).ready(function($){
		$('button').click(function(event) {
		event.preventDefault();
			var btnId = event.currentTarget.id;
			var btnName = $(event.currentTarget).attr("name");
			var btn_class = event.currentTarget.className;
			var btnResult = btnName + 'result';
		jQuery.ajax({
				url : postshortpoints.ajax_url,
				type : 'POST',
				data : {
					action : 'shortpoints-ajax',
					name : btnId,
					btnName : btnName,
					btnClass : btn_class
				},
				success : function( response ) {
					$("#" + btnResult).html("");
					$("#" + btnResult).html(response);
				}
			});
		});
		
		$('#ajaxsub').on("click", "#ajaxsub", function(){
          location.reload(true);
		});
		
/* 		$("button").click(function(event) {
			var btnId = event.currentTarget.id;
			var btnResult = btnId + 'result';
			jQuery.ajax({
				url : postshortpoints.ajax_url,
				type : 'post',
				btnId : btnId,
				data : {
					action : 'shortpoints-ajax',
				},
				success : function( response ) {
					$(btnResult).after(response);
				}
			});
		}); */

});
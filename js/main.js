jQuery(function($) {
	var update_post_content = function() {
		var options = {
			action: 'save_post_content',
			data: {
				post_id: $('#post_ID').val(),
				content: $('#content').getCode()
			}
		};

		jQuery.post(ajaxurl, options, function(response) {
			if (response != 'success') {
				alert('There was a problem saving your data.');
			}
		});

		inserting_media = false;
	}

	$('#open-wp-media-lib').live('click', function() {
			inserting_media = true;
			openMediaEmbed();
		}
	);

	$('#content').redactor({ 
    	keyupCallback: update_post_content,
    	callback: function(obj) {

    	}
	});
});
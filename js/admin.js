(function($) {
	"use strict";
	
	$(function() {
	
		if( 0 < $('#post_media').length ) {
		  $('form').attr('enctype', 'multipart/form-data');
		} // end if
		
	});
}(jQuery));

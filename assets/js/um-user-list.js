/**
 *  - v1.0.0 - 2019-08-18
 * 
 *
 * Copyright (c) 2019;
 * Licensed GPLv2+
 */
window.UMUserList = window.UMUserList || {};

( function( window, document, $, plugin ) {
	var $c = {};

	plugin.init = function() {
		plugin.cache();
		plugin.bindEvents();
	};

	plugin.cache = function() {
		$c.window   = $( window );
		$c.body     = $( document.body );
		$c.link     = $( '.umul-member-list-refresh' );
		$c.document = $( document );
	};

	plugin.bindEvents = function() {
		$c.document.on( 'click', '.umul-member-list-refresh', plugin.umul_refresh_connect );
	};

	plugin.umul_refresh_connect = function( e ) {
		e.preventDefault();
		var obj    = jQuery(this);
		var count  = $(this).data( 'count' );
		var skip   = $(this).data( 'skip' );
		var order  = $(this).data( 'order' );
		var xhttp  = new XMLHttpRequest();
		xhttp.responseType = 'json';
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
			var jsonResponse = xhttp.response;
			var res_txt      = jsonResponse.txt;
			var res_skip     = parseInt(jsonResponse.skip);
			if ( res_skip > 0 ) {
				obj.data( 'skip', jsonResponse.skip);
				obj.closest('.umul-member-list-refresh-wrap').show();
			} else {
				obj.data( 'skip', '0');
				obj.closest('.umul-member-list-refresh-wrap').hide();
			}
				obj.closest('.umul-member-list-container').find('.umul-member-outer').html(res_txt);
			}
		};
		xhttp.open("POST", umulfAjax.ajax_url, true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("orderby=" + order + "&count="+count+"&skip="+skip+"&action=umul_refresh_user_connection");
	};

	$( plugin.init );
}( window, document, jQuery, window.UMUserList ) );
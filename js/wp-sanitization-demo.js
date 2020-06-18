jQuery( document ).ready( function( $ ) {
	$( '#wp-sanitization-demo-go' ).click( function ( e ) { 
		e.preventDefault();

		var functions = [
			'sanitize_email',
			'sanitize_file_name',
			'sanitize_html_class',
			'sanitize_key',
			'sanitize_mime_type',
			'sanitize_sql_orderby',
			'sanitize_text_field',
			'sanitize_title',
			'sanitize_title_for_query',
			'sanitize_title_with_dashes',
			'sanitize_user',
			'intval',
			'absint',
			'wp_filter_post_kses',
			'wp_filter_nohtml_kses',
			'ent2ncr',
			'wp_rel_nofollow',
			'esc_html',
			'esc_textarea',
			'sanitize_text_field',
			'esc_attr',
			'esc_attr__',
			'esc_js',
			'esc_url',
			'esc_url_raw',
			'urlencode',
			'balanceTags',
			'force_balance_tags',
			'tag_escape',
			'is_email'
		];

		functions.forEach( functionName => {
			$.post(
				wpsd_global.ajax_url,
				{
					_ajax_nonce     : wpsd_global.generic_nonce,
					action          : 'wp_sanitization_demo_update',
					functionName    : functionName,
					stringToSanitize: $( '#wp-sanitization-demo-input' ).val()
				},
				function( response ) {
					if ( true === response.success ) {
						$( 'tr.' + functionName + ' .result' ).html( response.data ); // Update the result cell with the results.
					}
				}
			);
		});
	});
});

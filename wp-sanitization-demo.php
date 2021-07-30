<?php
/**
 * Plugin Name:     WP Sanitization Demo
 * Description:     See the results of the various built-in sanitization and escaping functions on a user-provided string. Use [wp-sanitization-demo] to add the form to a page.
 * Author:          Yoko Co
 * Author URI:      https://www.yokoco.com/
 * Text Domain:     yoko-wp-sanitization-demo
 * Version:         0.1.1
 * License:         GPL3
 *
 * WP Sanitization Demo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * WP Sanitization Demo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Sanitization Demo. If not, see
 * https://www.gnu.org/licenses/gpl-3.0.html.
 *
 * @package         Yoko_WP_Sanitization_Demo
 */

// Define global constants based on the plugin name, version, and text domain.
$plugin_data = get_file_data(
	__FILE__,
	array(
		'name'    => 'Plugin Name',
		'version' => 'Version',
		'text'    => 'Text Domain',
	)
);

/**
 * This helper function provides consistently-named constants.
 *
 * @param string $constant_name The unprefixed version of the desired constant name.
 * @param mixed  $value The value to assign to the prefixed constant.
 * @return void
 */
function yoko_wp_sanitization_demo_constants( $constant_name, $value ) {
	$constant_name_prefix = 'YOKO_WP_SANITIZATION_DEMO_';
	$constant_name        = $constant_name_prefix . $constant_name;
	if ( ! defined( $constant_name ) ) {
		define( $constant_name, $value );
	}
}
yoko_wp_sanitization_demo_constants( 'DIR', dirname( plugin_basename( __FILE__ ) ) );
yoko_wp_sanitization_demo_constants( 'BASE', plugin_basename( __FILE__ ) );
yoko_wp_sanitization_demo_constants( 'URL', plugin_dir_url( __FILE__ ) );
yoko_wp_sanitization_demo_constants( 'PATH', plugin_dir_path( __FILE__ ) );
yoko_wp_sanitization_demo_constants( 'SLUG', dirname( plugin_basename( __FILE__ ) ) );
yoko_wp_sanitization_demo_constants( 'NAME', $plugin_data['name'] );
yoko_wp_sanitization_demo_constants( 'VERSION', $plugin_data['version'] );
yoko_wp_sanitization_demo_constants( 'TEXT', $plugin_data['text'] );
yoko_wp_sanitization_demo_constants( 'PREFIX', 'yoko_wp_sanitization_demo' );
yoko_wp_sanitization_demo_constants( 'SETTINGS', 'yoko_wp_sanitization_demo' );


/**
 * This class adds a form and displays results to demonstrate the built-in WordPress sanitization functions.
 */
class YokoWPSanitizationDemo {
	/**
	 * API key for authenticated requests.
	 *
	 * @var string
	 */
	private $function_whitelist = array(
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
		'wp_kses_post',
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
		'is_email',
	);

	/**
	 * Fired when the plugin file is loaded.
	 */
	public function __construct() {
		add_shortcode( 'wp-sanitization-demo', array( $this, 'display_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ) );
		add_action( 'wp_ajax_wp_sanitization_demo_update', array( $this, 'go_sanitize' ) );
		add_action( 'wp_ajax_nopriv_wp_sanitization_demo_update', array( $this, 'go_sanitize' ) );
	}

	/**
	 * Conditionally enqueue plugin Javascript, and pass it necessary info from the PHP side via localization.
	 *
	 * @return void
	 */
	public function enqueue_front_end_scripts() {
		global $post;
		// Check if the form is included via a shortcode.
		if (
			! empty( $post->post_content ) &&
			has_shortcode( $post->post_content, 'wp-sanitization-demo' )
		) {
			wp_enqueue_script( 'wp-sanitization-demo', plugins_url( 'js/wp-sanitization-demo.js', __FILE__ ), array( 'jquery' ), gmdate( 'ymdhis' ), true );

			$javascript_object_for_frontend_scripts = array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'home_url'      => home_url(),
				'generic_nonce' => wp_create_nonce( 'wp-sanitization-demo' ),
			);
			wp_localize_script(
				'wp-sanitization-demo',
				'wpsd_global',
				$javascript_object_for_frontend_scripts
			);
		}
	}

	/**
	 * Display the form and placeholder table.
	 *
	 * @return string
	 */
	public function display_form() {
		$out  = '<form class="wp-sanitization-demo" style="margin-bottom: 1em;">' . PHP_EOL;
		$out .= '	<label for="wp-sanitization-demo-input">' . esc_html__( 'Enter the value to sanitize:', 'wp-sanitization-demo' ) . '</label>' . PHP_EOL;
		$out .= '	<input type="text" id="wp-sanitization-demo-input" name="wp-sanitization-demo-input">' . PHP_EOL;
		$out .= '	<input type="submit" id="wp-sanitization-demo-go" value="Go">' . PHP_EOL;
		$out .= '</form>' . PHP_EOL;
		$out .= '<table class="wp-sanitization-demo-results">' . PHP_EOL;
		$out .= '	<tr class="category"><th colspan="2">Built-in WordPress sanitize_*() functions</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_email/">sanitize_email()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_file_name"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_file_name/">sanitize_file_name()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_html_class"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_html_class/">sanitize_html_class()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_key"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_key/">sanitize_key()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_meta"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_meta/">sanitize_meta()</a></td><td class="result">' . esc_html__( 'N/A. Depends on the meta_key.', 'wp-sanitization-demo' ) . '</td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_mime_type"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_mime_type/">sanitize_mime_type()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_option"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_option/">sanitize_option()</a></td><td class="result">' . esc_html__( 'N/A. Depends on the option name.', 'wp-sanitization-demo' ) . '</td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_sql_orderby"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_sql_orderby/">sanitize_sql_orderby()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_text_field"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_text_field/">sanitize_text_field()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_title"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_title/">sanitize_title()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_title_for_query"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_title_for_query/">sanitize_title_for_query()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_title_with_dashes"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_title_with_dashes/">sanitize_title_with_dashes()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_user"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_user/">sanitize_user()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">Integers</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="intval"><td class="function-name"><a href="https://www.php.net/manual/en/function.intval.php">intval()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="absint"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/absint/">absint()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">HTML/XML</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="wp_kses_post"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/wp_kses_post/">wp_kses_post()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="ent2ncr"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/ent2ncr/">ent2ncr()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="wp_rel_nofollow"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/wp_rel_nofollow/">wp_rel_nofollow()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_html"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_html/">esc_html()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_textarea"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_textarea/">esc_textarea()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_text_field"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/sanitize_text_field/">sanitize_text_field()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">Attribute Nodes</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_attr"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_attr/">esc_attr()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_attr__"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_attr__/">esc_attr__()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">JavaScript</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_js"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_js/">esc_js()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">URLs</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_url"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_url/">esc_url()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="esc_url_raw"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/esc_url_raw/">esc_url_raw()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="urlencode"><td class="function-name"><a href="https://www.php.net/manual/en/function.urlencode.php">urlencode()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '	<tr class="category"><th colspan="2">Input validation (where not previously handled)</th></tr>' . PHP_EOL;
		$out .= '	<tr class="sanitize_email"><th class="header-function-name">Function</th><th class="header-result">Result</th></tr>' . PHP_EOL;
		$out .= '	<tr class="balanceTags"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/balanceTags/">balanceTags()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="force_balance_tags"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/force_balance_tags/">force_balance_tags()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="tag_escape"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/tag_escape/">tag_escape()</a></td><td class="result"></td></tr>' . PHP_EOL;
		$out .= '	<tr class="is_email"><td class="function-name"><a href="https://developer.wordpress.org/reference/functions/is_email/">is_email()</a></td><td class="result"></td></tr>' . PHP_EOL;

		$out .= '</table>' . PHP_EOL;

		return $out;
	}

	/**
	 * Take the input, run it through the requested function, and return the results.
	 *
	 * @return mixed The sanitized result.
	 */
	public function go_sanitize() {
		$nonce_check = check_ajax_referer( 'wp-sanitization-demo' );
		if ( false === $nonce_check ) {
			// The nonce is no longer valid, so we can't complete the request.
			wp_send_json_error( esc_html__( "For security, this page must be reloaded every so often to confirm you're still logged in with an account that has permission to make these updates. Please reload the page and try again.", 'yoko-directory' ) );
		}

		$function_name      = wp_unslash( $_POST['functionName'] );
		$string_to_sanitize = wp_unslash( $_POST['stringToSanitize'] );

		if (
			! empty( $function_name ) &&
			! empty( $string_to_sanitize )
		) {
			if ( in_array( $function_name, $this->function_whitelist, true ) ) {

				// Most of these functions simply take the string as an argument, but a few require additional arguments.
				switch ( $function_name ) {
					case 'sanitize_meta':
						$result = $function_name( $string_to_sanitize );
						break;
					default:
						$result = $function_name( $string_to_sanitize );
						break;
				}

				wp_send_json_success( $result );
			} else {
				wp_send_json_success( $string_to_sanitize );
				// Return the string, unmodified.
			}
		}
	}
}

// Load the plugin class.
$GLOBALS['yoko_wp_sanitization_demo'] = new YokoWPSanitizationDemo();

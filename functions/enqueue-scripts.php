<?php 
/**
 * 	Collecttion of Style-/CSS-files
 */
function mbfse_child_style_Files(){
	$f_ass = '/assets/';
	$f_CSS = $f_ass.'scss/';
	$f_JS  = $f_ass.'js/';

	wp_enqueue_style(
		'mb-child-main',
		get_stylesheet_directory_uri().'/style.css',
		array('mbfse-menu-mobile'),
		wp_get_theme()->get( 'Version' )
	);

	/*CSS*/
	wp_enqueue_style(
		'mb-child-header',
		get_stylesheet_directory_uri().$f_CSS.'header.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);

	/*JS*/
	wp_enqueue_script(
		'mb-child-script',
		get_stylesheet_directory_uri().$f_JS.'scripts.js',
		array('jquery', 'mbfse-main'),
		wp_get_theme()->get( 'Version' )
	);
}
add_action('wp_enqueue_scripts', 'mbfse_child_style_Files', 999);
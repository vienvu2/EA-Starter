<?php
/**
 * EA Starter
 *
 * @package      EAStarter
 * @since        1.0.0
 * @copyright    Copyright (c) 2014, Contributors to EA Genesis Child project
 * @license      GPL-2.0+
 */

// Duplicate 'the_content' filters
global $wp_embed;
add_filter( 'ea_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
add_filter( 'ea_the_content', array( $wp_embed, 'autoembed'     ), 8 );
add_filter( 'ea_the_content', 'wptexturize'        );
add_filter( 'ea_the_content', 'convert_chars'      );
add_filter( 'ea_the_content', 'wpautop'            );
add_filter( 'ea_the_content', 'shortcode_unautop'  );
add_filter( 'ea_the_content', 'do_shortcode'       );

/**
 * Shortcut function for get_post_meta();
 *
 * @since 1.2.0
 * @param string $key
 * @param int $id
 * @param boolean $echo
 * @param string $prepend
 * @param string $append
 * @param string $escape
 * @return string
 */
function ea_cf( $key = '', $id = '', $echo = false, $prepend = false, $append = false, $escape = false ) {
	$id    = ( empty( $id ) ? get_the_ID() : $id );
	$value = get_post_meta( $id, $key, true );
	if( $escape )
		$value = call_user_func( $escape, $value );
	if( $value && $prepend )
		$value = $prepend . $value;
	if( $value && $append )
		$value .= $append;

	if ( $echo ) {
		echo $value;
	} else {
		return $value;
	}
}

/**
 * Get the first term attached to post
 *
 * @param string $taxonomy
 * @param string/int $field, pass false to return object
 * @param int $post_id
 * @return string/object
 */
function ea_first_term( $taxonomy = 'category', $field = 'name', $post_id = false ) {

	$post_id = $post_id ? $post_id : get_the_ID();
	$term = false;

	// Use WP SEO Primary Term
	// from https://github.com/Yoast/wordpress-seo/issues/4038
	if( class_exists( 'WPSEO_Primary_Term' ) ) {
		$term = get_term( ( new WPSEO_Primary_Term( $taxonomy,  $post_id ) )->get_primary_term(), $taxonomy );
	}

	// Fallback on term with highest post count
	if( ! $term || is_wp_error( $term ) ) {

		$terms = get_the_terms( $post_id, $taxonomy );

		if( empty( $terms ) || is_wp_error( $terms ) )
			return false;

		// If there's only one term, use that
		if( 1 == count( $terms ) ) {
			$term = array_shift( $terms );

		// If there's more than one...
		} else {

			// Sort by term order if available
			// @uses WP Term Order plugin
			if( isset( $terms[0]->order ) ) {
				$list = array();
				foreach( $terms as $term )
					$list[$term->order] = $term;
				ksort( $list, SORT_NUMERIC );

			// Or sort by post count
			} else {
				$list = array();
				foreach( $terms as $term )
					$list[$term->count] = $term;
				ksort( $list, SORT_NUMERIC );
				$list = array_reverse( $list );
			}

			$term = array_shift( $list );
		}
	}

	// Output
	if( $field && isset( $term->$field ) )
		return $term->$field;

	else
		return $term;
}

/**
 * Conditional CSS Classes
 *
 * @param string $base_classes, classes always applied
 * @param string $optional_class, additional class applied if $conditional is true
 * @param bool $conditional, whether to add $optional_class or not
 * @return string $classes
 */
function ea_class( $base_classes, $optional_class, $conditional ) {
	return $conditional ? $base_classes . ' ' . $optional_class : $base_classes;
}

/**
 * Column Classes
 *
 * @param int $type, number from 2-6
 * @param int $count, current count in the loop
 * @param int $tablet_type, number of columns used on tablets
 * @return string $classes
 */
function ea_column_class( $type, $count, $tablet_type = false ) {
	$output = '';
	$classes = array( '', '', 'one-half', 'one-third', 'one-fourth', 'one-fifth', 'one-sixth' );
	if( !empty( $classes[$type] ) )
		$output = ea_class( $classes[$type], 'first', 0 == $count % $type );

	if( $tablet_type && !empty( $classes[$tablet_type] ) )
		$output .= ' ' . ea_class( 'tablet-' . $classes[$tablet_type], 'tablet-first', 0 == $count % $tablet_type );

	return $output;
}

/**
 * Default Widget Area Arguments
 *
 * @param array $args
 * @return array $args
 */
function ea_widget_area_args( $args = array() ) {

	$defaults = array(
		'name'          => '',
		'id'            => '',
		'description'   => '',
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	);
	$args = wp_parse_args( $args, $defaults );

	if( !empty( $args['name'] ) && empty( $args['id'] ) )
		$args['id'] = sanitize_title_with_dashes( $args['name'] );

	return $args;

}

/**
 * Structural Wraps
 *
 */
function ea_structural_wrap( $context = '', $output = 'open', $echo = true ) {

	$wraps = get_theme_support( 'ea-structural-wraps' );

	//* If theme doesn't support structural wraps, bail.
	if ( ! $wraps )
		return;

	if ( ! in_array( $context, (array) $wraps[0] ) )
		return '';

	//* Save original output param
	$original_output = $output;

	switch ( $output ) {
		case 'open':
			$output = '<div class="wrap">';
			break;
		case 'close':
			$output = '</div>';
			break;
	}

	$output = apply_filters( "ea_structural_wrap-{$context}", $output, $original_output );

	if ( $echo )
		echo $output;
	else
		return $output;
}

/**
 * Page Layout
 *
 */
function ea_page_layout() {

	$available_layouts = array( 'full-width-content', 'content-sidebar', 'sidebar-content' );
	$layout = 'full-width-content';

	$layout = apply_filters( 'ea_page_layout', $layout );
	$layout = in_array( $layout, $available_layouts ) ? $layout : $available_layouts[0];

	return sanitize_title_with_dashes( $layout );
}

/**
 * Return Full Width Content
 * used when filtering 'ea_page_layout'
 */
function ea_return_full_width_content() {
	return 'full-width-content';
}

/**
 * Return Content Sidebar
 * used when filtering 'ea_page_layout'
 */
function ea_return_content_sidebar() {
	return 'content-sidebar';
}

/**
 * Return Sidebar Content
 * used when filtering 'ea_page_layout'
 */
function ea_return_sidebar_content() {
	return 'sidebar-content';
}

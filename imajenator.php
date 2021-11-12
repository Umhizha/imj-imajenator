<?php
/*
Plugin Name: Imajenator
Plugin URI: http://imajenation.co.zw
Description: The Imajenaizer Of Sites.
Version: 1.0
Author: Imajenation Media
Author URI: http://imajenation.co.zw
License: GPL2
*/

/**
 * Function for registering a featured image rest api field
 * Replace "gallery" with your custom post type" 
 * Replace "fimg_url" with your desired field name
 * repeat "register_rest_field" for your desired post type 
 * */ 

add_action('rest_api_init', 'register_rest_images_function' );
function register_rest_images_function(){
    register_rest_field( 
        array('gallery'),
        'fimg_url',
        array(
            'get_callback'    => 'get_rest_featured_image',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
function get_rest_featured_image( $object, $field_name, $request ) {

    if( $object['featured_media'] ){
        $img = wp_get_attachment_image_src( $object['featured_media'], 'app-thumb' );
        return $img[0];
    }
    return false;
}

/**
 * Function for registering a default gallery rest api field
 * Replace "gallery" with your custom post type" 
 * Replace "gallery_pics" with desired field name
 * repeat "register_rest_field" for your desired post type 
 * 
 * */ 
add_action('rest_api_init', 'gallery_rest_field_function');

function gallery_rest_field_function(){
    register_rest_field(
        'gallery', 
        'gallery_pics', 
        array(
            'get_callback' => 'func_to_get_meta_data', 
            'update_callback' => null, 
            'schema' => null
        )
    );
}

function func_to_get_meta_data($obj, $name, $request){
    return get_attached_media('image', $obj['id']);
}


/**
 * Function for modifying JWT response
 * You can add your own custom fields like phone number with relevant callbacks
 * 
 * */ 
function mod_jwt_auth_token_before_dispatch( $data, $user ) {
    $user_info = get_user_by( 'email',  $user->data->user_email );
    $profile = array (
        'id' => $user_info->id,
        'user_first_name' => $user_info->first_name,
        'user_last_name' => $user_info->last_name,
        'user_email' => $user->data->user_email,
        'user_nicename' => $user->data->user_nicename,
        'user_display_name' => $user->data->display_name,
        //'phone' => get_field( 'phone', "user_$user_info->id" ) // you also can get ACF fields
       
    );
    $response = array(
        'token' => $data['token'],
        'profile' => $profile
    );
    return $response;
}
add_filter( 'jwt_auth_token_before_dispatch', 'mod_jwt_auth_token_before_dispatch', 10, 2 );

add_action('wp_rest_user_user_register', 'user_registered');
function user_registered($user) {
    // Do Something
    wp_new_user_notification($user);

    // wp_send_new_user_notifications( $user->ID );
}


/**
 *  Function for formatting rest api date 
 *  Change "post" to the post type on which you want this to appear
 *  Repeat for all your desired post types
 */
add_action('rest_api_init', 'add_rest_date_function');

function add_rest_date_function() {
    register_rest_field(
        array('post'),
        'formatted_date',
        array(
            'get_callback'    => function() {
                return get_the_date();
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );

    register_rest_field(
        array('post'),
        'time_ago',
        array(
            'get_callback'    => 'my_post_time_ago_function'
            'update_callback' => null,
            'schema'          => null,
        )
    );

     register_rest_field(
        array('post'),
        'uptoweek_ago',
        array(
            'get_callback'    => 'altered_post_time_ago_function'
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function my_post_time_ago_function() {
return sprintf( esc_html__( '%s ago', 'textdomain' ), human_time_diff(get_the_time ( 'U' ), current_time( 'timestamp' ) ) );
}

function altered_post_time_ago_function() {
return ( get_the_time('U') >= strtotime('-1 week') ) ? sprintf( esc_html__( '%s ago', 'textdomain' ), human_time_diff( get_the_time ( 'U' ), current_time( 'timestamp' ) ) ) : get_the_date();
}
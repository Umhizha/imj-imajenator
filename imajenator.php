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

 
add_action('rest_api_init', 'register_rest_images' );
function register_rest_images(){
    register_rest_field( array('gallery'),
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

add_action('rest_api_init', function(){register_rest_field('gallery', 'gallery_pics', array('get_callback' => 'func_to_get_meta_data', 'update_callback' => null, 'schema' => null));});

function func_to_get_meta_data($obj, $name, $request){return get_attached_media('image', $obj['id']);}

function mod_jwt_auth_token_before_dispatch( $data, $user ) {
    $user_info = get_user_by( 'email',  $user->data->user_email );
    $profile = array (
        'id' => $user_info->id,
        'user_first_name' => $user_info->first_name,
        'user_last_name' => $user_info->last_name,
        'user_email' => $user->data->user_email,
        'user_nicename' => $user->data->user_nicename,
        'user_display_name' => $user->data->display_name,
       
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
}

add_action('rest_api_init', function() {
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
});
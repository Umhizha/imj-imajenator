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
            'get_callback'    => 'altered_post_time_ago_function',
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

add_action('rest_api_init', 'imj_register_new_route');

function imj_posts(){
    $args = [
        'numberposts' => 99999,
        'post_type' => 'code_note'
    ];

    $posts = get_posts($args);
    $data = [];
    $i = 0;

    foreach($posts as $post){
        $data[$i]['id'] = $post->ID;
        $data[$i]['title'] = $post->post_title;
        $data[$i]['content'] = $post->post_content;
        $data[$i]['slug'] = $post->post_name;
        $data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
        $data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
        $data[$i]['featured_image']['large'] = get_the_post_thumbnail_url( $post->ID, 'large' );
        $i++;

    }

    return $data;
}

function imj_post($slug){
    $args = [
        'name'      => $slug['slug'],
        'post_type' => 'code_note'
    ];

    $post = get_posts($args);
    $code_note = pods( 'code_note', $post[0]->ID);

    $data['id'] = $post[0]->ID;
    $data['title'] = $post[0]->post_title;
    $data['content'] = $post[0]->post_content;
    $data['slug'] = $post[0]->post_name;
    $data['string'] = $code_note->field( 'string' );
    $data['featured_image']['thumbnail'] = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
    $data['featured_image']['medium'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
    $data['featured_image']['large'] = get_the_post_thumbnail_url( $post->ID, 'large' );
    $data['biscuit']['type'] = "Lobels";
    $data['biscuit']['cat'] = array(0 => 5, 1=>7);
    return $data;
}

function imj_galleries(){

    $args = [
        'numberposts' => 99999,
        'post_type' => 'gallery'
    ];

    $galleries = get_posts($args);
    $data =[];
    $i = 0;
   
   foreach($galleries as $gallery){

        $gallery_field = pods('gallery', $gallery->ID);
        $imagesFull = $gallery_field->field('gallery');
        $imagesTrim = [];
        $ii = 0;

        if($imagesFull != null){
            foreach($imagesFull as $image){
                $imagesTrim[$ii]['ID'] = $image['ID'];
                $imagesTrim[$ii]['url'] = $image['guid'];
                $imagesTrim[$ii]['title'] = $image['post_title'];
                $imagesTrim[$ii]['mime_type'] = $image['post_mime_type'];
                $imagesTrim[$ii]['date'] = $image['post_date'];
                $imagesTrim[$ii]['date_gmt'] = $image['post_date_gmt'];
                $imagesTrim[$ii]['author'] = $image['post_author'];
                $imagesTrim[$ii]['parent_gallery'] = $image['post_parent'];
                $ii++;
            }
        }

        $data[$i]['id'] = $gallery->ID;
        $data[$i]['title'] = $gallery->post_title; 
        $data[$i]['slug'] = $gallery->post_name;
        $data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url( $gallery->ID, 'thumbnail' );
        $data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url( $gallery->ID, 'medium' );
        $data[$i]['featured_image']['large'] = get_the_post_thumbnail_url( $gallery->ID, 'large' );
        $data[$i]['images'] = $imagesTrim;
        $i++;
   }
   return $data;
}

function imj_get_clients(){

    $args = [
        'numberposts' => 99999,
        'post_type' => 'client'
    ];

    $clients = get_posts($args);
    $data = [];
    $i = 0;
   
   foreach($clients as $client){

        $pod = pods('client', $client->ID);
        $projects = $pod->field('projects');
        $projects_data = [];
        $pi= 0;

        foreach($projects as $project){
           $proj_pod = pods('project', $project['ID']);

           $projects_data[$pi]['id'] = $project['ID'];
           $projects_data[$pi]['title'] = $project['post_title'];
           $projects_data[$pi]['slug'] = $project['post_name'];
           $projects_data[$pi]['proj_string'] = $proj_pod->field('proj_string');
           $projects_data[$pi]['proj_json'] = $proj_pod->field('proj_json');
           $projects_data[$pi]['client'] = $proj_pod->field('client')['post_title'];
           $pi++;
        }

        $data[$i]['id'] = $client->ID;
        $data[$i]['title'] = $client->post_title; 
        $data[$i]['slug'] = $client->post_name;
        $data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url( $client->ID, 'thumbnail' );
        $data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url( $client->ID, 'medium' );
        $data[$i]['featured_image']['large'] = get_the_post_thumbnail_url( $client->ID, 'large' );
        $data[$i]['projects'] = $projects_data;
        $i++;
   }
   return $data;
}

function imj_add_gallery($request){
    // Get the book pod object
    $body = $request->get_params();
    $pod = pods( 'gallery' );
    // To add a new item, let's set the data first
    $data = array(
        'title' => $body['title'],
        'author' => 1, // User ID for relationship field
        'content' => '',
        'gallery' => $body['gallery']
    );
    // Add the new item now and get the new ID
    $new_book_id = $pod->add( $data );
    return $new_book_id;
}

function imj_update_gallery($request){
    $id = $request['id'];
    $body = $request->get_params();
    $new_gallery = $body['gallery'];
    $raw_ids = [];

    // Get the gallery item by ID 
    $pod = pods( 'gallery', $id );

    foreach($new_gallery as $image){    
        array_push($raw_ids, $image['id']);  
    }

    //$pod->save( 'gallery', $body['gallery'] );

    // Set a group of fields to specific values
    $data = array(
        'title' => $body['title'],
        'author' => 2,
        //'gallery' => $raw_arry
    );

    // Save the data as set above
    $pod->save( $data );
    $pod->add_to( 'gallery', $raw_ids);

    return $raw_ids;
}

function imj_update_client($request){
    $id = $request['id'];
    $body = $request->get_params();
    $new_projects = $body['projects'];
    $raw_ids=[];

    // Get the gallery item by ID 
    $pod = pods( 'client', $id );

    foreach($new_projects as $project){    
        array_push($raw_ids, $project['id']);  
    }

    //$pod->save( 'gallery', $body['gallery'] );

    // Set a group of fields to specific values
    $data = array(
        'title' => $body['title'],
        'author' => 2,
        //'gallery' => $raw_arry
    );

    // Save the data as set above
    $pod->save( $data );
    $pod->add_to( 'projects', $raw_ids);

    return $raw_ids;
}

function imj_register_new_route(){
    //namespce + version, name of route, content of the whole JSON endpoint
    register_rest_route('imj/v1', 'posts', [
        'methods' => 'GET',
        'callback' => 'imj_posts'
    ]);

     register_rest_route('imj/v1', 'posts/(?P<slug>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'imj_post'
    ));

    register_rest_route('imj/v1', 'gallery', [
        'methods' => 'GET',
        'callback' => 'imj_galleries',
    ]);

    register_rest_route('imj/v1', 'gallery_new', [
        'methods' => 'POST',
        'callback' => 'imj_add_gallery'
        
    ]);

    register_rest_route('imj/v1', 'gallery_edit/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'imj_update_gallery'
    ]);

    register_rest_route('imj/v1', 'project_edit/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'imj_update_project'
    ]);

    register_rest_route('imj/v1', 'client_edit/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'imj_update_client'
    ]);

     register_rest_route('imj/v1', 'client', [
        'methods' => 'GET',
        'callback' => 'imj_get_clients'
    ]);
    
    

    register_rest_route('imj/v1', 'gallery/(?P<slug>[a-zA-Z0-9-]+)', [
        'methods' => 'GET, POST, PUT, PATCH, DELETE',
        'callback' => 'imj_gallery'
    ]);
}
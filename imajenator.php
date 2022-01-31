<?php
/*
Plugin Name: Imajenator
Plugin URI: http://imajenation.co.zw
Description: The Imajenaizer Of Sites.
Version: 1.1
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
 * 
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
            'get_callback'    => 'my_post_time_ago_function',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function my_post_time_ago_function() {
return sprintf( esc_html__( '%s ago', 'textdomain' ), human_time_diff(get_the_time ( 'U' ), current_time( 'timestamp' ) ) );
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

function imj_post($request){
    $args = [
        'name'      => $request['slug'],
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

function imj_get_user_courses($request){

   $user_id = $request['id'];
    $the_ids = get_enrolled_courses_ids_by_user($user_id);

    $course_args = array(
                    'post_type'      => "courses",
                    'post_status'    => "publish",
                    'post__in'       => $the_ids,
                    'posts_per_page' => -1
                );
    $the_courses = get_posts( $course_args );
   

    $user_courses = [];
    $i = 0;
   // Prepare Course List
   foreach($the_courses as $course){

        
        $topic_list = [];
        $ti= 0;

        $topics = get_topics($course->ID);
//var_dump($topics);
        // Prepare topic lists
        foreach($topics as $topic){
         
           $topic_id = $topic->ID;

           $quiz_obj = quiz_with_settings($topic_id);
           $quiz_list = $quiz_obj['data'];
          // var_dump($quiz_list);
           $quizes = [];
           $qi = 0;

           foreach($quiz_list as $each_quiz){
               
               $quiz_qna_obj = quiz_question_ans($each_quiz->ID);
               $quiz_qna = $quiz_qna_obj['data'];

               $quizes[$qi]['id'] = $each_quiz->ID;
               $quizes[$qi]['title'] = $each_quiz->post_title;
               $quizes[$qi]['content'] = $each_quiz->post_content;
               $quizes[$qi]['slug'] = $each_quiz->post_name;
               $quizes[$qi]['qna'] = $quiz_qna;
               $qi++;
           }

           $topic_list[$ti]['id'] = $topic->ID;
           $topic_list[$ti]['title'] = $topic->post_title;
           $topic_list[$ti]['slug'] = $topic->post_name;
           $topic_list[$ti]['quizes'] = $quizes;
           $ti++;
        }

        $user_courses[$i]['id'] = $course->ID;
        $user_courses[$i]['title'] = $course->post_title; 
        $user_courses[$i]['slug'] = $course->post_name;
        $user_courses[$i]['content'] = $course->post_content;
        $user_courses[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url( $course->ID, 'thumbnail' );
        $user_courses[$i]['featured_image']['medium'] = get_the_post_thumbnail_url( $course->ID, 'medium' );
        $user_courses[$i]['featured_image']['large'] = get_the_post_thumbnail_url( $course->ID, 'large' );
        $user_courses[$i]['topics'] = $topic_list;
        $i++;
   }
   return $user_courses;
}

$the_ids = get_enrolled_courses_ids_by_user();

    $course_args = array(
                    'post_type'      => "courses",
                    'post_status'    => "publish",
                    'post__in'       => $the_ids,
                    'posts_per_page' => -1
                );
    $the_courses = get_posts( $course_args );
 //var_dump($the_courses);
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

      register_rest_route('imj/v1', 'user_courses/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'imj_get_user_courses'
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

function get_enrolled_courses_ids_by_user( $user_id = 1 ) {
		global $wpdb;
		$user_id = 1;
		$course_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_parent
			FROM 	{$wpdb->posts}
			WHERE 	post_type = %s
					AND post_status = %s
					AND post_author = %d;
			",
			'tutor_enrolled',
			'completed',
			$user_id
		) );

		return $course_ids;
	}



function get_topics( $course_id = 0 ) {
		$course_id = $course_id;

		$args = array(
			'post_type'      => 'topics',
			'post_parent'    => $course_id,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		);

		$query = get_posts( $args );

		return $query;
}
function get_active_courses_by_user( $user_id = 0 ) {
    
		$user_id             = get_user_id( $user_id );
		$course_ids          = get_completed_courses_ids_by_user( $user_id );
		$enrolled_course_ids = get_enrolled_courses_ids_by_user( $user_id );
		$active_courses      = array_diff( $enrolled_course_ids, $course_ids );

		if ( count( $active_courses ) ) {
			$course_post_type = tutor()->course_post_type;
			$course_args = array(
				'post_type'      => $course_post_type,
				'post_status'    => 'publish',
				'post__in'       => $active_courses,
                'posts_per_page' => -1,
			);

			return new \WP_Query( $course_args );
		}

		return false;
	}

function get_completed_courses_ids_by_user( $user_id = 0 ) {
		global $wpdb;

		$user_id = 1;

		$course_ids = (array) $wpdb->get_col( $wpdb->prepare(
			"SELECT comment_post_ID AS course_id
			FROM 	{$wpdb->comments} 
			WHERE 	comment_agent = %s 
					AND comment_type = %s
					AND user_id = %d
			",
			'TutorLMSPlugin',
			'course_completed',
			$user_id
		) );

		return $course_ids;
	}

function do_enroll( $course_id = 0, $order_id = 0, $user_id = 0 ) {
		if ( ! $course_id ) {
			return false;
		}

		do_action( 'tutor_before_enroll', $course_id );
		$user_id = $user_id;
		$title = __( 'Course Enrolled', 'tutor')." &ndash; ".date( get_option('date_format') ) .' @ '.date(get_option('time_format') ) ;

		$enrolment_status = 'completed';

		$enroll_data = apply_filters( 'tutor_enroll_data',
			array(
				'post_type'     => 'tutor_enrolled',
				'post_title'    => $title,
				'post_status'   => $enrolment_status,
				'post_author'   => $user_id,
				'post_parent'   => $course_id,
			)
		);

        var_dump($enroll_data);
		// Insert the post into the database
		$isEnrolled = wp_insert_post( $enroll_data );
        var_dump($isEnrolled);
		if ( $isEnrolled ) {

			// Run this hook for both of pending and completed enrollment
			do_action( 'tutor_after_enroll', $course_id, $isEnrolled );

			// Run this hook for completed enrollment regardless of payment provider and free/paid mode
			if( $enroll_data['post_status'] == 'completed' ) {
				do_action('tutor_after_enrolled', $course_id, $user_id, $isEnrolled);
			}

			//Mark Current User as Students with user meta data
			update_user_meta( $user_id, '_is_tutor_student', tutor_time() );

			if ( $order_id ) {
				//Mark order for course and user
				$product_id = get_course_product_id( $course_id );
				update_post_meta( $isEnrolled, '_tutor_enrolled_by_order_id', $order_id );
				update_post_meta( $isEnrolled, '_tutor_enrolled_by_product_id', $product_id );
				update_post_meta( $order_id, '_is_tutor_order_for_course', tutor_time() );
				update_post_meta( $order_id, '_tutor_order_for_course_id_'.$course_id, $isEnrolled );
			}
			return true;
		}

		return false;
	}

function get_course_settings( $course_id = 0, $key = null, $default = false ) {
    $course_id     = get_post_id( $course_id );
    $settings_meta = get_post_meta( $course_id, '_tutor_course_settings', true );
    $settings      = (array) maybe_unserialize( $settings_meta );

    return array_get( $key, $settings, $default );
}

function quiz_question_ans($thequiz_id) {
     $post_type = "tutor_quiz";
	 $t_quiz_question = "tutor_quiz_questions";
	 $t_quiz_ques_ans = "tutor_quiz_question_answers";
	 $t_quiz_attempt = "tutor_quiz_attempts";
	 $t_quiz_attempt_ans = "tutor_quiz_attempt_answers";
	global $wpdb;

    $post_parent = $thequiz_id;


		$q_t = $wpdb->prefix.$t_quiz_question;//question table

		$q_a_t = $wpdb->prefix.$t_quiz_ques_ans;//question answer table

		$quizs = $wpdb->get_results(
			$wpdb->prepare("SELECT question_id,question_title, question_description, question_type, question_mark, question_settings FROM $q_t WHERE quiz_id = %d", $post_parent)
		);	
      //  var_dump($quizs, $q_t, $q_a_t, $post_parent);		
		$data = [];

		if (count($quizs)>0) {

			//get question ans by question_id
			foreach ($quizs as $quiz) {
				//unserialized question settings
				$quiz->question_settings = maybe_unserialize($quiz->question_settings);

				//question options with correct ans
				$options = $wpdb->get_results(
					$wpdb->prepare("SELECT answer_title,is_correct FROM $q_a_t WHERE belongs_question_id = %d", $quiz->question_id)
				);

				//set question_answers as quiz property
				$quiz->question_answers = $options;

				array_push($data, $quiz);
			}

			$response = array(
				'status_code'=> 'success',
				'message'=> __('Question retrieved successfully','tutor'),
				'data'=> $data
			);

			return $response;
		}

		$response = array(
			'status_code'=> 'not_found',
			'message'=> __('Question not found for given ID','tutor'),
			'data'=> []
		);

		return $response;		
	}

   




function quiz_with_settings($topic_id) {
    $post_type = "tutor_quiz";
		$post_parent = $topic_id;

		global $wpdb;

		$table = $wpdb->prefix."posts";

		$quizs = $wpdb->get_results(
			$wpdb->prepare("SELECT ID, post_title, post_content, post_name FROM $table WHERE post_type = %s AND post_parent = %d", $post_type, $post_parent)
		);

		$data = [];

		if (count($quizs)>0) {
			foreach ($quizs as $quiz) {
				$quiz->quiz_settings = get_post_meta($quiz->ID,'tutor_quiz_option',false);

				array_push($data, $quiz);

				$response = array(
					'status_code'=> 'success',
					'message'=> __("Quiz retrieved successfully",'tutor'),
					'data'=> $data
				);
			}
			return $response;
		}	
		$response = array(
			'status_code'=> 'not_found',
			'message'=> __("Quiz not found for given ID",'tutor'),
			'data'=> $data
		);
		return $response;
	}

function tutor_time() {
        //return current_time( 'timestamp' );
        return time() + (get_option('gmt_offset') * HOUR_IN_SECONDS);
}

//do_enroll(248, 0, 1);

     // Pass in the quiz ID
    //$qna = quiz_question_ans(181);

    // pass in the topic ID
  //  $qna = quiz_with_settings(179);
  
//   $uc = imj_get_user_courses(1);

//   var_dump($uc);
<?php

    /**
    * Plugin Name: Wordpress Prometheus Exporter
    * Plugin URI: https://github.com/nicolasreymond/wp-metrics.git
    * Description:  This Wordpress plugin exports metrics for prometheus to scrape 'em
    * Author: Nicolas Reymond
    * Author URI: https://github.com/nicolasreymond
    * Version: 0.3
    */

    include "wp-metrics-admin.php";
    if (isset($_POST["password"])) {
        update_option("pass", $_POST["password"]);
    }

    function my_awesome_func( $data ) {
        return "";
    }

    // reference https://github.com/WP-API/WP-API/blob/develop/lib/infrastructure/class-wp-rest-server.php
    // serve_request() function
    add_action( 'rest_api_init', function () {
      register_rest_route( 'metrics', '/', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
      ) );
    } );

    function get_wordpress_metrics(){
        $result="";

        
        $users=count_users();
        $result.="# TYPE wp_users_total gauge\n";
        $result.="# HELP wp_users_total Total number of users.\n";
        $result.="wp_users_total ".$users['total_users']."\n";
        
        $result.="# TYPE wc_total_orders gauge\n";
        $result.="# HELP wc_total_orders Total number of woocomerce order.\n";
        $total_order = new WP_Query(array('post_type'=>'shop_order','post_status'=> array('wc-completed', 'wc-processing')));
        $result.="wc_total_orders ".$total_order->found_posts."\n";

        $last_rev_id = get_last_revivion_id();
        $result.="# TYPE wp_last_rev_id gauge\n";
        $result.="# HELP wp_last_rev_id Last id of revision post.\n";
        $result.="wp_last_rev_id ".$last_rev_id."\n"; 
        
        $result.="# TYPE wc_product_by_category gauge\n";
        $result.="# HELP wc_product_by_category Total of product for each categories.\n";
        $all_cats=product_cats();
        foreach ($all_cats as $cat) {
            $all_ids = get_posts( array(
                'post_type' => 'product',
                'numberposts' => -1,
                'post_status' => 'publish',
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $cat, /*category name*/
                        'operator' => 'IN',
                        )
                    ),
                )
            );
            $result.= "wc_product_by_category{category=\"".$cat."\"} ". count($all_ids). "\n";  
        }
        
        $posts=wp_count_posts();
        $posts_pub=$posts->publish;
        $posts_dra=$posts->draft;
        $pages=wp_count_posts('page');

        // $df contient le nombre d'octets libres sur "/"
        $df = disk_free_space("/");
        $result.="# TYPE wp_posts_published_total gauge\n";
        $result.="# HELP wp_posts_published_total Total number of posts published.\n";
        $result.="wp_upload_free_storage ".$df."\n";

        $result.="# TYPE wp_posts_published_total gauge\n";
        $result.="# HELP wp_posts_published_total Total number of posts.\n";
        $result.='wp_posts_total{status="published"} '.$posts_pub."\n";
        $result.='wp_posts_total{status="draft"} '.$posts_dra."\n";

        $result.="# TYPE wp_pages_total counter\n";
        $result.="# HELP wp_pages_total Total number of posts published.\n";
        $result.='wp_pages_total{status="published"} '.$pages->publish."\n";
        $result.='wp_pages_total{status="draft"} '.$pages->draft."\n";

        return $result;
    }
    
    function render_metrics( $served, $result, $request, $server ) {
        // assumes 'format' was passed into the intial API route
        // example: https://baconipsum.com/wp-json/baconipsum/test-response?format=text
        // the default JSON response will be handled automatically by WP-API
        if ( $request->get_route()=="/metrics" ) {
                header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
                $metrics=get_wordpress_metrics();
                echo $metrics;
                $served = true; // tells the WP-API that we sent the response already
            
            
        }
        return $served;
    }
    add_filter( 'rest_pre_serve_request', 'render_metrics', 10, 4 );

    function get_space_used() {
    /**
     * Filters the amount of storage space used by the current site, in megabytes.
     *
     * @since 3.5.0
     *
     * @param int|false $space_used The amount of used space, in megabytes. Default false.
     */
    $space_used = apply_filters( 'pre_get_space_used', false );
 
    if ( false === $space_used ) {
        $upload_dir = wp_upload_dir();
        $space_used = get_dirsize( $upload_dir['basedir'] ) / MB_IN_BYTES;
    }
 
    return $space_used;
}
   
function product_cats() {
    $options = array();

    $categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
    foreach( $categories as $category ) {
        $options[$category->term_id] = $category->slug;
    }
    return $options;
}

function get_last_revivion_id(){
    $query = new WP_Query( array( 'post_type' => 'revision', 'post_status' => 'inherit' ) );
    $posts = $query->posts;

    foreach($posts as $post) {
        $max_rev_id = 0;
        if ($post->ID >= $max_rev_id) {
            $max_rev_id = $post->ID;
        } 
    }
    return $max_rev_id;
}

function ensure_is_logged(){
    $url = $_SERVER['REQUEST_URI'];
    if (!preg_match("/\/metrics$/", $url)) {
        return;
    }   
  
  if ( is_user_logged_in() ) {
        return;
    } else {

        if (!get_option("pass")) {
            add_option("pass", "root");
        }
        
        $validated = ($_SERVER['PHP_AUTH_PW'] == get_option("pass") && isset($_SERVER['PHP_AUTH_USER']));
        
        if (!$validated) {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            die ("Not authorized");
        }
    }
}
add_action('init', 'ensure_is_logged');



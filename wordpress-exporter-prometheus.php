<?php

    /**
    * Plugin Name: Wordpress Prometheus Exporter
    * Plugin URI: https://github.com/nicolasreymond/wp-metrics.git
    * Description:  This Wordpress plugin exports metrics for prometheus to scrape 'em
    * Author: Nicolas Reymond
    * Author URI: https://github.com/nicolasreymond
    * Version: 0.1
    */
     
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
        $result.="# HELP wp_users_total Total number of users.\n";
        $result.="# TYPE wp_users_total gauge\n";
        $users=count_users();
        $result.="wp_users_total ".$users['total_users']."\n";

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
        $n_posts_pub=$posts->publish;
        $n_posts_dra=$posts->draft;
        $n_pages=wp_count_posts('page');

        $result.="# HELP wp_posts_published_total Total number of posts published.\n";
        $result.="# TYPE wp_posts_published_total gauge\n";
        $result.='wp_posts_total{status="published"} '.$n_posts_pub."\n";

        $result.="# HELP wp_posts_draft_total Total number of posts drafted.\n";
        $result.="# TYPE wp_posts_draft_total gauge\n";
        $result.='wp_posts_total{status="draft"} '.$n_posts_dra."\n";

        $result.="# HELP wp_pages_total Total number of posts published.\n";
        $result.="# TYPE wp_pages_total counter\n";
        $result.='wp_pages_total{status="published"} '.$n_pages->publish."\n";
        $result.='wp_pages_total{status="draft"} '.$n_pages->draft."\n";

        return $result;
    }
    
    function multiformat_rest_pre_serve_request( $served, $result, $request, $server ) {
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
    add_filter( 'rest_pre_serve_request', 'multiformat_rest_pre_serve_request', 10, 4 );

function product_cats() {
    $options = array();

    $categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
    // var_dump($categories);
    foreach( $categories as $category ) {
        $options[$category->term_id] = $category->slug;
    }
    // return array('options'=>$options);
    return $options;
}
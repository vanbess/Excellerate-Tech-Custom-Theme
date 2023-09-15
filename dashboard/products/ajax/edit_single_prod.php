<?php

defined('ABSPATH') ?: exit();

/**
 * Edit single product AJAX
 */
add_action('wp_ajax_nopriv_extech_edit_single_prod', 'extech_edit_single_prod');
add_action('wp_ajax_extech_edit_single_prod', 'extech_edit_single_prod');

function extech_edit_single_prod() {

    check_ajax_referer('edit prod nonce');

    // debug
    // wp_send_json($_POST);

    // grab subbed values and sanitize
    $product_title         = sanitize_text_field($_POST["product_title"]);
    $product_sku           = sanitize_text_field($_POST["product_sku"]);
    $product_regular_price = floatval($_POST["product_regular_price"]);
    $product_sale_price    = isset($_POST["product_sale_price"]) ? floatval($_POST["product_sale_price"]) : false;
    $product_description   = sanitize_text_field($_POST["product_description"]);
    $product_image         = $_FILES["product_image"];
    $product_status        = sanitize_text_field($_POST["product_status"]);
    $product_stock         = intval($_POST['product_stock']);

    // get product id
    $pid = intval($_POST['pid']);

    // wp_send_json($pid);

    // get current blog id
    $blog_id = intval($_POST['current_blog_id']);

    // wp_send_json($blog_id);

    // switch to blog
    switch_to_blog($blog_id);

    // debug: check if we can retrieve product object successfully
    // $product = get_post($pid);
    // wp_send_json($product);

    // update product meta
    update_post_meta($pid, '_sku', $product_sku);
    update_post_meta($pid, '_regular_price', $product_regular_price);
    update_post_meta($pid, '_sale_price', $product_sale_price);
    update_post_meta($pid, '_stock', $product_stock);
    
    // update product post title
    wp_update_post([
        'ID' => $pid,
        'post_title' => $product_title
    ]);

    // update product post content
    wp_update_post([
        'ID' => $pid,
        'post_content' => $product_description
    ]);

    // update post status
    wp_update_post([
        'ID' => $pid,
        'post_status' => $product_status
    ]);

    // if product image exists, insert and attach
    if ($product_image["size"] > 0) :

        // upload image
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($product_image["tmp_name"]);
        $filename   = $product_image["name"];

        // check if directory exists
        if (wp_mkdir_p($upload_dir["path"])) :
            $file = $upload_dir["path"] . "/" . $filename;
        else :
            $file = $upload_dir["basedir"] . "/" . $filename;
        endif;

        // write image data to file
        file_put_contents($file, $image_data);

        // create attachment
        $wp_filetype = wp_check_filetype($filename, null);

        // set up attachment data
        $attachment = array(
            "post_mime_type" => $wp_filetype["type"],
            "post_title"     => sanitize_file_name($filename),
            "post_content"   => "",
            "post_status"    => "inherit"
        );

        // insert attachment
        $attach_id = wp_insert_attachment($attachment, $file, $pid);

        // generate attachment data
        require_once(ABSPATH . "wp-admin/includes/image.php");

        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

        // update attachment data
        wp_update_attachment_metadata($attach_id, $attach_data);

        // set product thumbnail
        set_post_thumbnail($pid, $attach_id);

    endif;

    // send success response
    wp_send_json_success(['message' => 'Product updated successfully.']);

    // restore current blog
    restore_current_blog();

    wp_die();
}

<?php
/*
Plugin Name: WP Thumbnail Directory
Description: WP Thumbnail Directory
Version: 0.1.0
Author: Solido Labs
Author URI: http://soli.do/
*/

namespace Solido\WPThumbnailDirectory;

add_filter('wp_image_editors', __NAMESPACE__ . '\\wp_image_editors', 10, 1);
add_filter('wp_prepare_attachment_for_js', __NAMESPACE__ . '\\wp_prepare_attachment_for_js', 10, 3);
add_filter('media_send_to_editor', __NAMESPACE__ . '\\media_send_to_editor', 10, 3);
add_filter('wp_get_attachment_image_src', __NAMESPACE__ . '\\wp_get_attachment_image_src', 10, 4);
add_filter('wp_calculate_image_srcset', __NAMESPACE__ . '\\wp_calculate_image_srcset', 10, 5);

function wp_image_editors($editors)
{
    require_once(__DIR__ . '/include/class.xmedia-wp-image-editor-gd.php');
    require_once(__DIR__ . '/include/class.xmedia-wp-image-editor-imagick.php');
    foreach ($editors as $k => $editor) {
        if (class_exists('XMedia_' . $editor)) $editors[$k] = 'XMedia_' . $editor;
    }
    return $editors;
}

function get_thumbs_upload_url($upload_dir)
{
    $upload_url = $upload_dir['baseurl'];
    return $upload_url . '/_cache';
}

function wp_prepare_attachment_for_js($response, $attachment, $meta)
{
    $upload_dir = wp_upload_dir();  
    $upload_url = $upload_dir['baseurl'];    
    $thumbs_upload_url = get_thumbs_upload_url($upload_dir);

    foreach ($response['sizes'] as $k => $v) {
        if ($k === 'full') continue;
        $response['sizes'][$k]['url'] = str_replace($upload_url, $thumbs_upload_url, $v['url']);
    }

    return $response;
}

function media_send_to_editor($html, $id, $attachment)
{
    if ($attachment['image-size'] == 'full') {
        return $html;
    }

    $upload_dir = wp_upload_dir();
    $upload_url = $upload_dir['baseurl'];
    $thumbs_upload_url = get_thumbs_upload_url($upload_dir);

    $html = str_replace($upload_url, $thumbs_upload_url, $html);
    
    return $html;
}

function wp_get_attachment_image_src($image, $attachment_id, $size, $icon)
{
    if (is_array($size)) return $image;  
    if (!$size || $size == 'full') return $image;

    $upload_dir = wp_upload_dir();
    $upload_url = $upload_dir['baseurl'];
    $thumbs_upload_url = get_thumbs_upload_url($upload_dir);

    $image[0] = str_replace($upload_url, $thumbs_upload_url, $image[0]);  
    $image_url = $image[0];

    $image_path = str_replace($upload_url, '', $image_url);
    $image_path = $upload_dir['basedir'] . $image_path;

    if (!file_exists($image_path)) {
        // do the full image exists?
        $full_image_src = \wp_get_attachment_image_src($attachment_id, 'full');
        if ($full_image_src) {
            $full_image_path = str_replace($upload_url, '', $full_image_src[0]);
            $full_image_path = $upload_dir['basedir'] . $full_image_path;
            
            if (file_exists($full_image_path)) {
                $sizes = get_image_sizes();
                $editor = wp_get_image_editor($full_image_path);
                $editor->multi_resize($sizes);
            }
        }
    }

    return $image;
}

function get_image_sizes($size = null) {
    global $_wp_additional_image_sizes;

    $sizes = array();

    foreach ( get_intermediate_image_sizes() as $_size ) {

        if ($size && $size !== $_size) continue;

        if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
            $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
            $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
            $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
        } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
            $sizes[ $_size ] = array(
                'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
                'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
            );
        }
    }

    return $sizes;
}

function wp_calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
    $w0 = $image_meta['width'];
    $h0 = $image_meta['height'];
    $override = array();

    foreach ($image_meta['sizes'] as $size) {
        if ($size['width'] != $w0 || $size['height'] != $h0) {
            $override[] = $size['file'];
        }
    }

    $upload_dir = wp_upload_dir();
    $upload_url = $upload_dir['baseurl'];
    $thumbs_upload_url = get_thumbs_upload_url($upload_dir);

    foreach ($sources as $k => $info) {
        $url = $info['url'];    
        $filename = pathinfo($url, PATHINFO_BASENAME);
        if (!in_array($filename, $override)) continue;
        $sources[$k]['url'] = str_replace($upload_url, $thumbs_upload_url, $url);
    }

    return $sources;
}
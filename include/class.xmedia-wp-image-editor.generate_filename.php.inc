<?php

    if ( ! $suffix )
      $suffix = $this->get_suffix();

    $info = pathinfo( $this->file );
    $dir  = $info['dirname'];
    $ext  = $info['extension'];

    $name = wp_basename( $this->file, ".$ext" );
    $new_ext = strtolower( $extension ? $extension : $ext );

    if ( ! is_null( $dest_path ) && $_dest_path = realpath( $dest_path ) )
      $dir = $_dest_path;

    $filename = trailingslashit( $dir ) . "{$name}-{$suffix}.{$new_ext}";
    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['basedir'];

    if (strpos($filename, $upload_dir) !== false) {
      $filename = str_replace($upload_dir, $upload_dir . '/_cache', $filename);
      $dir = dirname($filename);

      if (!file_exists($dir)) {
        wp_mkdir_p($dir);
      }
    }

    return $filename;


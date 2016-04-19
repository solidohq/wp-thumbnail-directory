<?php
class XMedia_WP_Image_Editor_GD extends WP_Image_Editor_GD {
  public function generate_filename( $suffix = null, $dest_path = null, $extension = null ) {
    include __DIR__ . '/class.xmedia-wp-image-editor.generate_filename.php.inc';
    return $filename;
  }
}
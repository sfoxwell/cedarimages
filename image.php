<?php

require_once( 'config.php' );
require_once( 'cedarimages.php' );

$target_path    = $_GET['i'];
$target_width   = $_GET['x'];
$target_height   = isset( $_GET['y'] ) ? $_GET['y'] : 500000;

$cache_path = 'cache/';

$CedarImages = new CedarImages(array(
  'target_path'     => $target_path,
  'target_width'    => $target_width,
  'target_height'   => $target_height,
  'cache_dir'       => $CONFIG['cache_dir'],
  'relative_offset' => $CONFIG['rel_offset']
));

$image_data = $CedarImages->create_image();

header( $image_data['header'] );
if ( $image_data['mime_type'] == 'image/jpeg' ) {
  imagejpeg( $image_data['image'] );
}
elseif ( $image_data['mime_type'] == 'image/png' ) {
  imagepng( $image_data['image'] );
}

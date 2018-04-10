<?php

/**
 * Cedar Images
 *
 * @package    Cedar Images
 * @author     Stephan Smith
 * @copyright  (c) 2017 Studio Cedar
 */

class CedarImages {

  public $target_path;
  public $target_width;
  public $target_height;

  public $target_path_relative;

  public $relative_offset = '../';

  public $cache_dir = 'cache/';
  public $cache_path;
  public $cache_path_dir;

  public $target_filename;
  public $target_path_dir;

  public $target_is_png = false;
  public $target_is_jpeg = false;

  public $target_mime_type;

  function __construct( $options ) {

    $this->target_path    = $options['target_path'];
    $this->target_width   = (int) $options['target_width'];
    $this->target_height  = (int) $options['target_height'];

    $this->target_path_relative = $this->relative_offset . $this->target_path;

    $this->cache_dir = $options['cache_dir'] ? $options['cache_dir'] : $this->cache_dir;
    $this->relative_offset = $options['relative_offset'] ? $options['relative_offset'] : $this->relative_offset;

    $this->target_filename  = $this->get_filename( $this->target_path );

    $this->target_path_dir  = $this->get_path_dir();

    $this->target_is_jpeg = $this->is_jpeg( $this->target_filename );
    $this->target_is_png  = $this->is_png( $this->target_filename );

    $this->cache_path_dir = $this->get_cache_path();
    $this->cache_path     = $this->cache_path_dir . $this->target_filename;

    $this->target_mime_type   = $this->get_mime_type();

  }

  function contain( $image, $width, $height ) {
    $owidth = imagesx($image);
    $oheight = imagesy($image);

  	$hratio = $box_h / $oheight;
  	$wratio = $box_w / $owidth;

  	// check that the new width and height aren't bigger than the original values.
  	// the new values are higher than the original, don't resize or we'll lose quality
  	if ( ( $owidth <= $width) && ($oheight <= $height)) {
  		$sx = $owidth;
  	 	$sy = $oheight;
  	} else if (($wratio * $oheight) < $height) {
  		$sx = $width;
  		$sy = ceil( ( $oheight / $owidth ) * $sx);
  	} else {
  		$sy = $height;
  		$sx = ceil( ( $owidth / $oheight ) * $sy);
  	}

    //create the image, of the required size
    $new = imagecreatetruecolor($sx, $sy);
    if($new === false) {
        //creation failed -- probably not enough memory
        return null;
    }



    //Copy the image data, and resample
    //
    //If you want a fast and ugly thumbnail,
    //replace imagecopyresampled with imagecopyresized
    if(!imagecopyresampled($new, $image,
        $m_x, $m_y, //dest x, y (margins)
        0, 0, //src x, y (0,0 means top left)
        $sx, $sy,//dest w, h (resample to this size (computed above)
        imagesx($image), imagesy($image)) //src w, h (the full size of the original)
    ) {
        //copy failed
        imagedestroy($new);
        return null;
    }
    //copy successful
    return $new;
  }

  function fit( $image, $width, $height ) {

  }

  function get_filename( $str ) {
    $array = explode( '/', $str );
    return $array[ count( $array ) - 1 ];
  }

  function get_path_dir() {
    return preg_replace( '/' . $this->target_filename . '/', '', $this->target_path );
  }

  function is_jpeg( $filename ) {
    if ( preg_match( '/.jpg$/i', $filename ) || preg_match( '/.jpeg$/i', $filename ) ) {
      return true;
    }
    return false;
  }

  function is_png( $filename ) {
    if ( preg_match( '/.png$/i', $filename ) ) {
      return true;
    }
    return false;
  }

  function get_mime_type() {
    if ( $this->target_is_jpeg ) {
      return 'image/jpeg';
    }
    else if ( $this->target_is_png ) {
      return 'image/png';
    }
    return false;
  }

  function put_file($data ) {
    if ( !is_dir( $this->cache_path_dir ) ) {
      mkdir( $this->cache_path_dir, 0755, true );
    }
    if ( $this->target_is_jpeg ) {
      if ( imagejpeg( $data, $this->cache_path ) ) {
        return true;
      }
    }
    else if ( $this->target_is_png ){
      if ( imagepng( $data, $this->cache_path ) ) {
        return true;
      }
    }
    return false;
  }

  function get_cache_path() {
    $path = $this->cache_dir . $this->target_width. '/';
    if ( $this->target_height < 500000 ) {
      $path .= $this->target_height . '/';
    }
    $path .= $this->target_path_dir;
    return $path;
  }

  function create_image() {

      $return_header  = NULL;
      $return_image   = NULL;

    	if ( $this->target_is_jpeg ) {
    		$i = imagecreatefromjpeg( $this->target_path_relative );
    	}
    	else if ( $this->target_is_png ) {
    		$i = imagecreatefrompng( $this->target_path_relative );
    	}

    	$thumb = $this->contain( $i, $this->target_width, $this->target_height );
    	imagedestroy($i);

    	if ( is_null($thumb) ) {
        return array(
          'header'    => 'HTTP/1.1 500 Internal Server Error',
          'image'     => false,
          'mime_type' => NULL
        );
    	}
      else {
        $this->put_file( $thumb );

        return array(
          'header'    => 'Content-Type: ' . $this->target_mime_type,
          'image'     => $thumb,
          'mime_type' => $this->target_mime_type
        );
      }
  }
}

<?php
/**
 * WordPress image editors with one more method, grayscale(), for
 * library/gallery-bw.php. Loaded only while an image is being saved in black
 * and white, and offered first through `wp_image_editors` for that one call,
 * so every other image operation keeps the site's own editors (EWWW's).
 *
 * The weights are Rec. 709 luma (0.2126, 0.7152, 0.0722), the same as CSS
 * `filter: grayscale(1)`, so the saved file matches the View ▾ preview.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

if ( ! class_exists( 'Tiagsspace_Image_Editor_Imagick', false ) ) {
	class Tiagsspace_Image_Editor_Imagick extends WP_Image_Editor_Imagick {
		/** @return true|WP_Error */
		public function grayscale() {
			try {
				if ( method_exists( $this->image, 'colorMatrixImage' ) ) {
					$r = 0.2126;
					$g = 0.7152;
					$b = 0.0722;
					// 5x5: three identical luma rows, alpha and offset untouched.
					$this->image->colorMatrixImage( array(
						$r, $g, $b, 0, 0,
						$r, $g, $b, 0, 0,
						$r, $g, $b, 0, 0,
						0, 0, 0, 1, 0,
						0, 0, 0, 0, 1,
					) );
				} else {
					$this->image->modulateImage( 100, 0, 100 );
				}
				// Stay a three-channel RGB file (ImageMagick would write grey
				// pixels as a one-channel JPEG): the sRGB profile still fits,
				// and every service that takes the file gets plain RGB.
				$this->image->setImageType( Imagick::IMGTYPE_TRUECOLOR );
				if ( method_exists( $this->image, 'setType' ) ) {
					$this->image->setType( Imagick::IMGTYPE_TRUECOLOR ); // what the JPEG writer reads
				}
				$this->image->setOption( 'type', 'TrueColor' );
			} catch ( Exception $e ) {
				return new WP_Error( 'image_grayscale_error', $e->getMessage() );
			}
			return true;
		}
	}
}

if ( ! class_exists( 'Tiagsspace_Image_Editor_GD', false ) ) {
	class Tiagsspace_Image_Editor_GD extends WP_Image_Editor_GD {
		/** @return true|WP_Error */
		public function grayscale() {
			// GD's own filter (0.299 / 0.587 / 0.114): GD is only the fallback.
			if ( ! imagefilter( $this->image, IMG_FILTER_GRAYSCALE ) ) {
				return new WP_Error( 'image_grayscale_error', __( 'Could not convert the image to black and white.', 'tiagsspace' ) );
			}
			return true;
		}
	}
}

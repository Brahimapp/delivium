<?php
/**
 * Marker icon generator for the driver app
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/driver/includes
 */

class Delivium_Marker_Generator {

    /**
     * Generate PNG markers from SVG files
     *
     * @since    1.0.0
     */
    public static function generate_markers() {
        $svg_dir = plugin_dir_path(dirname(__FILE__)) . 'images/';
        
        // Convert delivery marker
        $delivery_svg = file_get_contents($svg_dir . 'delivery-marker.svg');
        if ($delivery_svg) {
            $im = new Imagick();
            $im->readImageBlob($delivery_svg);
            $im->setImageFormat('png32');
            file_put_contents($svg_dir . 'delivery-marker.png', $im->getImageBlob());
            $im->clear();
        }

        // Convert driver marker
        $driver_svg = file_get_contents($svg_dir . 'driver-marker.svg');
        if ($driver_svg) {
            $im = new Imagick();
            $im->readImageBlob($driver_svg);
            $im->setImageFormat('png32');
            file_put_contents($svg_dir . 'driver-marker.png', $im->getImageBlob());
            $im->clear();
        }
    }

    /**
     * Check if marker PNGs exist, generate if not
     *
     * @since    1.0.0
     */
    public static function ensure_markers_exist() {
        $images_dir = plugin_dir_path(dirname(__FILE__)) . 'images/';
        
        if (!file_exists($images_dir . 'delivery-marker.png') || 
            !file_exists($images_dir . 'driver-marker.png')) {
            self::generate_markers();
        }
    }
} 
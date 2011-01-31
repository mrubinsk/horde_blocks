<?php
/**
 * Custom Ansel Horde_Block for getting a JSON representation
 * of the requested gallery - including the images' size attributes
 * since we don't want to have to load the images to get size information
 * with every _ansel_getImages() api call.
 *
 * @author  Michael J. Rubinsky <mike@horde.org>
 * @package custom
 */

$block_name = _("Images JSON");

/**
 */
class Horde_Block_ansel_image_info extends Horde_Block {

    var $_app = 'ansel';
    var $_gallery = null;

    function _params()
    {

    }

    function _title()
    {
        return _("Images JSON");
    }

    function _content()
    {
        return $this->json();
    }


    /**
     * JSON representation of the gallery.
     *
     * This code adapted from the Ansel_View_Gallery class.
     * Changes made to fit in the structure of Horde_Block and
     * to return a hash instead of a flat array.
     */
    function json()
    {
        require_once 'Horde/Text/Filter.php';
        $gallery = &$this->_getGallery();
        if (!is_a($gallery, 'PEAR_Error')) {
            $json = array();
            $images = $gallery->getImages();
            foreach ($images as $image) {
                $dimensions = $image->getDimensions('screen');
                if (is_a($dimensions, 'PEAR_Error')) {
                    $dimensions = array('height' => $GLOBALS['conf']['screen']['height'],
                                        'width' => $GLOBALS['conf']['screen']['width']);
                }

                $json[] = array(Ansel::getImageUrl($image->id, 'screen'),
                                $dimensions['width'],
                                $dimensions['height'],
                                htmlspecialchars($image->filename),
                                Text_Filter::filter($image->caption, 'text2html', array('parselevel' => TEXT_HTML_MICRO)),
                                $image->id);
            }

            require_once 'Horde/Serialize.php';
            return Horde_Serialize::serialize($json, SERIALIZE_JSON, NLS::getCharset());
        }
    }

    function &_getGallery()
    {
        @define('ANSEL_BASE', dirname(__FILE__) . '/../..');
        require ANSEL_BASE . '/lib/base.php';

        // Make sure we haven't already selected a gallery.
        if (is_a($this->_gallery, 'DataTreeObject_Gallery')) {
            return $this->_gallery;
        }

        // Get the gallery object and cache it.
        if (isset($this->_params['gallery']) && $this->_params['gallery'] != '__random') {
            $this->_gallery = &$GLOBALS['ansel_shares']->getGallery($this->_params['gallery']);
        } else {
            $this->_gallery = &Ansel::getRandomGallery();
        }

        if (empty($this->_gallery)) {
            return PEAR::raiseError(_("Gallery does not exist."));
        } elseif (is_a($this->_gallery, 'PEAR_Error') ||
                  !$this->_gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {
            return PEAR::raiseError(_("You do not have permission to view this gallery."));
        }

        // Return a reference to the gallery.
        return $this->_gallery;
    }

}

<?php
/**
 * $Date: 2009/11/05 16:32:52 $
 */
$block_name = _("DSPAM Stats");

/**
 * $Horde: $
 *
 * @package Horde_Block
 */
class Horde_Block_Horde_dspam extends Horde_Block {

    /**
     * Whether this block has changing content.
     */
    var $updateable = true;

    /**
     * @var string
     */
    var $_app = 'horde';

    function _params()
    {
        return array('time' => array('type' => 'enum',
                                     'name' => _("Time format"),
                                     'default' => '24-hour',
                                     'values' => array('24-hour' => _("24 Hour Format"),
                                                       '12-hour' => _("12 Hour Format"))));
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("My DSPAM Stats");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content.
     */
    function _content()
    {
        $results = `dspam_stats -h mike`;
        return '<pre>' . $results . '</pre>';
    }

}

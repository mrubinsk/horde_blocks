<?php

/* If you prefer that only admins be able to see this block, un-comment the
 *  if statement and closing brace.
 */
if (Auth::isAdmin()) {
    require_once 'Horde/DOM.php';
    $block_name = _("System Info");
}
/**
 * Horde_Block to display system information obtained from a machine running
 * phpSysInfo.  More information on the phpSysInfo project can be found
 * at http://phpsysinfo.sourceforge.net/.
 *
 * $Id: phpSysInfo.php,v 1.6 2008/01/13 05:24:47 mrubinsk Exp $
 *
 * Copyright 2006 Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

class Horde_Block_Horde_phpSysInfo extends Horde_Block {

    /**
     * Flag this block as updateable.
     */
    var $updateable = true;

    /**
     * Application the block belongs to.
     */
    var $_app = 'horde';

    /**
     * For handling and reporting errors to the block content.
     */
    var $_isError = false;
    var $_errorText;

    /**
     * Cache the XML so we don't hit the server for each _fetchXML()
     */
    //var $_xml_cache;

    /**
     * Cache the host information as well, since we retrieve it for
     * the title of the block as well.
     */
    var $_host_cache;

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        $host = $this->_getGeneralInfo();
        if (!is_a($host, 'PEAR_Error') && !empty($host['hostname']) && !empty($this->_params['host'])) {
            return '<a href="' . $this->_params['host'] . '" target="_blank">System Info For ' . $host['hostname'] . '</a>';
        } elseif (is_a($host, 'PEAR_Error'))  {
            return $host->getMessage();
        } else {
            return _("System Information");
        }
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        global $registry;

        // Grab all the info.
        $host = $this->_getGeneralInfo();
        if (is_a($host, 'PEAR_Error')) {
            return $host->getMessage();
        }
        if (!empty($this->_params['memory'])) {
            $meminfo = $this->_getMemoryInfo();
            if (is_a($meminfo, 'PEAR_Error')) {
                return $meminfo->getMessage();
            }
        }

        if (!empty($this->_params['filesystem'])) {
            $fileinfo = $this->_getFilesystemInfo();
            if (is_a($fileinfo, 'PEAR_Error')) {
                return $fileinfo->getMessage();
            }

        }
        if (!empty($this->_params['hddtemp'])) {
            $hddtemp = $this->_getHDDTemp();
            if (is_a($hddtemp, 'PEAR_Error')) {
                return $hddtemp->getMessage();
            }

        }

        // Start building the HTML string.
        if ($this->_isError) {
            $html = 'There was an error displaying this block.<br />';
            $html .= $this->_errorText;
        } else {
            $html = '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
            // Set up the bar graphics
            $url = Horde::url($registry->get('webroot', 'horde') . '/services/images/pixel.php');
            $right = Util::addParameter($url, 'c', '#ffffff');

            // Build the Host Info section
            $html .= '<th colspan="3" class="control">' . $host['ip'] . ' As of: ' . date('r') . '</th>';
            $uptime = $host['uptime'];
            $html .= '<tr><td>Uptime</td><td colspan="2">' . $uptime . '</td></tr>';
            $html .= '<tr><td>Load Averages</td><td>' . $host['load_average'] . '</td></tr>';

            // CPU load bar
            if (isset($host['cpu_load'])) {
                $color = $host['cpu_load'] > 80 ? '#ff0000' : '#00ff00';
                $left = Util::addParameter($url, 'c', $color);
                $html .= '<tr><td>CPU Load</td><td colspan="2">';
                $html .= Horde::img($left, '', 'style="margin-right: 5px;" width="' . $host['cpu_load'] . '" height="10"', '');
                $html .= $host['cpu_load'] . '%</td>';
                $html .= '<tr><td>User Count</td><td colspan="2">' . $host['user_count'] . '</td></tr>';
                $html .= '</tr>';
            }

            // Build the Memory section.
            if (isset($meminfo['percent'])) {
                $color = $meminfo['percent'] > 90 ? '#ff0000' : '#00ff00';
                $left = Util::addParameter($url, 'c', $color);
                $right = Util::addParameter($url, 'c', '#ffffff');
                $html .= '<th colspan="3" class="control">Memory Usage</th>';
                $html .= '<tr class="linedRow"><td>Type</td><td>Percent Capacity</td><td>Free</td></tr>';

                // Physical
                $html .= '<tr><td>Physical: ' . $meminfo['percent'] . '%</td><td>';
                if ($this->_params['fullbars']) {
                    $html .= Horde::img($left, '', 'style="border-left: 1px solid black; border-top: 1px solid black; border-bottom: 1px solid black;" width="' . $meminfo['percent'] . '" height="10"', '');
                    $html .= Horde::img($right, '', 'style="border-top: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;" width="' . (100 - $meminfo['percent']) . '" height="10"', '');
                } else {
                    $html .= Horde::img($left, '', 'style="margin-right: 5px;" width="' . $meminfo['percent'] . '" height="10"', '');
                }
                $html .= ' ' . $meminfo['percent'] . '%</td>';
                $html .= '<td>' .(int)($meminfo['free']/1024) . ' MB</td></tr>';

                // Swap
                $color = $meminfo['swap_percent'] > 90 ? '#ff0000' : '#00ff00';
                $left = Util::addParameter($url, 'c', $color);
                $html .= '<tr><td>Disk Swap: ' . $meminfo['swap_percent'] . '%</td><td>';
                if ($this->_params['fullbars']) {
                    $html .= Horde::img($left, '', 'style="border-left: 1px solid black; border-top: 1px solid black; border-bottom: 1px solid black;" width="' . $meminfo['swap_percent'] . '" height="10"', '');
                    $html .= Horde::img($right, '', 'style="border-top: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;" width="' . (100 - $meminfo['swap_percent']) . '" height="10"', '');
                } else {
                    $html .= Horde::img($left, '', 'style="margin-right: 5px;" width="' . $meminfo['swap_percent'] . '" height="10"', '');
                }
                $html .= ' ' . $meminfo['swap_percent'] . '%</td>';
                $html .= '<td>' . (int)($meminfo['swap_free']/1024) . ' MB</td></tr>';
            }

            // Filesystems
            if (isset($fileinfo[0])) {
                $html .= '<th colspan="3" class="control">Filesystems</th>';
                $html .= '<tr class="linedRow"><td>Mountpoint</td><td>Percent Capacity</td><td>Free</td></tr>';
                foreach ($fileinfo as $mount) {
                    // phpSystemInfo for some reason returns percent values for
                    // filesystems with the trailing '%' sign.
                    $html .= '<tr>';
                    $mount['percent'] = (int)$mount['percent'];
                    $color = $mount['percent'] > 90 ? '#ff0000' : '#00ff00';
                    $left = Util::addParameter($url, 'c', $color);
                    $html .= '<td>' . $mount['mount_point'] . '</td><td>';
                    if ($this->_params['fullbars']) {
                        $html .= Horde::img($left, '', 'style="border-left: 1px solid black; border-top: 1px solid black; border-bottom: 1px solid black;" width="' . $mount['percent'] . '" height="10"', '');
                        $html .= Horde::img($right, '', 'style="border-top: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;" width="' . (100 - $mount['percent']) . '" height="10"', '');
                    } else {
                        $html .= Horde::img($left, '', 'style="margin-right: 3px;" width="' . $mount['percent'] . '" height="10"', '');
                    }
                    $html .= ' ' . $mount['percent'] . '%</td>';
                    $html .= '<td>' . (int)($mount['free'] / 1024) . ' MB</td></tr>';
                }
            }

            // HDDTemps
            if(!empty($hddtemp[0])) {
                $html .= '<th colspan="3" class="control">HDD Temps</th>';
                $html .= '<tr class="linedRow"><td>Label</td><td>Temp (C)</td><td>Model</td></tr>';
                $left = Util::addParameter($url, 'c', '#00ff00');
                foreach ($hddtemp as $drive) {
                    $percent = (int)(($drive['value'][0]/60) * 100);
                    $color = $percent > 90 ? '#ff0000' : '#00ffoo';
                    $left = Util::addParameter($url, 'c', '#00ff00');
                    $html .= '<tr><td>' . $drive['label'][0] . '</td><td>';
                    if ($this->_params['fullbars']) {
                        $html .= Horde::img($left, '', 'style="border-left: 1px solid black; border-top: 1px solid black; border-bottom: 1px solid black;" width="' . $percent . '" height="10"', '');
                        $html .= Horde::img($right, '', 'style="border-top: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black;" width="' . (100 - $percent) . '" height="10"', '');
                    } else {
                        $html .= Horde::img($left, '', 'style="margin-right: 3px;" width="' . $percent . '" height="10"', '');
                    }
                    $html .= ' ' . $drive['value'][0] . '</td><td>' . $drive['model'][0] . '</td></tr>';
                }
            }
            $html .= '</table>';
        }
        return $html;
    }


    function _params()
    {
        $params = array(
            'host' => array('type' => 'text',
                            'name' => _("Host"),
                            'default' => 'http://localhost/phpsysinfo/index.php'
                      ),
            'filesystem' => array('type' => 'boolean',
                                  'name' => _("Show filesystem information?"),
                                  'default' => 1
                            ),
            'memory' => array('type' => 'boolean',
                              'name' => _("Show memory usage?"),
                              'default' => 1
                              ),
            'hddtemp' => array('type' => 'boolean',
                                'name' => _("Show HDD Temps?"),
                                'default' => 1
                          ),
            'fullbars' => array('type' => 'boolean',
                                'name' => _("Show full bar length?"),
                                'default' => 1
                          )
        );
        return $params;
    }

    /**
     * Retrieve memory related information.
     *
     * @return mixed Array containing results or PEAR_Error
     */
    function _getMemoryInfo()
    {
        $results = $this->_getNode('Memory');
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $swap_results = $this->_getNode('Swap');
        if (is_a($swap_results, 'PEAR_Error')) {
            return $swap_results;
        }

        $return = array(
            'free' => isset($results['Free']) ? $results['Free'][0] : '',
            'used' => isset($results['Used']) ? $results['Used'][0] : '',
            'total' => isset($results['Total']) ? $results['Total'][0] : '',
            'percent' => isset($results['Percent']) ? $results['Percent'][0] : '',
            'application' => isset($results['App']) ? $results['App'][0] : '',
            'buffers' => isset($results['Buffers']) ? $results['Buffers'][0] : '',
            'cached' => isset($results['Cached']) ? $results['Cached'][0] : '',
            'swap_free' => isset($swap_results['Free']) ? $swap_results['Free'][0] : '',
            'swap_used' => isset($swap_results['Used']) ? $swap_results['Used'][0] : '',
            'swap_total' => isset($swap_results['Total']) ? $swap_results['Total'][0] : '',
            'swap_percent' => isset($swap_results['Percent']) ? $swap_results['Percent'][0] : '',
        );
        return $return;
    }

    /**
     * Retrieve file system related information.
     *
     * @return Array containing information.
     */
    function _getFileSystemInfo()
    {
        $return = array();
        $results = $this->_getNode('FileSystem');
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $results = $results['Mount'];
        $cnt = count($results);
        for ($i = 0; $i < $cnt; $i++) {
            $return[] = array(
                'mount_point' => $results[$i]['MountPoint'][0],
                'fs_type' => $results[$i]['Type'][0],
                'device' => $results[$i]['Device'][0]['Name'][0],
                'free' => $results[$i]['Free'][0],
                'used' => $results[$i]['Used'][0],
                'percent' => $results[$i]['Percent'][0],
                'options' => isset($results[$i]['Options']) ? $results[$i]['Options'][0] : '',
            );
        }
        return $return;
    }

    /**
     * Retrieve drive temps if available.
     *
     * @return Array containing drive temperature information
     */
    function _getHDDTemp()
    {
        $results = $this->_getNode('HDDTemp');
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $temp = array();
        if (count($results)) {
           $results = $results['Item'];
           foreach ($results as $drive) {
               $temp[] = array(
                    'label' => $drive['Label'],
                    'value' => $drive['Value'],
                    'model' => $drive['Model'],
               );
           }
        }
        return $temp;
    }

    /**
     * Retrieve general host information.
     *
     * @return Array containing host info.
     */
    function _getGeneralInfo()
    {
        if (!empty($this->_host_cache)) {
            return $this->_host_cache;
        }

        $return = array();
        $results = $this->_getNode('Vitals');
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $uptime = isset($results['Uptime']) ? $results['Uptime'][0] : 0;

        $min = $uptime / 60;
        $hours = $min / 60;
        $day = floor($hours / 24);
        $hours = floor($hours - ($day * 24));
        $min = floor($min - ($day * 60 * 24) - ($hours * 60));

        $return = array(
            'hostname' => isset($results['Hostname']) ? $results['Hostname'][0] : '',
            'ip' => isset($results['IPAddr']) ? $results['IPAddr'][0] : '',
            'kernel_version' => isset($results['Kernel']) ? $results['Kernel'][0] : '',
            'distro' => isset($results['Distro']) ? $results['Distro'][0] : '',
            'uptime' => $day . ' Day' . ($day > 1 ? 's ' : ' ')  . $hours . ' Hour' . ($hours > 1 ? 's ' : ' ') . $min . ' Minute' . ($min > 1 ? 's' : ''),
            'user_count' => isset($results['Users']) ? $results['Users'][0] : '',
            'load_average' => isset($results['LoadAvg']) ? $results['LoadAvg'][0] : '',
        );

        // The phpSysInfo install might be configured not to return this.
        // If it's not there, don't pretend it was so the bar graphic doesn't
        // display.
        if (isset($results['CPULoad'])) {
            $return['cpu_load'] = $results['CPULoad'][0];
        }

        $this->_host_cache = $return;
        return $return;
    }

    /**
     * Retrieve the requested node from the XML tree.
     *
     * @param string $tag_name  The tag_name of the desired node.
     *
     * @return mixed  Array containing information or PEAR_Error.
     */
    function _getNode($tag_name)
    {
        $xml = $this->_fetchXML();
        if (is_a($xml, 'PEAR_Error')) {
            return $xml;
        }

        $tree = Horde_Dom_Document::factory(array('xml' => $xml));
        $nodes = $tree->get_elements_by_tagname($tag_name);

        // This is the parent node of the info we are interested in.
        if (isset($nodes[0])) {
            $nodes = $nodes[0]->child_nodes();
            $results = $this->_build($nodes);
            return $results;
        }
    }

    /**
     * Drill down the XML tree and build the array.
     *
     * @param DOMNode $nodes  The node to traverse.
     *
     * @return Array containing the data.
     */
    function _build($nodes)
    {
        for ($i = 0; $i < count($nodes); $i++) {
                $node = $nodes[$i];
                if ($node->has_child_nodes()) {
                   $res[$node->node_name()][] =  $this->_build($node->child_nodes());
                } else {
                    if (strlen(trim($node->get_content()))) {
                       return $node->get_content();
                    }
                }
        }
        return $res;
    }

    /**
     * Fetch the XML from the host and cache it if desired.
     *
     * @return mixed  The XML string from the phpSysInfo host of Pear_Error.
     */
    function _fetchXML()
    {
       if (!isset($this->_params['host'])) {
           return PEAR::raiseError('Missing host parameter for phpSysInfo.');
       }

       //if (!empty($this->_xml_cache)) {
       //     return $this->_xml_cache;
        //} else {
            require_once 'HTTP/Request.php';
            $req = &new HTTP_Request($this->_params['host']);
            $req->setMethod(HTTP_REQUEST_METHOD_POST);
            $req->addPostData('template', 'xml');
            $result = $req->sendRequest();
            if (is_a($result, 'PEAR_Error')) {
               return $result;
            }

            // Good response code?
            $responseCode = $req->getResponseCode();
            if ($responseCode != 200) {
                return PEAR::raiseError(_("The requested host could not be reached."));
            }
            $xml = $req->getResponseBody();
            // When an error occurs in the phpSysInfo script, it may or
            // may not return an XML string depending on the version and error.
            if (!strpos($xml, '<phpsysinfo>')) {
                $this->_isError = true;
                $this->_errorText = $xml;
                return PEAR::raiseError($xml);
            }
            //$this->_xml_cache = $xml;
            return $xml;
       // }
    }
}

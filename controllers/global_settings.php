<?php

/**
* Remote Boot global settings controller
*
* @category   apps
* @package    remote_boot
* @subpackage controllers
* @author     PoliLinux <clearos@polilinux.com.br>
* @author     Felipe Stall Rechia <felipe.rechia@polilinux.com.br>
* @copyright  2016 PoliLinux Soluções e Serviços TI Ltda
* @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
*/

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\network\Network as Network;
use \clearos\apps\remote_boot\Remote_Boot as Remote_Boot;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
* Network settings controller.
*
* @category   apps
* @package    network
* @subpackage controllers
* @author     ClearFoundation <developer@clearfoundation.com>
* @copyright  2011 ClearFoundation
* @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
* @link       http://www.clearfoundation.com/docs/developer/apps/network/
*/

class Global_settings extends ClearOS_Controller
{
    /**
    * global settings controller constructor
    *
    * @return void
    */
    public function __construct() {
        clearos_profile(__METHOD__, __LINE__);

        $this->lang->load('remote_boot');
        $this->load->library('session');
        $this->load->library('remote_boot/Remote_Boot');
    }

    /**
    * global settings index.
    *
    * @return void
    */

    public function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_view_edit('view');
    }

    /**
    * global settings edit view.
    *
    * @return void
    */

    public function edit()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_view_edit('edit');
    }

    /**
    * global settings view/edit form
    *
    * @param string $form_type ('view'|'edit')
    *
    * @return view
    */

    protected function _view_edit($form_type)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->remote_boot->internal_application_checks();
        $rbmessages = Remote_Boot::$internal_msgs->get_msg_queue();
        $this->session->set_userdata('rb_internal_msgs',$rbmessages);


        // Set validation rules
        //---------------------
        $this->load->library('form_validation');
        $this->form_validation->set_policy('global[tftpserver][automanage]', 'remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('global[tftpserver][ip]', 'remote_boot/Remote_Boot', 'validate_ip', TRUE);
        $this->form_validation->set_policy('global[folders][tftp][name]', 'remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('global[tftpserver][cfgfile]', 'remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('global[nfsserver][automanage]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('global[nfsserver][ip]', 'remote_boot/Remote_Boot', 'validate_ip', TRUE);
        $this->form_validation->set_policy('global[folders][nfs][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('global[folders][pxelinux][name]', 'remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok)) {
            try {
                // Update
                $form_data = $this->input->post();

                $this->remote_boot->edit_global_settings( $form_data );
                $rbmessages = Remote_Boot::$user_msgs->get_msg_queue();
                $this->session->set_userdata('rb_profile_msgs',$rbmessages);

                // Redirect to main page
                $this->page->set_status_updated();
                redirect('/remote_boot/');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }
        // Load view data
        //---------------
        try {
            $data = Remote_Boot::$rb_cfg->get_global_cfg();
            $data['form_type'] = $form_type;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
        $this->page->view_form('remote_boot/global_settings', $data, lang('text_global_settings'));
    }
}

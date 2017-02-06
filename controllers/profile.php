<?php
/**
* Remote Boot profile settings controller.
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
use \clearos\apps\remote_boot\Remote_Boot as Remote_Boot;

////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////


class Profile extends ClearOS_Controller
{

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * profile settings controller constructor
    *
    * loads some libraries and language files
    *
    * @return void
    */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->lang->load('remote_boot');
        $this->lang->load('network');
        $this->load->library('remote_boot/Remote_Boot');
        $this->load->library('session');
    }

    /**
    * profile settings controller index
    *
    * calls a summary view listing all configured profiles
    *
    * @return void
    */

    public function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_summary();
    }

    /**
    * profile settings controller view method
    *
    * @return void
    */

    public function view($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_edit_view('view',$profile);
    }

    /**
    * profile settings controller edit method
    *
    * @return void
    */

    public function edit($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_edit_view('edit',$profile);
    }

    /**
    * profile settings controller add method
    *
    * @return void
    */

    public function add($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_edit_view('add',$profile);
    }

    /**
    * profile settings controller enable method
    *
    * @return void
    */

    public function enable($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_update('enable',$profile);
    }

    /**
    * profile settings controller disable method
    *
    * @return void
    */

    public function disable($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_update('disable',$profile);
    }

    /**
    * profile settings ajax method gen_form_info
    *
    * based on form data already entered by the user, calls an Config function
    * that generates the configuration for the user and updates the form dynamically
    * through JavaScript
    *
    * @return JSON
    */

    public function gen_form_info( $name = NULL, $nfsip = NULL, $tftpip = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cfg = Remote_Boot::$rb_cfg->generate_template_cfg_based_on_global_settings($name, $nfsip, $tftpip);
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        echo json_encode( $cfg );
    }

    /**
    * profile settings controller delete method
    *
    * takes the user to a delete confirmation page. If deletion is confirmed,
    * user is taken to destroy method.
    *
    * @return view
    */

    public function delete($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $confirm_uri = '/app/remote_boot/profile/destroy/' . $profile;
        $cancel_uri = '/app/remote_boot';
        $data = Remote_Boot::$rb_cfg->get_profile_cfg($profile);
        $items = Array(
            lang('field_profile_name').": ".$data['name'].'<br/>'.
            lang('field_profile_network_cidr').": ".$data['network_cidr'].'<br/>'
        );

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
    * profile settings controller destroy method
    *
    * removes given profile and redirects the user back to main remote_boot page
    *
    * @return view
    */

    public function destroy($profile = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->remote_boot->remove_profile( (int) $profile );
        $rbmessages = Remote_Boot::$user_msgs->get_msg_queue();
        $this->session->set_userdata('rb_profile_msgs',$rbmessages);

        $this->page->set_status_deleted();
        redirect('/remote_boot/');
    }

    /**
    * profile settings controller _summary
    *
    * grabs all configuration of all profiles and feeds that data to profile/summary view
    *
    * @return view
    */

    protected function _summary()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load view data
        //---------------
        try {
            // get profile list with ID as index
            $data['profile_list'] = Remote_Boot::$rb_cfg->get_all_profiles_cfg();
            $data['form_type'] = 'view';
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $this->page->view_form('remote_boot/profile/summary', $data, lang('remote_boot_app_name'));
    }

    /**
    * profile settings controller _add_edit_view
    *
    * validates form data, handles form submission, as well as addition, and edition of profiles.
    *
    * redirects user to main remote_boot
    *
    * @return view
    */

    protected function _add_edit_view( $form_type, $profile )
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set validation rules
        //---------------------
        $this->load->library('form_validation');
        $this->form_validation->set_policy('profile[enabled]', 'remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[name]', 'remote_boot/Remote_Boot', 'validate_profile_name', TRUE);
        $this->form_validation->set_policy('profile[network_cidr]','remote_boot/Remote_Boot', 'validate_ip_network_with_prefix', TRUE);
        $this->form_validation->set_policy('profile[nfs][ip]','remote_boot/Remote_Boot', 'validate_ip', TRUE);
        $this->form_validation->set_policy('profile[folders][rootfs][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[folders][home][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[folders][etc][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[folders][var][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[folders][root][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[folders][rootfs][export][enabled]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[folders][home][export][enabled]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[folders][etc][export][enabled]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[folders][var][export][enabled]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[folders][root][export][enabled]','remote_boot/Remote_Boot', 'validate_cfg_boolean', TRUE);
        $this->form_validation->set_policy('profile[folders][kernel][name]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[pxelinux][cfgfile]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[pxelinux][initrd]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[pxelinux][kernel]','remote_boot/Remote_Boot', 'validate_absolute_path_or_file', TRUE);
        $this->form_validation->set_policy('profile[pxelinux][kparams]','remote_boot/Remote_Boot', 'validate_kparams', TRUE);
        $this->form_validation->set_policy('profile[folders][rootfs][export][options]','remote_boot/Remote_Boot', 'validate_nfs_params', TRUE);
        $this->form_validation->set_policy('profile[folders][home][export][options]','remote_boot/Remote_Boot', 'validate_nfs_params', TRUE);
        $this->form_validation->set_policy('profile[folders][etc][export][options]','remote_boot/Remote_Boot', 'validate_nfs_params', TRUE);
        $this->form_validation->set_policy('profile[folders][var][export][options]','remote_boot/Remote_Boot', 'validate_nfs_params', TRUE);
        $this->form_validation->set_policy('profile[folders][root][export][options]','remote_boot/Remote_Boot', 'validate_nfs_params', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------
        if ($this->input->post('submit') && ($form_ok)) {
            try {
                // ADD PROFILE
                $form_data = $this->input->post();
                if (strtolower($form_data['submit']) === 'add') {
                    $this->remote_boot->add_new_profile( $form_data );
                    $rbmessages = Remote_Boot::$user_msgs->get_msg_queue();
                    $this->session->set_userdata('rb_profile_msgs',$rbmessages);

                } elseif (strtolower($form_data['submit']) === 'update') {
                    $this->remote_boot->edit_profile( $form_data , (int) $profile );
                    $rbmessages = Remote_Boot::$user_msgs->get_msg_queue();
                    $this->session->set_userdata('rb_profile_msgs',$rbmessages);
                }

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
            // we load the default config profile (ID 0) as a template to add new
            // profiles
            if ($form_type == 'add') {
                $data['profile'] = Remote_Boot::$rb_cfg->generate_template_cfg_based_on_global_settings();
            } else {
                $data['profile'] = Remote_Boot::$rb_cfg->get_profile_cfg((int)$profile);
            }
            $data['form_type'] = $form_type;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $options = array();

        // view form commands must always refer to stuff within the views directory
        $this->page->view_form('remote_boot/profile/add_edit_view', $data, lang('remote_boot_app_name'),$options);
    }

    /**
    * profile settings controller _update
    *
    * handles profile enable/disable operations
    *
    * @return view
    */

    protected function _update( $operation, $profile )
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $this->remote_boot->manage_profile( $profile, $operation );
            $rbmessages = Remote_Boot::$user_msgs->get_msg_queue();
            $this->session->set_userdata('rb_profile_msgs',$rbmessages);
            $this->page->set_status_enabled();

            // Redirect to main page
            redirect('/remote_boot/');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}

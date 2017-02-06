<?php

/**
* Remote Boot application main class.
*
* @category   apps
* @package    remote_boot
* @subpackage Libraries
* @author     PoliLinux <clearos@polilinux.com.br>
* @author     Felipe Stall Rechia <felipe.rechia@polilinux.com.br>
* @author     Alexandre Calerio de Oliveira <alexandre@polilinux.com.br>
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

////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
////////////////////////////////////////////////////////////////////////

namespace clearos\apps\remote_boot;

////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\File   as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\base\Shell as Shell;
//use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\dhcp\Dnsmasq as Dnsmasq;


//clearos_load_library('base/Daemon');
clearos_load_library('dhcp/Dnsmasq');
clearos_load_library('base/Shell');
clearos_load_library('base/File');
clearos_load_library('base/Folder');

clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

clearos_load_library('remote_boot/Config');
clearos_load_library('remote_boot/Nfs_Server');
clearos_load_library('remote_boot/Pxelinux');
clearos_load_library('remote_boot/Messages');

include_once(dirname(__FILE__) . '/Constants.php');
////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////

class Remote_Boot
{
    ////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ////////////////////////////////////////////////////////////////////

    public static $rb_cfg;
    private static $rb_nfs;
    private static $rb_pxelinux;
    public static $user_msgs;
    public static $internal_msgs;
    private static $dnsmasq_restart_required = FALSE;

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Remote Boot Pxelinux constructor
    * We use static objects as properties, as it makes no sense to have several
    * instances of each.
    *
    * @return void
    */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!self::$user_msgs)
            self::$user_msgs        = new Messages();
        if (!self::$internal_msgs)
            self::$internal_msgs    = new Messages();
        if (!self::$rb_cfg)
            self::$rb_cfg           = new Config(self::$internal_msgs);
        if (!self::$rb_nfs)
            self::$rb_nfs           = new Nfs_Server(self::$internal_msgs);
        if (!self::$rb_pxelinux)
            self::$rb_pxelinux      = new Pxelinux(self::$internal_msgs);
    }

    /**
    * internal application checks are called every time the global_settings page is viewed
    * TFTP and NFS services status are logged to debug file (if enabled)
    *
    *
    * @return void
    */

    public function internal_application_checks() {
        clearos_profile(__METHOD__, __LINE__);
        self::$internal_msgs->set_queue_title(lang('msg_title_internal_app_checks'));
        // global checks
        $this->_create_global_folders_and_files();

        // dnsmasq and tftp related checks
        $globalcfg = self::$rb_cfg->get_global_cfg();
        if ($globalcfg['tftpserver']['automanage'] === '1') {
            self::$internal_msgs->log(__METHOD__,'TFTP management enabled');
            if (!$this->_check_dnsmasq_tftp_cfg()) {
                $this->_create_dnsmasq_tftp_cfg_file();
            }
        } else {
            self::$internal_msgs->log(__METHOD__,'TFTP management disabled');
            $this->_delete_dnsmasq_tftp_cfg_file();
        }
        $this->_check_and_enable_dnsmasq_service();


        // nfsd service
        if ($globalcfg['nfsserver']['automanage'] === '1') {
            self::$internal_msgs->log(__METHOD__,'NFS management enabled');
            $this->_start_and_enable_nfs_service();
        } else {
            self::$internal_msgs->log(__METHOD__,'NFS management disabled');
            $this->_stop_and_disable_nfs_service();
        }
        self::$rb_nfs->get_nfs_status();

        // QUESTION: Do firewall rules act only on external interfaces? We are supposed to use only LAN interfaces
        // firewall checks

        // pxelinux bootloader presence check
        $this->_prepare_bootloader();
    }

    /**
    * this function is used to create a dnsmasq TFTP config file based on global cfg
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _create_dnsmasq_tftp_cfg_file() {
        clearos_profile(__METHOD__, __LINE__);

        self::$internal_msgs->log(__METHOD__,'Attempting to configure TFTP');
        $globalcfg = self::$rb_cfg->get_global_cfg();
        $cfg_array[] = 'dhcp-boot='.basename($globalcfg['pxelinux']['bootloader']).','.$globalcfg['tftpserver']['ip'];
        $cfg_array[] = 'enable-tftp';
        $cfg_array[] = 'tftp-root='.$globalcfg['folders']['tftp']['name'];
        $cfg_array[] = '';
        $dnsmasq_tftp_config = implode(RB_LINE_BREAK,$cfg_array);

        try {
            $tftpcfgfile = new File($globalcfg['tftpserver']['cfgfile']);
            if (!$tftpcfgfile->exists()) {
                $tftpcfgfile->create(RB_DEF_SYSUSER,RB_DEF_SYSGROUP,RB_DEF_FILE_PERMS);
                self::$internal_msgs->log(__METHOD__,'File created: '.$tftpcfgfile->get_filename());
            }
            $tftpcfgfile->add_lines($dnsmasq_tftp_config);
        }
        catch (\Exception $e) {
            self::$internal_msgs->log(__METHOD__,'File not created: '.$tftpcfgfile->get_filename(),'error');
            self::$internal_msgs->log(__METHOD__,'Exception: '.$e->get_message(),'error');

            return FALSE;
        }

        self::$dnsmasq_restart_required = TRUE;

        return TRUE;
    }

    /**
    * this function is used to delete a dnsmasq TFTP config file based on global cfg
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _delete_dnsmasq_tftp_cfg_file() {
        clearos_profile(__METHOD__, __LINE__);

        $globalcfg = self::$rb_cfg->get_global_cfg();

        try {
            $tftpcfgfile = new File($globalcfg['tftpserver']['cfgfile']);
            if ($tftpcfgfile->exists()) {
                $tftpcfgfile->delete();
                self::$internal_msgs->log(__METHOD__,'File deleted: ',$globalcfg['tftpserver']['cfgfile']);
                self::$dnsmasq_restart_required = TRUE;
            }
        }
        catch (\Exception $e) {
            self::$internal_msgs->log(__METHOD__,'Could not delete: ',$globalcfg['tftpserver']['cfgfile']);
            return FALSE;
        }
        return TRUE;
    }

    /**
    * check dnsmasq service status and enable an start it (if required)
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _check_and_enable_dnsmasq_service()
    {
        clearos_profile(__METHOD__, __LINE__);

        $Dnsmasq_service = new Dnsmasq;
        $running_state = $Dnsmasq_service->get_running_state();

        if (!$Dnsmasq_service->get_boot_state()) {
            self::$internal_msgs->log(__METHOD__,'Enabling dnsmasq service...');
            $Dnsmasq_service->set_boot_state(TRUE);
        }
        if (!self::$dnsmasq_restart_required) {
            // dnsmasq restart not required
            return TRUE;
        } elseif ($running_state) {
            self::$internal_msgs->log(__METHOD__,'Restarting dnsmasq service...');
            $Dnsmasq_service->restart();
            self::$dnsmasq_restart_required = FALSE;
            return TRUE;
        } else {
            self::$internal_msgs->log(__METHOD__,'dnsmasq service is not running...', 'error');
            return FALSE;
        }
    }

    /**
    * check nfs-server service status. Enable an start it if required.
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _start_and_enable_nfs_service()
    {
        clearos_profile(__METHOD__, __LINE__);

        $running = self::$rb_nfs->get_running_state();

        if (!self::$rb_nfs->get_boot_state()) {
            self::$internal_msgs->log(__METHOD__,'enabling nfs-server');
            self::$rb_nfs->set_boot_state(TRUE);
        }
        if (!$running) {
            self::$internal_msgs->log(__METHOD__,'starting nfs-server');
            try {
                self::$rb_nfs->set_running_state(TRUE);
            } catch (\Exception $e) {
                self::$internal_msgs->log(__METHOD__,'nfs-server exception: '.$e->get_message(),'error');
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
    * check nfs-server service status. Disable and stop it if we are not using it.
    *
    * QUESTION this approach might be too intrusive. Suggestions are welcome
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _stop_and_disable_nfs_service()
    {
        clearos_profile(__METHOD__, __LINE__);

        $running = self::$rb_nfs->get_running_state();
        $boot_state = self::$rb_nfs->get_boot_state();
        $error = FALSE;
        if ($boot_state) {
            self::$internal_msgs->log(__METHOD__,'disabling nfs-server');
            try {
                self::$rb_nfs->set_boot_state(FALSE);
            } catch (\Exception $e) {
                self::$internal_msgs->log(__METHOD__,'nfs-server exception: '.$e->get_message(),'error');
                $error = TRUE;
            }
        }
        if ($running) {
            self::$internal_msgs->log(__METHOD__,'stopping nfs-server');
            try {
                self::$rb_nfs->set_running_state(FALSE);
            } catch (\Exception $e) {
                self::$internal_msgs->log(__METHOD__,'nfs-server exception: '.$e->get_message(),'error');
                $error = TRUE;
            }
        }
        return !$error;
    }

    /**
    * check if dnsmasq tftp server configuration is appropriate for network booting
    *
    * we expect something like this:
    * array (
    *   0 =>
    *   array (
    *     0 => 'dhcp-boot=<bootloader name>',
    *     1 => 'enable-tftp',
    *     2 => 'tftp-root=<tftp root folder>',
    *   ),
    * )
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _check_dnsmasq_tftp_cfg() {
        clearos_profile(__METHOD__, __LINE__);

        // get all uncommented lines from the config files
        $grep_args = '-Evh "^#" /etc/dnsmasq.d/*conf /etc/dnsmasq.conf';
        try {
            $shell = new Shell();
            $shell->execute(File::COMMAND_GREP, $grep_args);
            $shell_output_array = $shell->get_output();
        } catch (\Exception $e) {
            self::$internal_msgs->log(__METHOD__,'error checking dnsmasq tftp config: '.$e->get_message(),'error');
            return FALSE;
        }

        // find the configuration lines that we need to have in the TFTP file
        $regex_pattern = "/dhcp-boot=.*|enable-tftp.*|tftp-root=.*/";
        $uncommented_lines = implode(RB_LINE_BREAK,$shell_output_array);
        $match = array();

        if (!preg_match_all($regex_pattern,$uncommented_lines,$match)) {
            self::$internal_msgs->log(__METHOD__,'no matches for tftp cfg regex pattern: '.$regex_pattern,'warning');
            return FALSE;
        }
        elseif (count($match[0]) !== 3) {
            self::$internal_msgs->log(__METHOD__,'unexpected number matches in tftp config');
            return FALSE;
        }
        self::$internal_msgs->log(__METHOD__,'tftp server config found');
        return TRUE;
    }

    /**
    * Returns a list of network interfaces that can use DHCP, and therefore
    * suitable to be used for Remote Boot. Taken from dhcp app.
    *
    * @return array list of usable interfaces
    * @throws Engine_Exception
    */

    public static function get_usable_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['filter_ppp'] = TRUE;

        $interfaces = new Iface_Manager();

        $ethlist = $interfaces->get_interfaces($options);
        $validlist = array();

        foreach ($ethlist as $eth) {
            $ethinfo = new Iface($eth);

            $validlist[$eth] = array();
            $validlist[$eth]['ip'] = $ethinfo->get_live_ip();
            $validlist[$eth]['netmask'] = $ethinfo->get_live_netmask();
        }

        return $validlist;
    }

    /**
    * Adds a new profile to the Remote Boot configuration file, as well as:
    * - create profile folders,
    * - create profile exports file,
    * - create profile pxelinux cfg file,
    * - reload NFS exports
    *
    * @param    array $form_data
    * @return   TRUE if new profile was successfully added, FALSE if not.
    */

    public function add_new_profile( $form_data )
    {
        clearos_profile(__METHOD__, __LINE__);
        self::$user_msgs->set_queue_title(lang('msg_title_profile_add'));
        $error = FALSE;

        self::$rb_cfg->check_for_overlapping_networks($form_data,self::$user_msgs);

        if ( !$this->validate_unique_name($form_data['profile']['name']) ) {
            self::$user_msgs->add_msg_to_queue('profile_name_already_exists','('.$form_data['profile']['name'].')');
            $error = TRUE;
        }

        if ( !$this->validate_unique_network($form_data['profile']['network_cidr']) ) {
            self::$user_msgs->add_msg_to_queue('profile_network_already_exists','('.$form_data['profile']['network_cidr'].')');
            $error = TRUE;
        }

        if ( !$error )
        {
            // save config to file (Config) and get the new ID
            $profile_id = self::$rb_cfg->add_profile_cfg_to_file( $form_data );

            // we only continue if we have a valid ID
            if ($profile_id !== FALSE){
                self::$user_msgs->add_msg_to_queue('profile_added_to_config',
                '('.$form_data['profile']['name'].
                ' - '.$form_data['profile']['network_cidr'].')');

                // retrieve complete new profile data from file (after config merge)
                $newprofilecfg = self::$rb_cfg->get_profile_cfg($profile_id);

                // create profile folders
                if ($this->_create_profile_folders($newprofilecfg,self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_folders_created');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_folders_not_created');
                    $error = TRUE;
                }

                if ($newprofilecfg['enabled'] === '1'){
                    // create exports file
                    if(self::$rb_nfs->create_profile_exports($newprofilecfg,self::$user_msgs)) {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_not_created');
                        $error = TRUE;
                    }

                    // create pxelinux configuration file
                    self::$rb_pxelinux->set_pxe_profile_cfg($newprofilecfg);
                    if (self::$rb_pxelinux->sync_pxecfg('create')) {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_created');
                        $error = TRUE;
                    }

                    // reload NFS exports
                    if (self::$rb_nfs->reload_all_exports(self::$user_msgs)) {
                        self::$user_msgs->add_msg_to_queue('profile_exports_reloaded');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_exports_not_reloaded');
                        $error = TRUE;
                    }

                }

            } else {
                self::$user_msgs->add_msg_to_queue('profile_failed_to_add_to_config','('.$form_data['profile']['name'].')');
                $error = TRUE;
            }
        }
        return !$error;
    }

    /**
    * Edits an existing profile and saves it back to the Remote Boot configuration file. It also:
    * - moves profile folders,
    * - removes/creates profile exports file,
    * - removes/creates profile pxelinux cfg file,
    * - reloads NFS exports
    *
    * @param    $form_data array
    * @param    $id profile id integer
    *
    * @return   TRUE if new profile was successfully updated, FALSE if not.
    */
    public function edit_profile( $form_data, $id )
    {
        clearos_profile(__METHOD__, __LINE__);
        self::$user_msgs->set_queue_title(lang('msg_title_profile_edit'));
        $error = FALSE;

        // the function below should add new messages to our Messages queue
        self::$rb_cfg->check_for_overlapping_networks($form_data,self::$user_msgs);

        if ( !$this->validate_unique_name($form_data['profile']['name'], $id) ) {
            self::$user_msgs->add_msg_to_queue('profile_name_already_exists','('.$form_data['profile']['name'].')');
            $error = TRUE;
        }

        if ( !$this->validate_unique_network($form_data['profile']['network_cidr'], $id) ) {
            self::$user_msgs->add_msg_to_queue('profile_network_already_exists','('.$form_data['profile']['network_cidr'].')');
            $error = TRUE;
        }

        if ( !$error )
        {
            // get the old config from file
            $oldprofilecfg = self::$rb_cfg->get_profile_cfg($id);

            // merge and save new config to file (Config)
            $profile_edit_result = self::$rb_cfg->update_profile_cfg_on_file( $form_data, $id );

            if ($profile_edit_result === TRUE){
                self::$user_msgs->add_msg_to_queue('profile_replaced_in_config',' ('.$form_data['profile']['name'].')');

                // retrieve complete new profile data from file (after merge)
                $newprofilecfg = self::$rb_cfg->get_profile_cfg($id);
                $globalcfg = self::$rb_cfg->get_global_cfg();

                // move profile folders
                if ($this->_move_profile_folders($globalcfg,$oldprofilecfg,$newprofilecfg,self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_folders_moved');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_folders_not_moved');
                    $error = TRUE;
                }

                // remove old exports file
                if (self::$rb_nfs->remove_profile_exports($oldprofilecfg,self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_exports_file_removed');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_exports_file_not_removed');
                    $error = TRUE;
                }

                // remove old pxelinux configuration file
                self::$rb_pxelinux->set_pxe_profile_cfg($oldprofilecfg);
                if (self::$rb_pxelinux->sync_pxecfg('remove')) {
                    self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_removed');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_removed');
                    $error = TRUE;
                }
                if ($newprofilecfg['enabled'] === '1'){
                    // create exports file
                    if(self::$rb_nfs->create_profile_exports($newprofilecfg,self::$user_msgs)) {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_not_created');
                        $error = TRUE;
                    }

                    // create pxelinux configuration file
                    self::$rb_pxelinux->set_pxe_profile_cfg($newprofilecfg);
                    if (self::$rb_pxelinux->sync_pxecfg('create')) {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_created');
                        $error = TRUE;
                    }
                }
                // always reload NFS exports when profile is edited...
                // reload NFS exports
                if (self::$rb_nfs->reload_all_exports(self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_exports_reloaded');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_exports_not_reloaded');
                    $error = TRUE;
                }

            } else {
                self::$user_msgs->add_msg_to_queue('profile_failed_to_replace_config','('.$form_data['profile']['name'].')');
                $error = TRUE;
            }
        }
        return !$error;
    }

    /**
    * Removes an existing profile from Remote Boot configuration file. It also:
    * - removes profile folders (if empty),
    * - removes profile exports file,
    * - removes profile pxelinux cfg file,
    * - reloads NFS exports
    *
    * @param    $id profile id integer
    * @return   TRUE if new profile was successfully removed, FALSE if not.
    */

    public function remove_profile( $id )
    {
        clearos_profile(__METHOD__, __LINE__);
        self::$user_msgs->set_queue_title(lang('msg_title_profile_delete'));
        $error = FALSE;
        $profilecfg = self::$rb_cfg->get_profile_cfg($id);

        if ( $profilecfg !== FALSE )
        {
            $globalcfg = self::$rb_cfg->get_global_cfg();
            // remove old exports file
            if (self::$rb_nfs->remove_profile_exports($profilecfg,self::$user_msgs)) {
                self::$user_msgs->add_msg_to_queue('profile_exports_file_removed');
            } else {
                self::$user_msgs->add_msg_to_queue('profile_exports_file_not_removed');
                $error = TRUE;
            }
            // remove old pxelinux configuration file
            self::$rb_pxelinux->set_pxe_profile_cfg($profilecfg);
            if (self::$rb_pxelinux->sync_pxecfg('remove')) {
                self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_removed');
            } else {
                self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_removed');
                $error = TRUE;
            }

            // remove profile folders
            if ($this->_remove_profile_folders($globalcfg,$profilecfg,self::$user_msgs)) {
                self::$user_msgs->add_msg_to_queue('profile_all_folders_deleted');
            } else {
                // this is not an error, as some folders may have contents that the user may wish to keep
                self::$user_msgs->add_msg_to_queue('profile_not_all_folders_deleted');
            }

            // remove config from file
            if (self::$rb_cfg->remove_profile_cfg_from_file($id)) {
                self::$user_msgs->add_msg_to_queue('profile_removed_from_config');
            } else {
                self::$user_msgs->add_msg_to_queue('profile_not_removed_from_config');
                $error = TRUE;
            }
            // reload NFS exports
            if (self::$rb_nfs->reload_all_exports(self::$user_msgs)) {
                self::$user_msgs->add_msg_to_queue('profile_exports_reloaded');
            } else {
                self::$user_msgs->add_msg_to_queue('profile_exports_not_reloaded');
                $error = TRUE;
            }

        } else {
            self::$user_msgs->add_msg_to_queue('profile_not_found',$id);
            return FALSE;
        }
        return TRUE;
    }

    /**
    * Changes existing profile's state and runs necessary operations:
    * - removes/creates profile exports file,
    * - removes/creates profile pxelinux cfg file,
    * - reloads NFS exports
    *
    * @param    $id profile id integer
    * @param    $action 'enable'|'disable'
    *
    * @return   TRUE if new profile was successfully removed, FALSE if not.
    */

    public function manage_profile( $id, $action )
    {
        clearos_profile(__METHOD__, __LINE__);
        if ($action == 'enable')
        self::$user_msgs->set_queue_title(lang('msg_title_profile_enable'));
        elseif ($action == 'disable')
        self::$user_msgs->set_queue_title(lang('msg_title_profile_disable'));
        else
        return FALSE;

        $profilecfg = self::$rb_cfg->get_profile_cfg($id);

        if ( $profilecfg !== FALSE )
        {
            if ($action == 'enable')
            $profilecfg['enabled'] = '1';
            elseif ($action == 'disable')
            $profilecfg['enabled'] = '0';

            // the update function expects a $form_data['profile'] format..
            $form_data['profile'] = $profilecfg;

            $profile_edit_result = self::$rb_cfg->update_profile_cfg_on_file( $form_data, $id );

            if ($profile_edit_result === TRUE) {
                // we always try to remove pxe and nfs exports config
                // remove old exports file
                if (self::$rb_nfs->remove_profile_exports($profilecfg,self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_exports_file_removed');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_exports_file_not_removed');
                    $error = TRUE;
                }
                // remove old pxelinux configuration file
                self::$rb_pxelinux->set_pxe_profile_cfg($profilecfg);
                if (self::$rb_pxelinux->sync_pxecfg('remove')) {
                    self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_removed');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_removed');
                    $error = TRUE;
                }
                // and we only create config again in case of enabling
                if ($action == 'enable') {
                    // create exports file
                    if(self::$rb_nfs->create_profile_exports($profilecfg,self::$user_msgs)) {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_exports_file_not_created');
                        $error = TRUE;
                    }
                    // create pxelinux configuration file
                    self::$rb_pxelinux->set_pxe_profile_cfg($profilecfg);
                    if (self::$rb_pxelinux->sync_pxecfg('create')) {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_created');
                    } else {
                        self::$user_msgs->add_msg_to_queue('profile_pxelinux_cfg_file_not_created');
                        $error = TRUE;
                    }
                }
                // always reload NFS exports when profile is edited...
                // reload NFS exports
                if (self::$rb_nfs->reload_all_exports(self::$user_msgs)) {
                    self::$user_msgs->add_msg_to_queue('profile_exports_reloaded');
                } else {
                    self::$user_msgs->add_msg_to_queue('profile_exports_not_reloaded');
                    $error = TRUE;
                }
            } else {
                return FALSE;
                self::$user_msgs->add_msg_to_queue('profile_failed_to_replace_config','('.$form_data['profile']['name'].')');
            }
        }
        return TRUE;
    }


    /**
    * Edit global settings
    * - checks state of global TFTP server
    * - updates configuration file with new global settings
    *
    * @param    $form_data with global settings
    *
    * @return   TRUE if new profile was successfully edited, FALSE if not.
    */

    public function edit_global_settings( $form_data )
    {
        clearos_profile(__METHOD__, __LINE__);

        self::$user_msgs->set_queue_title(lang('msg_title_global_edit'));

        if ( self::$rb_cfg->update_global_cfg_to_file($form_data) ){
            self::$user_msgs->add_msg_to_queue('global_settings_udpated');
        } else {
            self::$user_msgs->add_msg_to_queue('global_settings_not_updated');

            return FALSE;
        }

        // after changing the config, we retrieve after the merge
        $globalcfg = self::$rb_cfg->get_global_cfg();

        if ($globalcfg['tftpserver']['automanage'] === '1') {
            self::$internal_msgs->log(__METHOD__,'Deleting and creating tftp server settings');
            $this->_delete_dnsmasq_tftp_cfg_file();
            // this sets the flag for a dnsmasq restart as well
            $this->_create_dnsmasq_tftp_cfg_file();
        }

        return TRUE;
    }


    ////////////////////////////////////////////////////////////////////
    // P R I V A T E    M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Makes sure that bootloader is at tftp root
    *
    * @return   TRUE if successful, FALSE if not.
    */

    protected function _prepare_bootloader(  )
    {
        clearos_profile(__METHOD__, __LINE__);

        // get global configuration
        $globalcfg = self::$rb_cfg->get_global_cfg();

        // set the config for Pxelinux and make sure that the bootloader is in the tftp root folder
        self::$rb_pxelinux->set_pxe_global_cfg($globalcfg);

        return self::$rb_pxelinux->copy_bootloader_to_tftp_root();
    }


    /**
    * Create all global folders and files if they don't exist, to ensure proper
    * operation of the application.
    *
    * @return void
    */

    protected function _create_global_folders_and_files(){
        clearos_profile(__METHOD__, __LINE__);

        $globalcfg = self::$rb_cfg->get_global_cfg();

        // create all global folders
        foreach ($globalcfg['folders'] as $key => $folder) {
            $f = new Folder($folder['name']);
            if( !$f->exists() ) {
                $f->create(RB_DEF_SYSUSER,RB_DEF_SYSGROUP,$folder['perms']);
                self::$internal_msgs->log(__METHOD__,'folder created '.$folder['name']);
            }
        }
        return;
    }

    /**
    * Creates profile folder structure
    *
    * @param  $profilecfg array of configuration details
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _create_profile_folders($profilecfg) {
        clearos_profile(__METHOD__, __LINE__);
        $error = FALSE;
        foreach ($profilecfg['folders'] as $key => $folder) {
            $f = new Folder($folder['name']);
            if( !$f->exists() ) {
                try{
                    $f->create(RB_DEF_SYSUSER,RB_DEF_SYSGROUP,$folder['perms']);
                    self::$internal_msgs->log(__METHOD__,'folder created '.$folder['name']);
                } catch (\Exception $e) {
                    self::$internal_msgs->log(__METHOD__,'could not create folder '.$folder['name'],'warning');
                    $error = TRUE;
                }
            }
        }
        return !$error;
    }

    /**
    * move profile folders
    *
    * @param  $globalcfg array of global configuration details
    * @param  $oldprofilecfg array of old profile configuration details
    * @param  $newprofilecfg array of new profile configuration details
    *
    * @return TRUE if successful, FALSE otherwise
    */
    protected function _move_profile_folders($globalcfg, $oldprofilecfg,$newprofilecfg) {
        clearos_profile(__METHOD__, __LINE__);
        $error = FALSE;
        $tftproot = $globalcfg['folders']['tftp']['name'];
        $nfsroot = $globalcfg['folders']['nfs']['name'];
        $oldprofilename = $oldprofilecfg['name'];
        $newprofilename = $newprofilecfg['name'];

        $oldtftpname = $tftproot.'/'.$oldprofilename;
        $newtftpname = $tftproot.'/'.$newprofilename;
        $oldnfsname  = $nfsroot.'/'.$oldprofilename;
        $newnfsname  = $nfsroot.'/'.$newprofilename;

        $old_tftp_folder = new Folder($oldtftpname);
        $new_tftp_folder = new Folder($newtftpname);
        $old_nfs_folder  = new Folder($oldnfsname);
        $new_nfs_folder  = new Folder($newnfsname);

        if ($old_tftp_folder->exists() && !$new_tftp_folder->exists()) {
            try {
                $old_tftp_folder->move_to($newtftpname);
                self::$internal_msgs->log(__METHOD__,'Moved '.$oldtftpname.' to '.$newtftpname);
            }
            catch (\Exception $e){
                self::$internal_msgs->log(__METHOD__,'Error moving '.$oldtftpname.' to '.$newtftpname);
                $error = TRUE;
            }
        }
        if ($old_nfs_folder->exists() && !$new_nfs_folder->exists()) {
            try {
                $old_nfs_folder->move_to($newnfsname);
                self::$internal_msgs->log(__METHOD__,'Moved '.$oldnfsname.' to '.$newnfsname);
            }
            catch (\Exception $e){
                self::$internal_msgs->log(__METHOD__,'Error moving '.$oldnfsname.' to '.$newnfsname);
                $error = TRUE;
            }
        }

        return !$error;
    }

    /**
    * remove profile folders
    *
    * @param  $globalcfg array of global configuration details
    * @param  $profilecfg array of profile configuration details
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _remove_profile_folders($globalcfg, $profilecfg) {
        clearos_profile(__METHOD__, __LINE__);
        $error = FALSE;
        // remove subfolders
        foreach ($profilecfg['folders'] as $key => $folder) {
            $f = new Folder($folder['name']);
            if( $f->exists() ) {
                try{
                    $f->delete();
                    self::$internal_msgs->log(__METHOD__,'Deleted folder '.$folder['name']);
                } catch (\Exception $e) {
                    self::$internal_msgs->log(__METHOD__,'Could not delete folder '.$folder['name'],'warning');
                    $error = TRUE;
                }
            }
        }
        // remove profile folder from tftp root and nfs root
        $profiletftproot = $globalcfg['folders']['tftp']['name'].'/'.$profilecfg['name'];
        $profilenfsroot = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'];

        $tftp = new Folder($profiletftproot);
        $nfs = new Folder($profilenfsroot);

        try{
            $tftp->delete();
            self::$internal_msgs->log(__METHOD__,'Deleted folder '.$profiletftproot);
        }
        catch (\Exception $e) {
            self::$internal_msgs->log(__METHOD__,'Could not delete '.$profiletftproot);
            $error = TRUE;
        }
        try{
            $nfs->delete();
            self::$internal_msgs->log(__METHOD__,'Deleted folder '.$profilenfsroot);
        }
        catch (\Exception $e) {
            self::$internal_msgs->log(__METHOD__,'Could not delete '.$profilenfsroot);
            $error = TRUE;
        }

        return !$error;
    }


    ///////////////////////////////////////////////////////////////////////////////
    // FORM VALIDATION FUNCTIONS
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * Validates profile name characters, length, and check whether it is unique
    *
    * @param    $name profile name string
    *
    * @return   error message string if profile name is invalid
    */

    public function validate_profile_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // match combination of word characters (a-z,uppercase,digits), dashes, and dots.
        $pattern='/^[\w\-\.]+$/';
        if (!preg_match($pattern, $name))
            return lang('form_validation_profile_name_characters');
        if (strlen($name) > RB_DEF_PROFILE_NAME_MAX_SIZE)
            return lang('form_validation_profile_name_length');
    }

    /**
    * Checks whether a profile name is unique. If ID is supplied, considers
    * matching profile names unique if IDs match
    *
    * @param    $name profile name string
    * @param    $id profile id (integer)
    *
    * @return   string error message if profile name is invalid
    */

    public function validate_unique_name($name, $id = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $profile_list_by_id = self::$rb_cfg->get_all_profiles_cfg();
        foreach ($profile_list_by_id as $existing_id => $profile) {
            if ( ($name === $profile['name']) && ($existing_id !== $id) ) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
    * Validates IP address
    *
    * @param string $ip address
    *
    * @return string error message if IP is invalid
    */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    /**
    * Validates network address with prefix
    *
    * @param string $ip address with prefix
    *
    * @return string error message anything is considered invalid input
    */

    public function validate_ip_network_with_prefix($network_with_prefix)
    {
        clearos_profile(__METHOD__, __LINE__);

        $net = self::$rb_cfg->get_network_and_prefix($network_with_prefix);

        // check if network prefix is alright
        if (Network_Utils::is_valid_prefix($net['prefix'])) {
            // Convert a prefix (/24) to a netmask (/255.255.255.0)
            $net['netmask'] = Network_Utils::get_netmask($net['prefix']);
        } else if (Network_Utils::is_valid_netmask($net['prefix'])) {
            $net['netmask'] = $net['prefix'];
        } else {
            return lang('network_prefix_invalid');
        }

        // check if network part constitutes a valid ip
        if (! Network_Utils::is_valid_ip($net['network']))
            return lang('form_validation_network_invalid');

        // calculate real network address
        $realnetwork = Network_Utils::get_network_address($net['network'], $net['netmask']);

        if ($realnetwork !== $net['network'])
            return lang('form_validation_network_address_invalid').$realnetwork;

    }

    /**
    * Checks whether a network with prefix is unique. If ID is supplied, considers
    * matching networks unique if IDs match
    *
    * @param    $network_with_prefix profile network with prefix string
    * @param    $id profile id (integer)
    *
    * @return   string error message if profile network is not unique
    */

    public function validate_unique_network($network_with_prefix, $id = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $profile_list_by_id = self::$rb_cfg->get_all_profiles_cfg();
        foreach ($profile_list_by_id as $existing_id => $profile) {
            if ( ($profile['network_cidr'] === $network_with_prefix) && ($existing_id !== $id) ) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
    * validates boolean fields from the form
    *
    * @param    $input is '0' or '1'
    *
    * @return   error message if invalid
    */

    public function validate_cfg_boolean($input)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!($input == '0' || $input == '1'))
            return lang('form_validation_boolean');
    }

    /**
    * validates path or filename fields from the form
    *
    * @param    $path string
    *
    * @return   error message if invalid
    */

    public function validate_absolute_path_or_file($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        // match combination of word characters (a-z,uppercase,digits),
        // dashes, dots, and slashes. need absolute paths (start with '/')
        // We don't support spaces
        if (preg_match_all("/\n|\r/m", $path))
            return lang('form_validation_no_newlines_allowed');
        $pattern='/^\/[\w\-\.\/]{1,}$/';
        if (!preg_match($pattern, $path))
            return lang('form_validation_path_invalid');
    }

    /**
    * validates kernel parameters supplied through the form
    *
    * @param    $kparams string
    *
    * @return   error message if invalid
    */

    public function validate_kparams($kparams)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match_all("/\n|\r/m", $kparams))
            return lang('form_validation_no_newlines_allowed');

        $pattern='/^[\w\-\.\/\=\:\s]{1,}$/';
        if (!preg_match($pattern, $kparams))
            return lang('form_validation_kparams_invalid');
    }

    /**
    * validates NFS export options supplied through the form
    *
    * @param    $options string with comma separated options
    *
    * @return   error message if invalid
    */

    public function validate_nfs_params($options)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match_all("/\n|\r/m", $options))
            return lang('form_validation_no_newlines_allowed');

        $options_array = explode(',',$options);
        $pattern  = "/(^sec=.*|^secure|^rw|^ro|^async|^sync|^no_wdelay|^nohide|";
        $pattern .= "^crossmnt|^no_subtree_check|^insecure_locks|^no_auth_nlm|";
        $pattern .= "^no_acl|^mountpoint=.*|^mp=.*|^fsid=.*|^refer=.*|^replicas=.*|";
        $pattern .= "^root_squash|^no_root_squash|^all_squash|anonuid=.*|^anongid=.*)$/";

        foreach ($options_array as $key => $option){
            if(strlen($option) === 0)
                return lang('form_validation_nfs_empty_param');
            if(!preg_match($pattern, $option))
                return lang('form_validation_nfs_invalid_param_char').' "'.$option.'"';
        }
    }

    /**
    * validates hostname
    *
    * @param    $hostname string
    *
    * @return   error message if invalid
    */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_hostname_invalid');
    }

}

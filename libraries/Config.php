<?php

/**
* Remote Boot Config management class. Handles configuration management in array and XML formats
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
use \clearos\apps\marketplace\Marketplace as Marketplace;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\base\App as App;


clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/App');
clearos_load_library('remote_boot/Messages');
clearos_load_library('remote_boot/Array2XML');
clearos_load_library('remote_boot/XML2Array');
clearos_load_library('marketplace/Marketplace');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

include_once(dirname(__FILE__) . '/Constants.php');

////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////

class Config
{
    ////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ////////////////////////////////////////////////////////////////////

    protected $config = array();
    protected $InternalMessages;
    protected $cfgloaded = FALSE;

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
     * Remote Boot Config constructor.
     *
     * @param   Messages $InternalMessages
     *
     */

    public function __construct(Messages $InternalMessages)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->InternalMessages = $InternalMessages;

        $this->load_cfg_from_file();
    }

    /**
     * Loads configuration from file if not loaded yet
     *
     * @return  void
     */

    public function load_cfg_from_file() {
        clearos_profile(__METHOD__, __LINE__);

        // there are many functions that call this...
        // but we only want to retrieve from file when really necessary
        if (!$this->cfgloaded){
            $this->_load_cfg_from_file();
        }
    }

    /**
     * gets full configuration array from file
     *
     * @return  Array with full configuration
     */

    public function get_full_cfg()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load_cfg_from_file();

        return $this->config;
    }

    /**
     * gets only the global configuration section from file
     *
     * @return  Array with global configuration
     */

    public function get_global_cfg()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load_cfg_from_file();

        return $this->get_full_cfg()['cfg']['global'];
    }

    /**
    * fixes single profile configurations returned by XML2Array
    *
    * XML2Array may return different results depending on the existing number of
    * profiles:
    *  - single profile: $profiles = Array(
    *                                'profile' => Array (profile_data)
    *                                     )
    *  - more profiles: $profiles = Array(
    *                                'profile' => Array (
    *                                    0 => Array (profile_data)
    *                                    1 => Array (profile_data)
    *                                     ...
    *                                                    )
    *                                    )
    * this function will fix the single profile case to always contain the numeric
    * index, so that we don't have to worry about this elsewhere
    *
    * @return TRUE if fix was applied, FALSE if nothing was done
    */
    protected function _fix_single_profile_cfg()
    {
        clearos_profile(__METHOD__, __LINE__);

        // we use the 'name' field to test whether we have a single profile
        if (isset($this->config['cfg']['profiles']['profile']['name'])){
            // we have a single profile
            $this->InternalMessages->log(__METHOD__,'Single profile fix needed');
            $single_profile = $this->config['cfg']['profiles']['profile'];
            unset($this->config['cfg']['profiles']['profile']);
            $this->config['cfg']['profiles']['profile'][0] = $single_profile;

            return TRUE;
        }
        $this->InternalMessages->log(__METHOD__,'Single profile fix NOT needed');

        return FALSE;
    }

    /**
     * gets all profiles from configuration file, indexed by profile ID
     *
     * @return  Array with all profiles configuration
     */

    public function get_all_profiles_cfg()
    {
        clearos_profile(__METHOD__, __LINE__);

        $profile_list = $this->get_full_cfg()['cfg']['profiles'];

        $profile_list_by_id = array();
        $profile_list_by_id['profile'] = array();

        foreach ($profile_list['profile'] as $key => $value) {
            $profile_list_by_id['profile'][(int) $value['@attributes']['id']] = $value;
        }

        return $profile_list_by_id['profile'];

    }

    /**
     * get a specific profile configuration from file
     *
     * @param   $id profile id
     *
     * @return  Array with all profiles configuration if successful, FALSE if unsuccessful
     */

    public function get_profile_cfg($id)
    {
        clearos_profile(__METHOD__, __LINE__);
        $profile_list = $this->get_all_profiles_cfg();

        if (isset($profile_list[$id])) {
            return $profile_list[$id];
        }
        else {
            $this->InternalMessages->log(__METHOD__,'profile id not found: '.$id, 'error');
            return FALSE;
        }
    }

    /**
     * merges form data configuration supplied by user into global configuration, and then
     * writes full configuration to file
     *
     * @param   $form_data user input form data
     *
     * @return  TRUE if successful, FALSE otherwise
     */

    public function update_global_cfg_to_file($form_data)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load_cfg_from_file();

        $temp_array = $this->_merge_config($this->config['cfg']['global'],$form_data['global']);
        $this->config['cfg']['global'] = $temp_array;

        return $this->_write_cfg_to_file();
    }

    /**
     * Adds form_data to configuration file
     *
     * @param   $form_data with user form data
     *
     * @return  new profile ID (integer) if successful, FALSE otherwise
     */

    public function add_profile_cfg_to_file($form_data)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->load_cfg_from_file();

        // find greatest ID from the existing profiles
        $profile_list_by_id = $this->get_all_profiles_cfg();
        $last_id = (int) max(array_keys($profile_list_by_id));
        $new_id = $last_id + 1;

        // merge form data into default config
        $newprofile = $this->_merge_config(
            $this->_generate_default_profile_config(),
            $form_data['profile']
        );

        $newprofile['@attributes']['id'] = $new_id;

        // store profile in the list we retrieved and overwrite profiles with the
        // updated list:
        $profile_list_by_id[$new_id] = $newprofile;
        unset($this->config['cfg']['profiles']['profile']);
        $this->config['cfg']['profiles']['profile'] = $profile_list_by_id;

        $result = $this->_write_cfg_to_file();

        if (!$result) {
            $this->InternalMessages->log(__METHOD__,'Error adding profile to cfg','error');
            return FALSE;
        }
        //  otherwise return the new ID of the profile
        return $new_id;
    }

    /**
     * Merges configuration data from form data into specified profile ID configuration
     * and stores new config into file
     *
     * @param   $form_data with user form data
     * @param   $id specifying profile to be updated
     *
     * @return  TRUE if successful, FALSE otherwise
     */

    public function update_profile_cfg_on_file($form_data, int $id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!isset($id)) {
            $this->InternalMessages->log(__METHOD__,'Profile not specified','error');
            return FALSE;
        }

        $profile_list_by_id = $this->get_all_profiles_cfg();

        // we look for the profile id supplied.
        if (isset($profile_list_by_id[$id])) {
            $profile_list_by_id[$id] = $this->_merge_config($profile_list_by_id[$id],$form_data['profile']);
            // we have to unset everything because we can't rely on the array keys
            // generated by XML to array
            unset($this->config['cfg']['profiles']['profile']);
            // then we replace by the modified array of profiles
            $this->config['cfg']['profiles']['profile'] = $profile_list_by_id;
        } else {
            $this->InternalMessages->log(__METHOD__,'Profile not found in config','error');
            return FALSE;
        }
        $result = $this->_write_cfg_to_file();
        return $result;
    }

    /**
     * Removes profile associated with specified profile ID from configuration file
     *
     * @param   $id specifying profile to be removed
     *
     * @return  TRUE if successful, FALSE otherwise
     */

    public function remove_profile_cfg_from_file(int $id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!isset($id)) {
            $this->InternalMessages->log(__METHOD__,'Profile not specified','error');
            return FALSE;
        }

        $this->load_cfg_from_file();

        $profile_list_by_id = $this->get_all_profiles_cfg();

        // we look for the profile id supplied.
        if (isset($profile_list_by_id[$id])) {
            // remove all profiles from the config in memory:
            unset($this->config['cfg']['profiles']['profile']);
            // remove the specified profile from our copy
            unset($profile_list_by_id[$id]);
            // then we replace cfg with the modified array of profiles
            $this->config['cfg']['profiles']['profile'] = $profile_list_by_id;
        } else {
            $this->InternalMessages->log(__METHOD__,'Profile not found in config','error');
        }

        return $this->_write_cfg_to_file();
    }

    /**
     * generates a template configuration based on:
     * - profile name (used in folder names and config files),
     * - NFS server IP (used in kernel parameters),
     * - TFTP server ip,
     * - current global configuration retrieved from file,
     * - and the default profile configuration (_generate_default_profile_config)
     *
     * used mainly by ajax call to update user form automatically
     *
     * @param   $name profile name string
     * @param   $nfsip NFS server ip string
     * @param   $tftpip TFTP server ip string
     *
     * @return  profile configuration array
     */

    public function generate_template_cfg_based_on_global_settings($name = NULL, $nfsip = NULL, $tftpip = NULL) {
        clearos_profile(__METHOD__, __LINE__);

        $globalcfg = $this->get_global_cfg();
        $profilecfg = $this->_generate_default_profile_config();

        if (!is_null($name))
            $profilecfg['name'] = $name;
        else
            $profilecfg['name'] = 'new_profile';

        if (!is_null($nfsip))
            $profilecfg['nfs']['ip'] = $nfsip;
        else
            $profilecfg['nfs']['ip'] = $globalcfg['nfsserver']['ip'];

        if (!is_null($tftpip))
            $profilecfg['tftp']['ip'] = $tftpip;
        else
            $profilecfg['tftp']['ip'] = $globalcfg['tftpserver']['ip'];

        $profilecfg['folders']['kernel']['name'] = $globalcfg['folders']['tftp']['name'].'/'.$profilecfg['name'].'/kernel';
        $profilecfg['pxelinux']['cfgfile'] = $globalcfg['folders']['pxelinux']['name'].'/'.$profilecfg['name'];
        $profilecfg['pxelinux']['initrd'] = $profilecfg['folders']['kernel']['name'].'/initrd.img';
        $profilecfg['pxelinux']['kernel'] = $profilecfg['folders']['kernel']['name'].'/vmlinuz';


        $profilecfg['folders']['root']['name'] = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'].'/root';
        $profilecfg['folders']['rootfs']['name'] = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'].'/rootfs';
        $profilecfg['folders']['var']['name'] = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'].'/var';
        $profilecfg['folders']['etc']['name'] = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'].'/etc';
        $profilecfg['folders']['home']['name'] = $globalcfg['folders']['nfs']['name'].'/'.$profilecfg['name'].'/home';

        $profilecfg['pxelinux']['kparams'] = 'nfsroot='.$profilecfg['nfs']['ip'].':'.
                                            $profilecfg['folders']['rootfs']['name'].'/ root=nfs rootfstype=nfs';

        return $profilecfg;
    }


    /**
     * Get network and prefix based on network/prefix input
     *
     * @param   $network_with_prefix string with a network in CIDR format (192.168.0.0/24)
     *
     * @return  Array with network and prefix
     */

    public function get_network_and_prefix($network_with_prefix){
        clearos_profile(__METHOD__, __LINE__);

        // split network and prefix or netmask
        if (! preg_match("/^(.*)\/(.*)$/", $network_with_prefix, $matches))
            return FALSE;

        $result = array();
        $result['network'] = $matches[1];
        $result['prefix']  = $matches[2];

        return $result;
    }

    /**
     * Checks if newly supplied form data contains a network that overlaps
     * with networks from other profiles. Used to generate warnings to the user
     *
     * @param   $form_data with user form data
     * @param   Messages $msgs in which we store messages to the user
     *
     * @return  void
     */

    public function check_for_overlapping_networks($form_data, Messages $msgs){
        clearos_profile(__METHOD__, __LINE__);

        $this->load_cfg_from_file();

        // store network, netmask and network_cidr info about new profile in $new
        $new = $this->get_network_and_prefix($form_data['profile']['network_cidr']);
        $new['network_cidr'] = $form_data['profile']['network_cidr'];

        $profile_list_by_id = $this->get_all_profiles_cfg();

        foreach ($profile_list_by_id as $id => $oldprofile) {

            // store network info about existing profiles in $old
            $old = $this->get_network_and_prefix($oldprofile['network_cidr']);
            $old['network_cidr'] = $oldprofile['network_cidr'];

            // We consider the network address as an IP address for the purpose of detecting whether
            // one network overlaps with others...

            if ((     Network_Utils::ip_on_network($new['network'],$old['network_cidr'])
                   || Network_Utils::ip_on_network($old['network'],$new['network_cidr'])
                ) && $old['network_cidr'] !== $new['network_cidr']
               ) {
                $msgs->add_msg_to_queue('profile_network_overlap',
                    $new['network_cidr'].' overlaps with '.$oldprofile['network_cidr'].' ('.$oldprofile['name'].')');
            }
        }
        return;
    }

    ////////////////////////////////////////////////////////////////////
    // P R I V A T E    M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Generates the default global config array, with default values which should
    * constitute a valid working config.
    *
    * @return array with default global config
    */

    protected function _generate_default_global_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get metadata from App library:
        $application = new App('remote_boot');
        $metadata = $application->get_metadata();
        $this->InternalMessages->log(__METHOD__,'global cfg version '.$metadata['version'].'-'.$metadata['release']);

        // Default config array with $key => $value format
        // this is converted to XML later
        $globalcfg = array(
            '@attributes'   => array(
                'id'           => '0',
                'rbversion'   => $metadata['version'],
                'rbrelease'   => $metadata['release'],
                'rbbasename'  => $metadata['basename'],
            ),
            'tftpserver'   => array(
                'automanage'   => 0,
                'cfgfile'      => RB_TFTP_CFG_FILE,
                'ip'           => $_SERVER['SERVER_ADDR'],
                'name'         => gethostname(),
            ),
            'nfsserver'    => array(
                'automanage'   => 0,
                'cfgfile'      => '',
                'ip'           => $_SERVER['SERVER_ADDR'],
            ),
            'ldapserver'   => array(
                'automanage'   => 0,
                'cfgfile'      => '',
                'ip'           => $_SERVER['SERVER_ADDR'],
            ),
            'pxelinux'     => array(
                'bootloader'   => RB_PXE_BOOTLOADER,
            ),
            'folders'      => array(
                'tftp'         => array(
                    'name'   => RB_TFTP_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                ),
                'nfs'          => array(
                    'name'   => RB_NFSROOT_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                ),
                'pxelinux'     => array(
                    'name'   => RB_PXE_CFG_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                ),
            ),
        );
        return $globalcfg;
    }


    /**
    * Generates the default profile config array, with default values which should
    * constitute a valid working config
    *
    * @return array with default profile config
    */

    protected function _generate_default_profile_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get metadata from App library:
        $application = new App('remote_boot');
        $metadata = $application->get_metadata();
        $this->InternalMessages->log(__METHOD__,'profile cfg version '.$metadata['version'].'-'.$metadata['release']);

        // Default config array with $key => $value format
        // this is converted to XML later
        $profile = array(
            '@attributes'   => array(
                'id'           => '0',
                'rbversion'   => $metadata['version'],
                'rbrelease'   => $metadata['release'],
                'rbbasename'  => $metadata['basename'],
            ),
            'name'            => RB_DEF_PROFILE_NAME,
            'enabled'         => 0,
            'network_cidr'    => RB_DEF_PROFILE_NETWORK_CIDR,
            'tftp'            => array(
                'enabled'      => 1,
                'ip'          => $_SERVER['SERVER_ADDR'],
            ),
            'nfs'          => array(
                'enabled'     => 1,
                'ip'          => $_SERVER['SERVER_ADDR'],
            ),
            'ldap'          => array(
                'enabled'      => 0,
                'ip'          => '0.0.0.0',
                'base'        => '',
                'cert'        => '',
                'sudo'        => '',
                'pass'        => '',
            ),
            'pxelinux'     => array(
                'cfgfile'     => RB_PXE_CFG_DEFAULT_FILE,
                'initrd'  => RB_PXE_DEF_INITRAMFS_FILE,
                'kernel'  => RB_PXE_DEF_KERNEL_FILE,
                'kparams' => 'nfsroot='.$_SERVER['SERVER_ADDR'].':'.RB_DEF_ROOTFS_PATH.'/ root=nfs rootfstype=nfs ',
            ),
            'folders'      => array(
                'kernel'       => array(
                    'name'   => RB_PXE_DEF_KERNEL_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 0,
                        'options' => RB_NFS_EXPORTOPTS_RO_SQUASH,
                    ),
                ),
                'rootfs'       => array(
                    'name'   => RB_DEF_ROOTFS_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 1,
                        'options' => RB_NFS_EXPORTOPTS_RW_NOSQUASH,
                    ),
                ),
                'home'         => array(
                    'name'   => RB_DEF_HOME_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 0,
                        'options' => RB_NFS_EXPORTOPTS_RW_SQUASH,
                    ),
                ),
                'etc'          => array(
                    'name'   => RB_DEF_ETC_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 0,
                        'options' => RB_NFS_EXPORTOPTS_RW_NOSQUASH,
                    ),
                ),
                'root'         => array(
                    'name'   => RB_DEF_ROOT_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 0,
                        'options' => RB_NFS_EXPORTOPTS_RW_NOSQUASH,
                    ),
                ),
                'var'          => array(
                    'name'   => RB_DEF_VAR_PATH,
                    'perms'  => RB_DEF_FOLDER_PERMS,
                    'export' => array(
                        'enabled'  => 0,
                        'options' => RB_NFS_EXPORTOPTS_RW_NOSQUASH,
                    ),
                ),
            ),
        );
        return $profile;
    }

    /**
    * converts current configuration ($this->config) from Array to XML format
    * and  writes XML contents to file
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _write_cfg_to_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        // check and create config path if necessary
        $folder = new Folder(RB_APP_CONFIG_PATH);
        if( !$folder->exists() ) {
            try
            {
                $folder->create("root", "root", RB_DEF_FOLDER_PERMS);
                $this->InternalMessages->log(__METHOD__,'folder created '.RB_APP_CONFIG_PATH);
            }
            catch (\Exception $e)
            {
                $this->InternalMessages->log(__METHOD__,'could not create folder '.RB_APP_CONFIG_PATH,'error');
                return FALSE;
            }
        }

        // check and create config file if necessary
        $config_file = new File(RB_APP_CONFIG_FILE);
        if( !$config_file->exists() ) {
            try
            {
                $this->InternalMessages->log(__METHOD__,'file created '.$config_file->get_filename());
                $config_file->create("root", "root", RB_DEF_FILE_PERMS);
            }
            catch (\Exception $e)
            {
                $this->InternalMessages->log(__METHOD__,'could not create file '.$config_file->get_filename(),'error');
                return FALSE;
            }
        }
        // the root node 'cfg' is required for the Array2XML function to work
        // but we need to remove it before saving in case the cfg already contains it.
        $save_this_cfg = $this->config;
        if (isset($this->config['cfg'])) {
            $save_this_cfg = $this->config['cfg'];
        }
        try {
            $xml = Array2XML::createXML('cfg', $save_this_cfg);
            $xmlcfg = $xml->saveXML();
        }
        catch (\Exception $e)  {
            $this->InternalMessages->log(__METHOD__,'Array2XML error '.$e->get_message(),'error');
        }

        $temp_filename = tempnam(CLEAROS_TEMP_DIR, basename(RB_APP_CONFIG_FILE));

        if ( !($temp_file = @fopen($temp_filename, "w")) ) {
            throw new File_Exception(lang('base_file_open_error')." - ".$temp_filename, CLEAROS_INFO);
        }

        fputs($temp_file, $xmlcfg);

        fclose($temp_file);

        $config_file->replace($temp_filename);

        return TRUE;
    }

    /**
    * loads the default configuration into memory and then writes it to file
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _create_new_default_cfg_file()
    {
        clearos_profile(__METHOD__, __LINE__);
        $this->_load_cfg_from_defaults();
        if ($this->_write_cfg_to_file() === TRUE)
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
    * loads $this->config property with default configuration array
    *
    * @return void
    */

    protected function _load_cfg_from_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        // first generate the default global and profile configs
        $default_global_cfg = $this->_generate_default_global_config();
        $default_profile_cfg = $this->_generate_default_profile_config();
        // merge them together to create a nicely structured array
        $cfg = array(
            'global' => $default_global_cfg,
            'profiles' => array(
                'profile' => array(
                    //0 => $default_profile_cfg
                )
            )
        );
        $this->config = $cfg;
        return;
    }

    /**
    * loads $this->config with contents extracted from XML configuration file.
    * In case the configuration file does not exist, it creates a new default one
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _load_cfg_from_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config_file = new File( RB_APP_CONFIG_FILE );

        if( !$config_file->exists() ) {
            // creating a new default file automatically loads the config
            return ($this->_create_new_default_cfg_file());
        }

        try {
            $this->config = XML2Array::createArray($config_file->get_contents());
        }
        catch (\Exception $e)  {
            $this->InternalMessages->log(__METHOD__,'XML2Array error '.$e->get_message(),'error');
            return FALSE;
        }
        $this->_fix_single_profile_cfg();
        $this->cfgloaded = TRUE;
        return TRUE;
    }

    /**
    * loads $this->config with contents extracted from XML configuration file.
    * In case the configuration file does not exist, it creates a new default one
    *
    * It takes config from $newcfg and merges all of it into $oldcfg
    *
    * @param  $oldcfg full array with existing configuration
    * @param  $newcfg new array with either full or partial configuration
    *
    * @return $oldcfg Array with any changes and new fields from $newcfg
    */
    protected function _merge_config($oldcfg, $newcfg)
    {
        foreach($oldcfg as $old_key => $old_value)
        {
            if(array_key_exists($old_key, $oldcfg)
            && is_array($old_value)
            && array_key_exists($old_key, $newcfg)
            && is_array($newcfg[$old_key])
            ) {
                $oldcfg[$old_key] = $this->_merge_config($oldcfg[$old_key], $newcfg[$old_key]);

            } elseif (array_key_exists($old_key, $newcfg)) {
                // we might not have all keys in the new cfg, only changes
                $oldcfg[$old_key] = $newcfg[$old_key];
            }
        }
    return $oldcfg;
    }
}

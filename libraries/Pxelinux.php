<?php

/**
* Remote Boot Pxelinux management class.
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

clearos_load_library('base/File');
clearos_load_library('base/Folder');

clearos_load_library('remote_boot/Messages');

include_once(dirname(__FILE__) . '/Constants.php');
////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////

class Pxelinux
{
    ////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ////////////////////////////////////////////////////////////////////

    protected $profilecfg = array();
    protected $globalcfg = array();
    protected $InternalMessages;

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Remote Boot Pxelinux constructor
    *
    * @param Messages $InternalMessages
    *
    * @return void
    */

    public function __construct(Messages $InternalMessages)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->InternalMessages = $InternalMessages;
    }

    /**
    * set $this->globalcfg with given global configuration array
    *
    * @param $globalcfg array of global config
    *
    * @return void
    */

    public function set_pxe_global_cfg($globalcfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->globalcfg = $globalcfg;

        return;
    }

    /**
    * set $this->profilecfg with given profile configuration array
    *
    * @param $profilecfg array of a single profile config
    *
    * @return void
    */

    public function set_pxe_profile_cfg($profilecfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->profilecfg = $profilecfg;

        return;
    }

    /**
    * Copies the system pxelinux bootloader to the tftp root folder
    *
    * @return TRUE in case of success, FALSE in case of failure
    */

    public function copy_bootloader_to_tftp_root()
    {
        clearos_profile(__METHOD__, __LINE__);

        // we don't need to create it again if it already exists
        $current_bootloader = new File($this->globalcfg['pxelinux']['bootloader']);
        if( $current_bootloader->exists() ) {

            return TRUE;
        }

        // create file object pointing to pxelinux file provided by syslinux package
        $default_bootloader = new File(RB_PXE_BOOTLOADER_SYS_FILE);
        if( !$default_bootloader->exists() ) {
            $this->InternalMessages->log(__METHOD__,'File not found '.$default_bootloader->get_filename());

            return FALSE;
        }

        // tftp root path must already exist
        $tftp_root = new Folder($this->globalcfg['folders']['tftp']['name']);
        if( !$tftp_root->exists() ) {
            $this->InternalMessages->log(__METHOD__,'Folder not found '.$tftp_root->get_folder_name());

            return FALSE;
        }

        // then we copy the file to its destination
        try{
            $default_bootloader->copy_to($current_bootloader->get_filename());
        } catch (\Exception $e) {
            $this->InternalMessages->log(__METHOD__,'Error copying file from '.
            $default_bootloader->get_filename().' to '.
            $current_bootloader->get_filename(),'error');

            return FALSE;
        }

        $this->InternalMessages->log(__METHOD__,'File created '.$current_bootloader->get_filename());

        return TRUE;
    }

    /**
    * Executes the following operations on the pxelinux cfg file
    *  - create
    *  - remove
    *
    * @param  string   $action
    * @return TRUE in case of success, FALSE in case of failure
    */

    public function sync_pxecfg($action)
    {
        clearos_profile(__METHOD__, __LINE__);

        switch( $action ){
            case 'create':
                return $this->_write_config_file();
            case 'remove':
                return $this->_remove_config_file();
        }
        return FALSE;
    }


    ////////////////////////////////////////////////////////////////////
    // P R I V A T E    M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * generate and write a file based on current profile config properties
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _write_config_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        $array_pxe_cfg = $this->_generate_pxe_config();
        return $this->_set_config_file_contents($array_pxe_cfg);
    }

    /**
    * remove a pxelinux cfg file based on current profile config properties
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _remove_config_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->profilecfg['pxelinux']['cfgfile']);

        if( $file->exists() ) {
            try{
                $file->delete();
                $this->InternalMessages->log(__METHOD__,'File deleted '.$file->get_filename());
            } catch (\Exception $e) {
                $this->InternalMessages->log(__METHOD__,'Error deleting file '.$file->get_filename(),'error');

                return FALSE;
            }
        }

        return TRUE;
    }

    ////////////////////////////////////////////////////////////////////
    // A U X I L I A R Y   P R I V A T E    M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Writes the contents of a file based on an array input
    *
    * @param    $array_contents
    *
    * @return   TRUE if successful, FALSE otherwise
    */

    protected function _set_config_file_contents($array_contents)
    {
        clearos_profile(__METHOD__, __LINE__);

        // convert to text and write to temp file
        $pxelinux_cfg_file_contents = implode(RB_LINE_BREAK, $array_contents);

        // if the file already exists, we delete it and create another one
        $pxelinux_cfg_file = new File($this->profilecfg['pxelinux']['cfgfile']);
        if ($pxelinux_cfg_file->exists()) {
            $this->InternalMessages->log(__METHOD__,'Deleting file '.$pxelinux_cfg_file->get_filename());
            $pxelinux_cfg_file->delete();
        }
        try {
            $pxelinux_cfg_file->create(RB_DEF_SYSUSER,RB_DEF_SYSGROUP,RB_DEF_FILE_PERMS);
            $this->InternalMessages->log(__METHOD__,'File created '.$pxelinux_cfg_file->get_filename());
            $pxelinux_cfg_file->add_lines($pxelinux_cfg_file_contents);
            $this->InternalMessages->log(__METHOD__,'Added contents to file '.$pxelinux_cfg_file->get_filename());
        } catch (\Exception $e) {
            $this->InternalMessages->log(__METHOD__,'Failed to create or add contents to file'.
            $pxelinux_cfg_file->get_filename(),'error');
            return FALSE;
        }
        return TRUE;
    }


    /**
    * Generates pxe config array based on $this->profilecfg. Uses the following parameters
    * ( 'kernel'  => string,
    *   'initrd'  => string,
    *   'kparams' => string  )
    * @return array with pxelinux configuration
    */
    protected function _generate_pxe_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $array_contents = array( 'default linux'
        ,'  prompt 2'
        ,'  timeout 1'
        ,'  label linux'
        ,'  kernel '.$this->profilecfg['pxelinux']['kernel']
        ,'  initrd '.$this->profilecfg['pxelinux']['initrd']
        ,'  append '.$this->profilecfg['pxelinux']['kparams']
        ,'' );

        return $array_contents;
    }

}

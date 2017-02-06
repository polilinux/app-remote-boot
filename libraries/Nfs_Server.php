<?php

/**
* Remote Boot NFS service and network exports management class
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
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File   as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell  as Shell;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');

clearos_load_library('remote_boot/Messages');

include_once(dirname(__FILE__) . '/Constants.php');
////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////

class Nfs_Server extends Daemon
{
    ////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;

    protected $InternalMessages;

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Remote Boot Exports constructor
    * it supplies the nfs-server initscript name to the Daemon Parent
    * (later used as argument for systemctl <operation> nfs-server calls)
    *
    * this constructor expects a configlet nfs-server.php to exist
    *
    * @param Messages $InternalMessages
    *
    * @return void
    */

    public function __construct(Messages $InternalMessages)
    {
        clearos_profile(__METHOD__, __LINE__);

        // The real package name is nfs-utils, but nfs-server is the initscript
        parent::__construct('nfs-server');

        $this->InternalMessages = $InternalMessages;
    }

    /**
    * get nfs status uses systemctl to get nfs status
    *
    * we should probably rely on service status from the Daemon class, but nfs-server depends
    * on several processes (portmapper, nfsd, mountd). Multiprocess option from Daemon did class not work well.
    *
    * @return TRUE if status is successfully acquired from systemctl, FALSE in case of errors
    */

    public function get_nfs_status() {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        try {
            $superuser = TRUE;
            // systemctl returns error codes when the services are dead... we have to
            // ignore that in order to parse its output from stdout
            $options['validate_exit_code'] = FALSE;
            $shell->execute(self::COMMAND_SYSTEMCTL,'status nfs-server nfs-mountd nfs-idmapd',$superuser,$options);
        } catch (\Exception $e) {
            $this->InternalMessages->log(__METHOD__,$e->get_message());
            return FALSE;
        }
        $output_array = $shell->get_output();
        $input_text = implode(RB_LINE_BREAK, $output_array);

        preg_match_all("/(nfs-.*\.service).*\n.*Loaded.*\n.*Active: (\w+\s\(\w+\)) /", $input_text, $matches);
        if (count($matches) === 3) {
            $nfsserver = $matches[1][0].': '.$matches[2][0].RB_LINE_BREAK;
            $nfsmountd = $matches[1][1].': '.$matches[2][1].RB_LINE_BREAK;
            $nfsidmapd = $matches[1][2].': '.$matches[2][2].RB_LINE_BREAK;
            $this->InternalMessages->log(__METHOD__,$nfsserver);
            $this->InternalMessages->log(__METHOD__,$nfsmountd);
            $this->InternalMessages->log(__METHOD__,$nfsidmapd);
            return TRUE;
        }

        $this->InternalMessages->log(__METHOD__,'could not get nfs status');

        return FALSE;

    }

    /**
    * create exports file based on profile cfg
    *
    * @param  $profilecfg array containing a single profile configuration
    *
    * @return TRUE if successful, FALSE otherwise
    */

    public function create_profile_exports( $profilecfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_create_profile_exports_file($profilecfg);
    }

    /**
    * remove exports file corresponding to profile cfg
    *
    * @param  $profilecfg array containing a single profile configuration
    *
    * @return TRUE if successful, FALSE otherwise
    */

    public function remove_profile_exports( $profilecfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_remove_profile_exports_file($profilecfg);
    }

    ////////////////////////////////////////////////////////////////////
    // P R I V A T E    M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * remove profile exports cfg based on profile name (taken from $profilecfg)
    *
    * @param  $profilecfg array containing a single profile configuration
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _remove_profile_exports_file($profilecfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        // if the file already exists, we delete it and create another one
        $filename = RB_NFS_EXPORTS_PATH.'/'.RB_NFS_EXPORTS_PREFIX.$profilecfg['name'].RB_NFS_EXPORTS_SUFFIX;
        $profile_exports_file = new File($filename);
        if ($profile_exports_file->exists()) {
            try{
                $profile_exports_file->delete();
                $this->InternalMessages->log(__METHOD__,'File deleted '.$profile_exports_file->get_filename());
            } catch (\Exception $e) {
                $this->InternalMessages->log(__METHOD__,'Could not delete file '.$profile_exports_file->get_filename());
                return FALSE;
            }

        }
        return TRUE;
    }

    /**
    * create profile exports cfg based on profile config taken from $profilecfg:
    * iterates through all folders from the profile configuration and creates
    * export entries for all folders marked as enabled
    *
    * @param  $profilecfg array containing a single profile configuration
    *
    * @return TRUE if successful, FALSE otherwise
    */

    protected function _create_profile_exports_file($profilecfg)
    {
        clearos_profile(__METHOD__, __LINE__);

        // go through all folders and include only the ones that are enabled:
        $exports_entries = array();

        foreach($profilecfg['folders'] as $folder => $details) {
            if($details['export']['enabled'] === '1') {
                // changing 0.0.0.0/0 case to *
                if ($profilecfg['network_cidr'] === '0.0.0.0/0') {
                    $profilecfg['network_cidr'] = '*';
                }

                $new_entry = $details['name'].' '.$profilecfg['network_cidr'].'('.$details['export']['options'].')';
                $exports_entries[] = $new_entry;

                $this->InternalMessages->log(__METHOD__,'New export entry '.$new_entry);
            }
        }
        $exports_entries[] = '';
        $exports_contents = implode(RB_LINE_BREAK,$exports_entries);

        // if the file already exists, we delete it and create another one
        $filename = RB_NFS_EXPORTS_PATH.'/'.RB_NFS_EXPORTS_PREFIX.$profilecfg['name'].RB_NFS_EXPORTS_SUFFIX;
        $profile_exports_file = new File($filename);
        if ($profile_exports_file->exists()) {
            $this->InternalMessages->log(__METHOD__,'Removing file '.$profile_exports_file->get_filename());
            $profile_exports_file->delete();
        }
        try {

            $profile_exports_file->create(RB_DEF_SYSUSER,RB_DEF_SYSGROUP,RB_DEF_FILE_PERMS);
            $this->InternalMessages->log(__METHOD__,'File created '.$profile_exports_file->get_filename());
            $profile_exports_file->add_lines($exports_contents);
            $this->InternalMessages->log(__METHOD__,'Added contents to file '.$profile_exports_file->get_filename());
        } catch (\Exception $e) {
            $this->InternalMessages->log(__METHOD__,'Failed to create or add contents to file'.
            $profile_exports_file->get_filename(),'error');
            return FALSE;
        }
        return TRUE;
    }

    /**
    * uses exportfs -ra to reload all exports from files
    *
    * @return TRUE if successful, FALSE otherwise
    */

    public function reload_all_exports()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $shell = new Shell();
            $shell->execute('/usr/sbin/exportfs','-ra',TRUE);
            $this->InternalMessages->log(__METHOD__,'Exports reloaded');
        } catch (\Exception $e) {
            $this->InternalMessages->log(__METHOD__,'Exports reload failure');
            return FALSE;
        }
        return TRUE;
    }

}

<?php
/**
* Remote Boot main controller
*
* @category   apps
* @package    remote_boot
* @subpackage controllers
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
// C L A S S
////////////////////////////////////////////////////////////////////////


class Remote_boot extends ClearOS_Controller
{

    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * main remote_boot controller
    *
    * calls profile and global_settings controllers
    *
    * @return void
    */

    public function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load libraries
        //---------------

        $this->lang->load('remote_boot');
        $this->load->library('session');
        $this->load->library('remote_boot/Remote_Boot');

        // Load views
        //-----------

        $views = array('remote_boot/global_settings', 'remote_boot/profile');

        $this->page->view_controllers($views, lang('base_settings'));
    }

}

<?php
/**
 * Definition of eZCMISModuleTest class
 *
 * Created on: <1-Jun-2009 20:59:01 vd>
 *
 * COPYRIGHT NOTICE: Copyright (C) 2001-2009 NXC AS
 * SOFTWARE LICENSE: GNU General Public License v2.0
 * NOTICE: >
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of version 2.0  of the GNU General
 *   Public License as published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of version 2.0 of the GNU General
 *   Public License along with this program; if not, write to the Free
 *   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *   MA 02110-1301, USA.
 */

/**
 * Module for tests
 *
 * @file ezcmismoduletest.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistesthandler.php' );

class eZCMISModuleTest extends eZCMISModuleBase
{
    /**
     * Processes GET methods
     */
    protected function processGET()
    {
        $test = new eZCMISTestHandler( $this->Params );

        return $test->process();
    }

    /**
     * @reimp
     */
    public function process()
    {
        return $this->processByHTTPMethod( array( 'GET' => 'processGET' ) );
    }
}
?>

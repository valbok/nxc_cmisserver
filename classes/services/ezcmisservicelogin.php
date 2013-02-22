<?php
/**
 * Definition of eZCMISServiceLogin class
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
 * Logs in to eZ Publish
 *
 * @file ezcmisservicelogin.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );

class eZCMISServiceLogin extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'u', null, true );
        $this->addField( 'pw', '', false );
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $userLogin = $this->getField( 'u' )->getValue();
        $userPassword = $this->getField( 'pw' )->getValue();

        eZCMIS::login( $userLogin, $userPassword );

        $doc = eZCMISAtomTools::createDocument();

        $ticket = $doc->createElement( 'ticket', session_id() );
        $doc->appendChild( $ticket );

        return $doc->saveXML();
    }
}
?>

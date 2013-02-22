<?php
/**
 * Definition of eZCMISTestType class
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
 * Tests fetching of a type
 *
 * @file ezcmistesttype.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestbase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );

class eZCMISTestType extends eZCMISTestBase
{
    /**
     * @reimp
     */
    protected function test()
    {
        $result = $this->httpRequest( $this->EndPoint, $this->User, $this->Password );

        $this->checkCode( '200', $result->code, 'GET', $this->EndPoint, $result->response );
        $this->checkResponse( $result->response );

        $info = eZCMISAtomTools::processXML( $result->response, '/app:service/app:workspace/cmis:repositoryInfo' );

        $rootFolderId = isset( $info[0] ) ? (string) eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:rootFolderId' ) : false;

        if ( !$rootFolderId )
        {
            $this->addMessage( ezpI18n::tr( 'cmis', 'Could not fetch "%name%"', null, array( '%name%' => 'rootFolderId' ) ) );
            $this->addMessage( $result->response );
            $this->throwError();
        }

        $result = $this->httpRequest( $rootFolderId, $this->User, $this->Password );

        $this->checkCode( '200', $result->code, 'GET', $rootFolderId, $result->response );
        $this->checkResponse( $result->response );

        $link = eZCMISAtomTools::processXML( $result->response, '/atom:entry/atom:link[@rel="type"]' );
        $typeLink = isset( $link[0]['href'] ) ? $link[0]['href'] : false;

        if ( !$typeLink )
        {
            $this->addMessage( ezpI18n::tr( 'cmis', 'Could not fetch link with "%attribute%" attribute', null, array( '%attribute%' => 'type' ) ) );
            $this->addMessage( $result->response );
            $this->throwError();
        }

        $result = $this->httpRequest( $typeLink, $this->User, $this->Password, array(), 'GET', array() );

        $this->checkCode( '200', $result->code, 'GET', $typeLink, $result->response );
        $this->checkResponse( $result->response );

        $entry = eZCMISAtomTools::processXML( $result->response, '/atom:entry' );
        $title = eZCMISAtomTools::getValue( $entry[0], 'title' );

        if ( !$title )
        {
            $this->addMessage( ezpI18n::tr( 'cmis', 'Could not fetch "%name%"', null, array( '%name%' => 'title' ) ) );
            $this->addMessage( $result->response );
            $this->throwError();
        }
    }
}
?>
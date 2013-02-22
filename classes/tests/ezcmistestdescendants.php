<?php
/**
 * Definition of eZCMISTestDescendants class
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
 * Tests fetching of descendants
 *
 * @file ezcmistestdescendants.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestbase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );

class eZCMISTestDescendants extends eZCMISTestBase
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

        $repositoryId = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:repositoryId' );
        $repositoryName = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:repositoryName' );
        $repositoryDescription = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:repositoryDescription' );
        $vendorName = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:vendorName' );
        $productName = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:productName' );
        $productVersion = eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:productVersion' );
        $rootFolderId = (string) eZCMISAtomTools::getXMLvalue( $info[0], 'cmis:rootFolderId' );

        $entry = eZCMISAtomTools::processXML( $result->response, '/atom:entry' );
        $descendantslinkDom = eZCMISAtomTools::processXML( $result->response, '/app:service/app:workspace/app:collection[@cmis:collectionType="rootdescendants"]' );
        $descendantsLink = isset( $descendantslinkDom[0]['href'] ) ? (string) $descendantslinkDom[0]['href'] : false;

        if ( !$descendantsLink )
        {
            $this->addMessage( ezpI18n::tr( 'cmis', 'Could not fetch "%collection%" collection', null, array( 'rootdescendants' ) ) );
            $this->addMessage( $result->response );
            $this->throwError();
        }

        $result = $this->httpRequest( $descendantsLink, $this->User, $this->Password );

        $this->checkCode( '200', $result->code, 'GET', $descendantsLink, $result->response );
        $this->checkResponse( $result->response );

        /**
         * Check returned data
         */
        $entry = eZCMISAtomTools::processXML( $result->response, '/atom:feed/atom:entry' );
        $objectId = eZCMISAtomTools::getPropertyValue( $entry[0], 'ObjectId' );

        if ( !$objectId )
        {
            $this->addMessage( ezpI18n::tr( 'cmis', 'Could not fetch "ObjectId"' ) );
            $this->addMessage( $result->response );
            $this->throwError();
        }
    }
}
?>
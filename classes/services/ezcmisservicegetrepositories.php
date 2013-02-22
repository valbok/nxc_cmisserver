<?php
/**
 * Definition of eZCMISServiceGetRepositories class
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
 * @service getRepositories: Returns a list of CMIS repositories available from this CMIS service endpoint.
 * @file ezcmisservicegetrepository.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );

class eZCMISServiceGetRepositories extends eZCMISServiceGetRepositoryInfo
{
    /**
     * @reimp
     */
    public function processRESTful()
    {
        $repositoryGroupList = self::fetchRepositoryList();

        $doc = eZCMISAtomTools::createDocument();

        // @TODO: should 'service' or 'feed' be there?
        $root = eZCMISAtomTools::createRootNode( $doc, 'service' );
        $doc->appendChild( $root );

        foreach ( $repositoryGroupList as $repository )
        {
            $repositoryInfo = $this->fetchRepositoryInfo( $repository );
            $entry = $doc->createElement( 'entry' );
            $root->appendChild( $entry );
            $element = $doc->createElement( 'cmis:repositoryId', $repositoryInfo['repositoryId'] );
            $entry->appendChild( $element );
            $element = $doc->createElement( 'cmis:repositoryName', $repositoryInfo['repositoryName'] );
            $entry->appendChild( $element );
            $element = $doc->createElement( 'cmis:repositoryURI', eZCMISServiceURL::createURL( 'repository', array( 'repositoryId' => $repositoryInfo['repositoryId'] ) ) );
            $entry->appendChild( $element );
        }

        return $doc->saveXML();
    }
}
?>

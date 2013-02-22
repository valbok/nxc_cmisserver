<?php
/**
 * Definition of eZCMISServiceUpdateProperties class
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
 * @service updateProperties: Updates properties of the specified object.
 * @file ezcmisserviceupdateproperties.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );

class eZCMISServiceUpdateProperties extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'objectId', null, true );
        $this->addField( 'post_data', null, true );
    }

    /**
     * @reimp
     */
    protected function checkFields()
    {
        parent::checkFields();

        $repositoryIdField = $this->getField( 'repositoryId' );
        $repository = new eZCMISServiceGetRepositoryInfo( array( 'repositoryId' => $repositoryIdField->getValue() ) );
        $repositoryId = $repository->getRepositoryId();
        $repositoryIdField->setValue( $repositoryId );

        $rootNode = eZCMISObjectHandler::fetchNode( $repositoryId );

        $objectId = $this->getField( 'objectId' )->getValue();

        $node = eZCMISObjectHandler::fetchNode( $objectId );
        $this->CMISObject = eZCMISObjectHandler::getObject( $node );

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canEdit() )
        {
            eZCMISExceptions::accessDenied();
        }
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();
        $postData = $this->getField( 'post_data' )->getValue();

        $info = eZCMISAtomTools::processXML( $postData, '//atom:entry' );

        $title = (string) eZCMISAtomTools::getValue( $info[0], 'title' );
        $summary = (string) eZCMISAtomTools::getValue( $info[0], 'summary' );

        if ( !$title )
        {
            eZCMISExceptions::isNotProvided( 'title' );
        }

        // Publish new version
        $this->CMISObject->publish();

        $this->CMISObject->setTitle( $title );
        $this->CMISObject->setSummary( $summary );

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );

        eZCMISServiceGetProperties::createPropertyList( $doc, $root, $repositoryId, $this->CMISObject );

        return $doc->saveXML();
    }
}
?>

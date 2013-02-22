<?php
/**
 * Definition of eZCMISServiceCreateFolder class
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
 * @service createFolder: Creates a folder object of the specified type in the specified location.
 * @file ezcmisserviceecreatefolder.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );

class eZCMISServiceCreateFolder extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        // This parameter MUST be specified if the Repository does NOT support the optional unfiling capability
        $this->addField( 'folderId', null, true );
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

        $folderId = $this->getField( 'folderId' )->getValue();

        $node = eZCMISObjectHandler::fetchNode( $folderId );
        $cmisObject = eZCMISObjectHandler::getObject( $node );

        if ( !$cmisObject or !eZCMIS::isChild( $rootNode, $node ) )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canCreate() )
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
        $folderId = $this->getField( 'folderId' )->getValue();
        $postData = $this->getField( 'post_data' )->getValue();

        $info = eZCMISAtomTools::processXML( $postData, '/atom:entry' );

        $typeId = eZCMISAtomTools::getPropertyObjectTypeId( $info[0] );
        $title = (string) eZCMISAtomTools::getValue( $info[0], 'title' );
        $summary = (string) eZCMISAtomTools::getValue( $info[0], 'summary' );

        if ( !$typeId )
        {
            eZCMISExceptions::isNotProvided( 'ObjectTypeId' );
        }

        // Fetch real typeId instead of alias
        $typeId = eZCMISTypeHandler::getRealTypeId( $typeId );

        // Check if the typeId is an Object-Type whose baseType is 'Folder'
        $baseType = eZCMISTypeHandler::getBaseTypeByTypeId( $typeId );
        if ( !$baseType or !eZCMISTypeHandler::isFolder( $baseType ) )
        {
            throw new eZCMISConstraintViolationException( ezpI18n::tr( 'cmis', "The typeId ('%type%') is not an Object-Type whose baseType is 'Folder'", null, array( '%type%' => $typeId ) ) );
        }

        if ( !$title )
        {
            eZCMISExceptions::isNotProvided( 'title' );
        }

        $newObject = eZCMISObjectHandler::createNew( eZCMISTypeHandler::getClassIdByTypeId( $typeId ), $folderId );
        if ( !$newObject )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'Could not create new object' ) );
        }

        $newObject->setTitle( $title );
        $newObject->setSummary( $summary );

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );

        eZCMISServiceGetProperties::createPropertyList( $doc, $root, $repositoryId, $newObject );

        return $doc->saveXML();
    }
}
?>
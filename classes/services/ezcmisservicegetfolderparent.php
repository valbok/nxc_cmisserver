<?php
/**
 * Definition of getFolderParent class
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
 * @service getFolderParent: Gets the parent folder object for the specified folder object.
 * @file ezcmisserviceegetfolderparent.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetchildren.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetFolderParent extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'folderId', null, true );

        /**
         * Repositories SHOULD return only the properties specified in the property filter.
         * Valid values for this parameter are:
         *     Not set: The set of properties to be returned SHALL be determined by the repository.
         *     "[queryName1], [queryName2], ...": The properties explicitly listed SHALL be returned.
         *     Note: The [queryName] tokens in the above expression SHALL be valid \u201cqueryName\u201d values
         *     as defined in the Object-types for the Objects whose properties are being returned.
         *     *: All properties SHALL be returned for all objects.
         *
         * @TODO: Implement
         */
        $this->addField( 'filter', false, false );

        /**
         * If false, return only the immediate parent of the folder.
         * If true, return an ordered list of all ancestor folders from the specified folder to the root folder. Default=False
         *
         * @note: It is not approved standard.
         *        There is a question about how to get parent list for an object?
         *        (This service is for folders only, to get parent of documents need to use getObjectParents service
         *         but it is used to fetch all assigments, not full parent list)
         */
        $this->addField( 'returnToRoot', 'false', false );

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
        $this->CMISObject = eZCMISObjectHandler::getObject( $node );

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) or !$this->CMISObject->isFolder() )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canRead() )
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

        $node = $this->CMISObject->getNode();

        $returnToRoot = strtolower( $this->getField( 'returnToRoot' )->getValue() ) == 'true';

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'feed' );
        $doc->appendChild( $root );

        eZCMISServiceGetChildren::createHeader( $doc, $root, $this->CMISObject, array( 'repositoryId' => $repositoryId,
                                                                                       'suffix' => 'Parent' ) );

        if ( $folderId != $repositoryId )
        {
            $currentNode = $node;
            do
            {
                $parentNode = $currentNode->fetchParent();
                $cmisObjectParent = eZCMISObjectHandler::getObject( $parentNode );

                if ( !$cmisObjectParent )
                {
                    break;
                }

                $entry = $doc->createElement( 'entry' );
                $root->appendChild( $entry );

                eZCMISServiceGetProperties::createPropertyList( $doc, $entry, $repositoryId, $cmisObjectParent );
                $currentNode = $parentNode;
            }
            while ( $returnToRoot and $cmisObjectParent->getObjectId() != $repositoryId );
        }

        return $doc->saveXML();
    }
}
?>

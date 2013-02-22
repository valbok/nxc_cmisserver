<?php
/**
 * Definition of eZCMISServiceDeleteTree class
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
 * @service deleteTree: Deletes the specified folder object and all of its child- and descendant-objects.
 * @file ezcmisserviceedeletetree.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceDeleteTree extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'folderId', null, true );

        /**
         * If TRUE, then the repository SHOULD continue attempting to
         * perform this operation even if deletion of a child- or descendant-object in the specified folder
         * cannot be deleted.
         * If FALSE (default), then the repository SHOULD abort this method when it fails to delete a
         * single child- or descendant-object.
         *
         * Currently it is not used
         * and means it will always be 'true' due to difficults to determine problems while nodes are deleted.
         */
        $this->addField( 'continueOnFailure', false, false );

        /**
         * An enumeration specifying how the repository SHALL process file-able
         * child- or descendant-objects. Valid values are:
         *     unfile: Unfile all fileable objects.
         *     deletesinglefiled: Delete all fileable non-folder objects whose only parent-folders are in
         *                        the current folder tree. Unfile all other fileable non-folder objects from the current folder tree.
         *     delete (default): Delete all fileable objects.
         *
         * Currently it is not used
         * and means it will always be 'delete' due to unfiling is not supported.
         */
        $this->addField( 'unfileObjects', false, false );
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

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        // If the object is the Root Folder,
        if ( $repositoryId == $folderId )
        {
           throw new eZCMISOperationNotSupportedException( ezpI18n::tr( 'cmis', 'Root folder cannot be removed' ) );
        }

        if ( !$node->canRemove() )
        {
            eZCMISExceptions::accessDenied();
        }
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $node = $this->CMISObject->getNode();

        eZContentOperationCollection::deleteObject( array( $node->attribute( 'node_id' ) ) );

        return false;
    }
}
?>
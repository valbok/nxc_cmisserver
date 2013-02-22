<?php
/**
 * Definition of eZCMISServiceDeleteContentStream class
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
 * @service deleteContentStream: Deletes the content stream for the specified Document object.
 * @file ezcmisserviceedeleteobject.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceDeleteContentStream extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'documentId', null, true );
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

        $objectId = $this->getField( 'documentId' )->getValue();

        $node = eZCMISObjectHandler::fetchNode( $objectId );
        $this->CMISObject = eZCMISObjectHandler::getObject( $node );

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) or !$this->CMISObject->isDocument() )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canEdit() )
        {
            eZCMISExceptions::accessDenied();
        }

        // If content stream is required it can not be deleted
        if ( $this->CMISObject->isContentStreamRequired() )
        {
            eZCMISExceptions::contentStreamIsRequired();
        }
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        if ( $this->CMISObject->hasContent() and $this->CMISObject->isContentStreamAllowed() )
        {
            // Publish new version
            $this->CMISObject->publish();
            // Delete content for recently published version
            $this->CMISObject->deleteContentStream();
        }

        return false;
    }
}
?>

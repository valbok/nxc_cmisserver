<?php
/**
 * Definition of eZCMISServiceSetContentStream class
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
 * @service setContentStream: Sets the content stream for the specified Document object.
 * @file ezcmisservicesetcontentstream.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );

class eZCMISServiceSetContentStream extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'documentId', null, true );
        $this->addField( 'post_data', null, true );

        /**
         * The CMIS base object type definitions include an opaque string \u201cChangeToken\u201d property that a
         * Repository MAY use for optimistic locking and/or concurrency checking to ensure that user updates do not conflict.
         *
         * @TODO: Implement
         */
        $this->addField( 'changeToken', false, false );

        /**
         * If TRUE (default), then the Repository SHALL replace the existing
         * content stream for the object (if any) with the input contentStream.
         * If FALSE, then the Repository SHALL only set the input contentStream for the object if the
         * object currently does not have a content-stream.
         */
        $this->addField( 'overwriteFlag', true, false );
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

        $documentId = $this->getField( 'documentId' )->getValue();

        $node = eZCMISObjectHandler::fetchNode( $documentId );
        $this->CMISObject = eZCMISObjectHandler::getObject( $node );

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) or !$this->CMISObject->isDocument() )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canEdit() )
        {
            eZCMISExceptions::accessDenied();
        }

        if ( $this->CMISObject->isContentStreamNotAllowed() )
        {
            eZCMISExceptions::contentStreamIsNotSupported();
        }
    }

    /**
     * Processes by http method
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();
        $documentId = $this->getField( 'documentId' )->getValue();
        $overwriteFlag = $this->getField( 'overwriteFlag' )->getValue();
        $postData = $this->getField( 'post_data' )->getValue();

        $contentType = isset( $_SERVER['CONTENT_TYPE'] ) ? $_SERVER['CONTENT_TYPE'] : 'application/octet-stream';

        if ( !$overwriteFlag and $this->CMISObject->hasContent() )
        {
            throw new eZCMISContentAlreadyExistsException( ezpI18n::tr( 'cmis', 'Content already exists' ) );
        }

        if ( !strlen( $postData ) )
        {
            eZCMISExceptions::isNotProvided( 'Content' );
        }

        // Publish new version
        $this->CMISObject->publish();

        $this->CMISObject->setContentStream( $postData, $contentType );

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );

        eZCMISServiceGetProperties::createPropertyList( $doc, $root, $repositoryId, $this->CMISObject );

        return $doc->saveXML();
    }
}
?>
<?php
/**
 * Definition of eZCMISServiceGetTypeDescendants class
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
 * @service getTypeDescendants: Returns the set of descendant Object-Types defined for the Repository under the specified Type.
 * @file ezcmisservicegettypedescendants.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisserviceurl.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );

class eZCMISServiceGetTypeDescendants extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );

        /**
         * The typeId of an Object-Type specified in the Repository.
         * If specified, then the Repository SHALL return only the specified Object-Type AND all of its descendant types.
         * If not specified, then the Repository SHALL return all types.
         */
        $this->addField( 'typeId', null, false );

        /**
         * The number of levels of depth in the type hierarchy from which to return results.
         * Valid values are:
         *     1 (default): Return only types that are children of the type.
         *     <Integer value greater than 1>: Return only types that are children of the type and descendants up to <value> levels deep.
         *     -1: Return ALL descendant types at all depth levels in the CMIS hierarchy.
         */
        $this->addField( 'depth', 1, false );

        /**
         * If TRUE, then the Repository SHALL return the property definitions for each Object-Type returned.
         * If False (default), the Repository SHALL return only the attributes for each Object-Type.
         */
        $this->addField( 'includePropertyDefinitions', 'false', false );

        /**
         * @TODO: Implement paging
         */
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
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();
        $typeId = $this->getField( 'typeId' )->getValue();
        $depth = $this->getField( 'depth' )->getValue();
        $includePropertyDefinitions = strtolower( $this->getField( 'depth' )->getValue() ) == 'true';

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'feed' );
        $doc->appendChild( $root );

        $author = $doc->createElement( 'author' );
        $root->appendChild( $author );
        $name = $doc->createElement( 'name', 'admin' );
        $author->appendChild( $name );

        $generator = $doc->createElement( 'generator', eZCMIS::VENDOR );
        $generator->setAttribute( 'version', eZPublishSDK::version() );
        $root->appendChild( $generator );

        $id = $doc->createElement( 'id', $typeId ? 'type-' . $typeId : 'types-all' );
        $root->appendChild( $id );

        eZCMISServiceGetProperties::createLink( $doc, $root, 'service', eZCMISServiceURL::createURL( 'repository', array( 'repositoryId' => $repositoryId ) ) );
        eZCMISServiceGetProperties::createLink( $doc, $root, 'self', eZCMISServiceURL::getRequestedURI() );
        // @TODO: Implement paging
        eZCMISServiceGetProperties::createLink( $doc, $root, 'first', eZCMISServiceURL::getRequestedURI() );
        eZCMISServiceGetProperties::createLink( $doc, $root, 'last', eZCMISServiceURL::getRequestedURI() );

        $title = $doc->createElement( 'title', $typeId ? 'Type ' . $typeId : 'Base Types' );
        $root->appendChild( $title );

        $types = eZCMISTypeHandler::getTypeDefinition( $typeId, $depth, $includePropertyDefinitions );

        $numItems = $doc->createElement( 'cmisra:numItems', count( $types ) );
        $root->appendChild( $numItems );

        foreach ( $types as $type )
        {
            $entry = $doc->createElement( 'entry' );
            $root->appendChild( $entry );
            self::createElement( $doc, $entry, $type, $repositoryId );
        }

        return $doc->saveXML();
    }

    /**
     * Creates CMIS type element
     */
    public static function createElement( DOMDocument $doc, DOMElement $entry, $type, $repositoryId )
    {
        if ( !isset( $type['id'] ) )
        {
            return false;
        }

        $author = $doc->createElement( 'author' );
        $entry->appendChild( $author );
        $name = $doc->createElement( 'name', 'admin' );
        $author->appendChild( $name );

        $typeId = $type['id'];
        $content = $doc->createElement( 'content', $typeId );
        $entry->appendChild( $content );

        $id = $doc->createElement( 'id', 'type-' . $typeId );
        $entry->appendChild( $id );

        eZCMISServiceGetProperties::createLink( $doc, $entry, 'self', eZCMISServiceURL::createURL( 'type', array( 'repositoryId' => $repositoryId, 'typeId' => $typeId ) ) );
        eZCMISServiceGetProperties::createLink( $doc, $entry, 'describedby', eZCMISServiceURL::createURL( 'type', array( 'repositoryId' => $repositoryId, 'typeId' => $typeId ) ) );

        if ( isset( $type['parentId'] ) )
        {
            eZCMISServiceGetProperties::createLink( $doc, $entry, 'up', eZCMISServiceURL::createURL( 'type', array( 'repositoryId' => $repositoryId, 'typeId' => $type['parentId'] ) ) );
        }

        eZCMISServiceGetProperties::createLink( $doc, $entry, 'down', eZCMISServiceURL::createURL( 'types', array( 'repositoryId' => $repositoryId, 'typeId' => $typeId ) ), 'application/atom+xml;type=feed' );
        eZCMISServiceGetProperties::createLink( $doc, $entry, 'down', eZCMISServiceURL::createURL( 'types', array( 'repositoryId' => $repositoryId, 'typeId' => $typeId ) ), 'application/cmistree+xml' );
        eZCMISServiceGetProperties::createLink( $doc, $entry, 'service', eZCMISServiceURL::createURL( 'repository', array( 'repositoryId' => $repositoryId ) ) );

        $summary = $doc->createElement( 'summary', $type['description'] );
        $entry->appendChild( $summary );

        $title = $doc->createElement( 'title', $type['displayName'] );
        $entry->appendChild( $title );

        $info = eZCMISAtomTools::createElementByArray( $doc, 'type', $type, 'cmisra:' );
        $info->setAttribute( 'cmisra:id', $typeId );
        $info->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );

        if ( eZCMISTypeHandler::isFolder( $type['baseId'] ) )
        {
            $info->setAttribute( 'xsi:type', 'cmis:cmisTypeFolderDefinitionType' );
        }
        elseif ( eZCMISTypeHandler::isDocument( $type['baseId'] ) )
        {
            $info->setAttribute( 'xsi:type', 'cmis:cmisTypeDocumentDefinitionType' );
        }

        if ( $info )
        {
            $entry->appendChild( $info );
        }

        return true;
    }
}
?>

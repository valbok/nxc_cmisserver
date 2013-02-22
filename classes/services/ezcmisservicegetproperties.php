<?php
/**
 * Definition of eZCMISServiceGetProperties class
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
 * @service getProperties: Gets a subset of the properties for an Object.
 * @file ezcmisserviceegetproperties.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisserviceurl.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetProperties extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'objectId', null, true );

        /**
         * If TRUE, then the Repository SHALL return the
         * available actions for each object in the result set. Defaults to FALSE.
         *
         * @TODO: Implement
         */
        $this->addField( 'includeAllowableActions', false, false );

        /**
         * Value indicating what relationships in which the objects returned
         * participate SHALL be returned, if any. Values are:
         * none: No relationships SHALL be returned.
         * source: Only relationships in which the objects returned are the source SHALL be returned.
         * target: Only relationships in which the objects returned are the target SHALL be returned.
         * both: Relationships in which the objects returned are the source or the target SHALL be returned.
         *
         * @TODO: Implement
         */
        $this->addField( 'includeRelationships', 'none', false );

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
         * Include the ACL's associated with the object in the response.
         *
         * @TODO: Implement
         */
        $this->addField( 'includeACLs', false, false );
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

        if ( !$node->canRead() )
        {
            eZCMISExceptions::accessDenied();
        }
    }

    /**
     * Processes by GET http method
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );

        self::createPropertyList( $doc, $root, $repositoryId, $this->CMISObject );

        return $doc->saveXML();
    }

    /**
     * Creates property list for CMIS object
     */
    public static function createPropertyList( DOMDocument $doc, DOMElement $root, $repositoryId, $cmisObject )
    {
        if ( !$cmisObject )
        {
            return false;
        }

        $objectId = $cmisObject->getObjectId();

        $owner = $cmisObject->getCreatedBy();

        $author = $doc->createElement( 'author' );
        $root->appendChild( $author );
        $name = $doc->createElement( 'name', $owner );
        $author->appendChild( $name );

        if ( $cmisObject->isFolder() )
        {
            $content = $doc->createElement( 'content', $objectId );
            $root->appendChild( $content );
        }
        elseif ( $cmisObject->isDocument() )
        {
            $content = $doc->createElement( 'content' );
            $root->appendChild( $content );
            $content->setAttribute( 'type', $cmisObject->getContentStreamMimeType() );
            $content->setAttribute( 'src',  eZCMISServiceURL::createURL( 'content', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );
        }

        $id = $doc->createElement( 'id', $objectId );
        $root->appendChild( $id );

        self::createLink( $doc, $root, 'self', eZCMISServiceURL::createURL( 'node', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );
        self::createLink( $doc, $root, 'edit', eZCMISServiceURL::createURL( 'node', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );
        self::createLink( $doc, $root, 'http://docs.oasis-open.org/ns/cmis/link/200908/allowableactions', eZCMISServiceURL::createURL( 'permissions', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );
        self::createLink( $doc, $root, 'http://docs.oasis-open.org/ns/cmis/link/200908/relationships', eZCMISServiceURL::createURL( 'relations', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );

        if ( $cmisObject->isFolder() )
        {
            self::createLink( $doc, $root, 'up', eZCMISServiceURL::createURL( 'parent', array( 'repositoryId' => $repositoryId, 'folderId' => $objectId ) ) );
            self::createLink( $doc, $root, 'down', eZCMISServiceURL::createURL( 'children', array( 'repositoryId' => $repositoryId, 'folderId' => $objectId ) ), 'application/atom+xml;type=feed' );
            self::createLink( $doc, $root, 'down', eZCMISServiceURL::createURL( 'descendants', array( 'repositoryId' => $repositoryId, 'folderId' => $objectId ) ), 'application/cmistree+xml' );
            self::createLink( $doc, $root, 'http://docs.oasis-open.org/ns/cmis/link/200908/foldertree', eZCMISServiceURL::createURL( 'foldertree', array( 'repositoryId' => $repositoryId, 'folderId' => $objectId ) ), 'application/cmistree+xml' );
        }
        elseif ( $cmisObject->isDocument() )
        {
            self::createLink( $doc, $root, 'enclosure', eZCMISServiceURL::createURL( 'content', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ), $cmisObject->getContentStreamMimeType() );
            self::createLink( $doc, $root, 'edit-media', eZCMISServiceURL::createURL( 'content', array( 'repositoryId' => $repositoryId, 'documentId' => $objectId ) ), $cmisObject->getContentStreamMimeType() );
            self::createLink( $doc, $root, 'up', eZCMISServiceURL::createURL( 'parents', array( 'repositoryId' => $repositoryId, 'objectId' => $objectId ) ) );
            self::createLink( $doc, $root, 'allversions', eZCMISServiceURL::createURL( 'versions', array( 'repositoryId' => $repositoryId, 'versionSeriesId' => $cmisObject->getVersionSeriesId() ) ) );
        }

        self::createLink( $doc, $root, 'describedby', eZCMISServiceURL::createURL( 'type', array( 'repositoryId' => $repositoryId, 'typeId' => $cmisObject->getObjectTypeId() ) ) );
        self::createLink( $doc, $root, 'service', eZCMISServiceURL::createURL( 'repository', array( 'repositoryId' => $repositoryId ) ) );

        $creationDate = $cmisObject->getCreationDate();
        $modificationDate = $cmisObject->getLastModificationDate();
        $name = $cmisObject->getName();

        $published = $doc->createElement( 'published', $creationDate );
        $root->appendChild( $published );

        $summary = $doc->createElement( 'summary', htmlspecialchars( $cmisObject->getSummary() ) );
        $root->appendChild( $summary );

        $title = $doc->createElement( 'title', htmlspecialchars( $name ) );
        $root->appendChild( $title );

        $updated = $doc->createElement( 'updated', $modificationDate );
        $root->appendChild( $updated );

        $object = $doc->createElement( 'cmisra:object' );
        $root->appendChild( $object );
        $properties = $doc->createElement( 'cmis:properties' );
        $object->appendChild( $properties );

        self::createPropertyItem( $doc, $properties, 'Id', 'objectId', $objectId );
        self::createPropertyItem( $doc, $properties, 'Id', 'baseTypeId', $cmisObject->getBaseType() );
        self::createPropertyItem( $doc, $properties, 'Id', 'objectTypeId', $cmisObject->getObjectTypeId() );
        self::createPropertyItem( $doc, $properties, 'String', 'createdBy', $owner );
        self::createPropertyItem( $doc, $properties, 'DateTime', 'creationDate', $creationDate );
        self::createPropertyItem( $doc, $properties, 'DateTime', 'lastModificationDate', $modificationDate );
        self::createPropertyItem( $doc, $properties, 'String', 'name', $name );
        // @TODO
        self::createPropertyItem( $doc, $properties, 'String', 'changeToken', '' );
        // @TODO: Need to figure out how to define this property
        self::createPropertyItem( $doc, $properties, 'String', 'lastModifiedBy', $owner );

        if ( $cmisObject->isFolder() )
        {
            $parentId = $repositoryId != $objectId ? $cmisObject->getParentId() : '';
            self::createPropertyItem( $doc, $properties, 'Id', 'parentId', $parentId );
            // @TODO
            self::createPropertyItem( $doc, $properties, 'Id', 'allowedChildObjectTypeIds', '' );
        }
        elseif ( $cmisObject->isDocument() )
        {
            self::createPropertyItem( $doc, $properties, 'Boolean', 'isImmutable', $cmisObject->isImmutable() );
            self::createPropertyItem( $doc, $properties, 'Boolean', 'isLatestVersion', $cmisObject->isLatestVersion() );
            self::createPropertyItem( $doc, $properties, 'Boolean', 'isMajorVersion', $cmisObject->isMajorVersion() );
            self::createPropertyItem( $doc, $properties, 'Boolean', 'isLatestMajorVersion', $cmisObject->isLatestMajorVersion() );
            self::createPropertyItem( $doc, $properties, 'String', 'versionLabel', $cmisObject->getVersionLabel() );
            self::createPropertyItem( $doc, $properties, 'Id', 'versionSeriesId', $cmisObject->getVersionSeriesId() );
            self::createPropertyItem( $doc, $properties, 'Boolean', 'isVersionSeriesCheckedOut', $cmisObject->isVersionSeriesCheckedOut() );
            self::createPropertyItem( $doc, $properties, 'String', 'versionSeriesCheckedOutBy', $cmisObject->getVersionSeriesCheckedOutBy() );
            self::createPropertyItem( $doc, $properties, 'Integer', 'contentStreamLength', $cmisObject->getContentStreamLength() );
            self::createPropertyItem( $doc, $properties, 'String', 'contentStreamMimeType', $cmisObject->getContentStreamMimeType() );
            self::createPropertyItem( $doc, $properties, 'String', 'contentStreamFileName', $cmisObject->getContentStreamFileName() );
            self::createPropertyItem( $doc, $properties, 'Id', 'contentStreamId', '' );
            self::createPropertyItem( $doc, $properties, 'String', 'versionSeriesCheckedOutId', $cmisObject->getVersionSeriesCheckedOutId() );
        }

        $edited = $doc->createElement( 'app:edited', $modificationDate );
        $root->appendChild( $edited );

        return true;
    }

    /**
     * Creates a property for CMIS object
     */
    public static function createPropertyItem( DOMDocument $doc, DOMElement $properties, $type, $name, $value, $prefix = 'cmis:'  )
    {
        $element = $doc->createElement( $prefix . 'property' . $type );
        if ( !empty( $value ) )
        {
            $element->appendChild( $doc->createElement( $prefix . 'value', htmlentities( $value ) ) );
        }

        $element->setAttribute( 'propertyDefinitionId', $prefix . $name );
        $properties->appendChild( $element );
    }

    /**
     * Creates link
     */
    public static function createLink( DOMDocument $doc, DOMElement $root, $rel, $href, $type = false )
    {
        $link = $doc->createElement( 'link' );
        $link->setAttribute( 'rel', $rel );

        if ( $type )
        {
            $link->setAttribute( 'type', $type );
        }

        $link->setAttribute( 'href', $href );
        $root->appendChild( $link );
    }
}
?>

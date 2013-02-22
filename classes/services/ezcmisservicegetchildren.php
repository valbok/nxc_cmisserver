<?php
/**
 * Definition of eZCMISServiceGetChildren class
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
 * @service getChildren: Gets the list of child objects contained in the specified folder.
 * @file ezcmisserviceegetproperties.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisserviceurl.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetChildren extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'folderId', null, true );
        $this->addField( 'post_data', null, false );

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
         * This is the maximum number of items that the repository SHALL
         * return in its response. Default is repository-specific.
         */
        $this->addField( 'maxItems', false, false );

        /**
         * This is the number of potential results that the repository SHALL
         * skip/page over before returning any results. Defaults to 0.
         */
        $this->addField( 'skipCount', 0, false );
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

        $limit = $this->getField( 'maxItems' )->getValue();
        $offset = $this->getField( 'skipCount' )->getValue();

        $children = $node->subTree( array( 'ClassFilterType' => 'include',
                                           'ClassFilterArray' => eZCMISTypeHandler::getTypeIdList(),
                                           'Depth' => 1,
                                           'DepthOperator' => 'eq',
                                           'SortBy' => $node->sortArray(),
                                           'Offset' => $offset,
                                           'Limit' => $limit ) );

        // If limit is set check existance of extra items
        $moreChildren = $limit ? $node->subTree( array( 'ClassFilterType' => 'include',
                                                        'ClassFilterArray' => eZCMISTypeHandler::getTypeIdList(),
                                                        'Depth' => 1,
                                                        'DepthOperator' => 'eq',
                                                        'Offset' => $offset + $limit,
                                                        'Limit' => false ) )
                               : false;

        $moreChildrenCount = $moreChildren ? count( $moreChildren ) : 0;
        $childrenCount = $moreChildrenCount ? count( $children ) + $moreChildrenCount : count( $children );

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'feed' );
        $doc->appendChild( $root );

        self::createHeader( $doc, $root, $this->CMISObject, array( 'repositoryId' => $repositoryId,
                                                                   'suffix' => 'Children',
                                                                   'limit' => $limit ? $limit : $childrenCount,
                                                                   'offset' => $offset,
                                                                   'moreChildrenCount' => $moreChildrenCount,
                                                                   'childrenCount' => $childrenCount ) );

        foreach ( $children as $child )
        {
            $cmisChild = eZCMISObjectHandler::getObject( $child );
            if ( !$cmisChild )
            {
                continue;
            }

            $entry = $doc->createElement( 'entry' );
            $root->appendChild( $entry );
            eZCMISServiceGetProperties::createPropertyList( $doc, $entry, $repositoryId, $cmisChild );
        }

        return $doc->saveXML();
    }

    /**
     * Creates XML header
     *
     * @param array $params List of parameters:
     *              repositoryId - Repository id
     *              suffix - A suffix that will be appended to some values
     *              limit - Result set limit
     *              offset - Result set offset
     *              moreChildrenCount - Count of existing children after limit that is not included in result set
     *              service - Service name, it will be used to generate URLs
     */
    public static function createHeader( DOMDocument $doc, DOMElement $root, $cmisObject, $params = array() )
    {
        if ( !$doc or !$root or !$cmisObject or !isset( $params['repositoryId'] ) )
        {
            return;
        }

        $repositoryId = $params['repositoryId'];
        $suffix = isset( $params['suffix'] ) ? $params['suffix'] : false;
        $limit = isset( $params['limit'] ) ? $params['limit'] : 10;
        $offset = ( isset( $params['offset'] ) and $params['offset'] ) ? $params['offset'] : 0;
        $moreChildrenCount = isset( $params['moreChildrenCount'] ) ? $params['moreChildrenCount'] : false;
        $childrenCount = isset( $params['childrenCount'] ) ? $params['childrenCount'] : false;
        $module = isset( $params['module'] ) ? $params['module'] : 'children';

        $owner = $cmisObject->getCreatedBy();

        $author = $doc->createElement( 'author' );
        $root->appendChild( $author );
        $name = $doc->createElement( 'name', $owner );
        $author->appendChild( $name );

        $generator = $doc->createElement( 'generator', eZCMIS::VENDOR );
        $generator->setAttribute( 'version', eZPublishSDK::version() );
        $root->appendChild( $generator );

        // @TODO: Add icon

        $id = $doc->createElement( 'id', $cmisObject->getObjectId() . ( $suffix ? '-' . strtolower( $suffix ) : '' ) );
        $root->appendChild( $id );

        eZCMISServiceGetProperties::createLink( $doc, $root, 'self', eZCMISServiceURL::getRequestedURI() );
        eZCMISServiceGetProperties::createLink( $doc, $root, 'source', eZCMISServiceURL::createURL( 'node', array( 'repositoryId' => $repositoryId, 'objectId' => $cmisObject->getObjectId() ) ) );

        if ( $childrenCount )
        {
            $type = 'application/atom+xml;type=feed';

            // Create 'first' link
            eZCMISServiceGetProperties::createLink( $doc, $root, 'first', eZCMISServiceURL::createURL( $module, array( 'repositoryId' => $repositoryId,
                                                                                                                       'folderId' => $cmisObject->getObjectId(),
                                                                                                                       'skipCount' => 0,
                                                                                                                       'maxItems' => $limit ) ),
                                                    $type );

            // Create 'next' link
            if ( $moreChildrenCount )
            {
                eZCMISServiceGetProperties::createLink( $doc, $root, 'next', eZCMISServiceURL::createURL( $module, array( 'repositoryId' => $repositoryId,
                                                                                                                          'folderId' => $cmisObject->getObjectId(),
                                                                                                                          'skipCount' => $offset + $limit,
                                                                                                                          'maxItems' => $limit ) ),
                                                        $type );

            }

            // Create 'prev' link
            if ( ( $offset - $limit ) >= 0 )
            {
                eZCMISServiceGetProperties::createLink( $doc, $root, 'prev', eZCMISServiceURL::createURL( $module, array( 'repositoryId' => $repositoryId,
                                                                                                                          'folderId' => $cmisObject->getObjectId(),
                                                                                                                          'skipCount' => $offset - $limit,
                                                                                                                          'maxItems' => $limit ) ),
                                                        $type );

            }
        }

        $title = $doc->createElement( 'title', $cmisObject->getName() . ( $suffix ? ' ' . ucfirst( strtolower( $suffix ) ) : '' ) );
        $root->appendChild( $title );

        $modificationDate = $cmisObject->getLastModificationDate();
        $updated = $doc->createElement( 'updated', $modificationDate );
        $root->appendChild( $updated );

        $numItems = $doc->createElement( 'cmisra:numItems', $childrenCount ? $childrenCount : 0 );
        $root->appendChild( $numItems );
    }
}
?>

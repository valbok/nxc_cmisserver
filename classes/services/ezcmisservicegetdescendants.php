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
 * @service getDescendants: Gets the set of descendant objects contained in the specified folder or any of its child-folders.
 * @file ezcmisserviceegetproperties.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetchildren.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetDescendants extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'folderId', null, true );

        /**
         * If TRUE, then the Repository SHALL return the
         * available actions for each object in the result set. Defaults to FALSE.
         *
         * @TODO: Implement
         */
        $this->addField( 'includeAllowableActions', 'true', false );

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
         * The number of levels of depth in the folder hierarchy from which to return results.
         * Valid values are:
         *     2 (default): Return only objects that are children of the folder and their children.
         *     <Integer value greater than 1>: Return only objects that are children of the folder and
         *         descendants up to <value> levels deep.
         *    -1: Return ALL descendant objects at all depth levels in the CMIS hierarchy.
         */
        $this->addField( 'depth', 2, false );
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

        $folderIdField = $this->getField( 'folderId' );

        $node = eZCMISObjectHandler::fetchNode( $folderIdField->getValue() );
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

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'feed' );
        $doc->appendChild( $root );

        eZCMISServiceGetChildren::createHeader( $doc, $root, $this->CMISObject, array( 'repositoryId' => $repositoryId,
                                                                                       'suffix' => 'Descendants' ) );

        $depth = $this->getField( 'depth' )->getValue();

        $params = array( 'ClassFilterType' => 'include',
                         'ClassFilterArray' => eZCMISTypeHandler::getTypeIdList(),
                         'Depth' => ( $depth < 0 ? false : $depth ),
                         'DepthOperator' => 'le',
                         'SortBy' => $node->sortArray(),
                          );

        $children = $node->subTree( $params );

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
}
?>

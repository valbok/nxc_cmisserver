<?php
/**
 * Definition of eZCMISObjectHandler class
 *
 * Created on: <02-Jul-2009 20:59:01 vd>
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
 * Handler for CMIS Objects
 *
 * @file ezcmisobjecthandler.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );

class eZCMISObjectHandler
{
    /**
     * @return eZCMISObjectBase descendant or boolean false if failed
     */
    public static function getObject( $node )
    {
        $baseType = $node ? eZCMISTypeHandler::getCMISClassName( $node->classIdentifier() ) : false;

        if ( !$baseType )
        {
            return false;
        }

        $includeFile = eZExtension::baseDirectory() . '/nxc_cmisserver/classes/objects/ezcmisobject' . $baseType . '.php';
        if ( !file_exists( $includeFile ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "File does not exist: '%file%'", null, array( '%file%' => $includeFile ) ) );
        }

        include_once( $includeFile );

        $class = 'eZCMISObject' . ucfirst( $baseType );
        if ( !class_exists( $class ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class does not exist: '%class%'", null, array( '%class%' => $class ) ) );
        }

        return new $class( $node, $baseType );
    }

    /**
     * Fetches content node by CMIS object id
     *
     * @return eZContentObjectTreeNode
     */
    public static function fetchNode( $id )
    {
        return eZContentObjectTreeNode::fetchByRemoteID( $id );
    }

    /**
     * Creates new eZCMISObjectBase descendant
     *
     * @param string $classIdentifier of class which should be instantiated
     * @param string $parentId remote id of node where new object will be located
     * @param array $params list of attributes which should be updated
     */
    public static function createNew( $classIdentifier, $parentId, $params = array() )
    {
        $parentNode = self::fetchNode( $parentId );
        if ( !$parentNode )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Node ('%node_id%') does not exist", null, array( '%node_id%' => $parentId ) ) );
        }

        $parentNodeId = $parentNode->attribute( 'node_id' );

        $typeList = eZCMISTypeHandler::getTypeIdList();
        if ( !in_array( $classIdentifier, $typeList ) )
        {
            return false;
        }

        $class = eZContentClass::fetchByIdentifier( $classIdentifier );

        if ( !$class )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Could not fetch class by identifier '%class%'", null, array( '%class%' => $classIdentifier ) ) );
        }

        $contentObject = $class->instantiate();

        if ( !$contentObject )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis',  "Could not instatiate content object by class identifier '%class%'", null, array( '%class%' => $classIdentifier ) ) );
        }

        $version = $contentObject->attribute( 'current_version' );
        $objectId = $contentObject->attribute( 'id' );

        if ( count( $params ) )
        {
            $attributeList = $object->contentObjectAttributes();

            foreach ( array_keys( $attributeList ) as $key )
            {
                $result = false;
                $attr = $attributeList[$key];
                foreach ( $params as $attrName => $value )
                {
                    if ( $attr->contentClassAttributeIdentifier() == $attrName )
                    {
                        $result = $value;
                        break;
                    }
                }

                if ( $result )
                {
                    // @TODO: add content correctly
                    $attr->fromString( $result );
                    $attr->sync();
                }
            }
        }

        $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $objectId,
                                                           'contentobject_version' => $version,
                                                           'parent_node' => $parentNodeId,
                                                           'is_main' => 1 ) );
        $nodeAssignment->store();

        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $objectId,
                                                                                     'version' => $version ) );

        $mainNode = $contentObject->mainNode();

        return self::getObject( $mainNode );
    }

}
?>
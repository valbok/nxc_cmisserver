<?php
/**
 * Definition of eZCMISObjectBase class
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
 * Base class for CMIS objects
 *
 * @file ezcmisobjectbase.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );

abstract class eZCMISObjectBase
{
    /**
     * Node for this object
     *
     * @var eZContentObjectTreeNode
     */
    protected $Node = null;

    /**
     * Object type id like 'cmis:file' or 'cmis:image'
     *
     * @var string
     */
    protected $ObjectTypeId = null;

    /**
     * Base type like 'cmis:folder' or 'cmis:document'
     *
     * @var string
     */
    protected $BaseType = null;

    /**
     * Constructor
     *
     * @param eZContentObjectTreeNode
     */
    public function __construct( $node )
    {
        $this->Node = $node;
        $this->ObjectTypeId = eZCMISTypeHandler::getTypeIdByLocalName( $this->getClassIdentifier() );
        $this->BaseType = eZCMISTypeHandler::getBaseTypeByTypeId( $this->ObjectTypeId );
    }

    /**
     * Provides content node
     *
     * @return eZContentObjectTreeNode
     */
    public function getNode()
    {
        return $this->Node;
    }

    /**
     * @return string CMIS Object id
     */
    public function getObjectId()
    {
        return $this->Node ? $this->Node->attribute( 'remote_id' ) : '';
    }

    /**
     * @return bool TRUE if the object is document
     */
    public function isDocument()
    {
       return eZCMISTypeHandler::isDocument( $this->getBaseType() );
    }

    /**
     * @return bool TRUE if the object is folder
     */
    public function isFolder()
    {
       return eZCMISTypeHandler::isFolder( $this->getBaseType() );
    }

    /**
     * @return string Base type
     */
    public function getBaseType()
    {
        return $this->BaseType;
    }

    /**
     * @return string
     */
    public function getObjectTypeId()
    {
        return $this->ObjectTypeId;
    }

    /**
     * @return string Object type id
     */
    public function getClassIdentifier()
    {
        return $this->Node ? $this->Node->classIdentifier() : '';
    }

    /**
     * @return string Owner of object
     */
    public function getCreatedBy()
    {
        if ( !$this->Node )
        {
            return '';
        }

        $object = $this->Node->object();

        $user = eZUser::fetch( $object->attribute( 'owner_id' ) );

        return $user ? $user->attribute( 'login' ) : 'Unknown';
    }

    /**
     * @return string Date of creation
     */
    public function getCreationDate()
    {
        if ( !$this->Node )
        {
            return '';
        }

        $object = $this->Node->object();

        return eZCMISAtomTools::getDate( $object->attribute( 'published' ) );
    }

    /**
     * @return string Last modification date
     */
    public function getLastModificationDate()
    {
        if ( !$this->Node )
        {
            return '';
        }

        $object = $this->Node->object();

        return eZCMISAtomTools::getDate( $object->attribute( 'modified' ) );
    }

    /**
     * @return string Object name
     */
    public function getName()
    {
        return $this->Node ? $this->Node->getName() : '';
    }

    /**
     * @return string Summary
     */
    public function getSummary()
    {
        return '';
    }

    /**
     * Sets object title
     */
    public function setTitle( $title )
    {
        if ( !$this->Node )
        {
            return false;
        }

        $object = $this->Node->object();

        $contentClass = $object->contentClass();
        $objectNamePattern = $contentClass ? $contentClass->ContentObjectName : false;

        if ( !$objectNamePattern )
        {
            return false;
        }

        // Get parts of object's name pattern( like <attr1|attr2>, <attr3> )
        $objectNamePatternPartsPattern = '/<([^>]+)>/U';
        preg_match_all( $objectNamePatternPartsPattern, $objectNamePattern, $objectNamePatternParts );

        if ( !count( $objectNamePatternParts ) or !count( $objectNamePatternParts[1] ) )
        {
            return false;
        }

        $objectNamePatternParts = $objectNamePatternParts[1];

        $dataMap = $object->dataMap();

        if ( !count( $dataMap ) )
        {
            return false;
        }

        // Assign $title to the object's attributes.
        $count = count( $objectNamePatternParts );
        for ( $pos = 0; $pos < $count; $pos++ )
        {
            $attributes = $objectNamePatternParts[$pos];
            $attributes = explode( '|', $attributes );
            foreach ( $attributes as $attribute )
            {
                $contentAttribute = isset( $dataMap[$attribute] ) ? $dataMap[$attribute] : false;
                if ( !$contentAttribute )
                {
                    continue;
                }

                $dataType = $contentAttribute->dataType();
                if ( !$dataType or !$dataType->isSimpleStringInsertionSupported() )
                {
                    continue;
                }

                $result = '';
                $dataType->insertSimpleString( $object, $object->currentVersion(), false, $contentAttribute, $title, $result );
                $contentAttribute->sync();
            }
        }

        $object->setName( $title );
        $this->Node->setName( $title );
        $this->Node->updateSubTreePath();

        return true;
    }

    /**
     * Sets summary
     *
     * @TODO: Implement it
     */
    public function setSummary()
    {
    }

    /**
     * Creates new version of object
     *
     * @return int Version
     */
    public function createNewVersion()
    {
        if ( !$this->Node )
        {
            return false;
        }

        $object = $this->Node->object();

        $contentObjectVersion = $object->createNewVersion();

        return $contentObjectVersion ? $contentObjectVersion->attribute( 'version' ) : false;
    }

    /**
     * Publishes new object version
     *
     * @param int Version that has been published
     */
    public function publish( $version = false )
    {
        if ( !$this->Node )
        {
            return false;
        }

        if ( !$version )
        {
            $version = $this->createNewVersion();
        }

        $object = $this->Node->object();
        $operationResult = $version ? eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ),
                                                                                                'version' => $version ) ) : false;
        $this->updateNode();

        return $operationResult ? true : false;
    }

    /**
     * Updates current node by new
     */
    public function updateNode( $nodeId = false )
    {
        if ( !$nodeId and $this->Node )
        {
            $nodeId = $this->Node->attribute( 'node_id' );
        }

        if ( !$nodeId )
        {
            return false;
        }

        $this->Node = eZContentObjectTreeNode::fetch( $nodeId );

        return true;
    }
}
?>

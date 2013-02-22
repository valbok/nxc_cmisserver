<?php
/**
 * Definition of eZCMISServiceGetRepositoryInfo class
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
 * @service getRepositoryInfo: Returns information about the CMIS repository and the optional capabilities it supports.
 * @file ezcmisservicegetrepositoryinfo.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisserviceurl.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetRepositoryInfo extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
    }

    /**
     * Fetches repository list from ini file
     *
     * @return array Repository list
     */
    protected static function fetchRepositoryList()
    {
        $ini = eZINI::instance( 'repository.ini' );
        // Fetch repository groups
        $repositoryGroupList = $ini->hasVariable( 'RepositorySettings', 'RepositoryList' ) ? $ini->variable( 'RepositorySettings', 'RepositoryList' ) : false;

        if ( !$repositoryGroupList )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'No repository groups found' ) );
        }

        $repositoryList = array();
        // Fetch settings for each griup
        foreach ( $repositoryGroupList as $key => $repositoryGroup )
        {
            if ( !$ini->hasGroup( $repositoryGroup ) )
            {
                continue;
            }

            $repositoryList[$key] = $ini->group( $repositoryGroup );
        }

        return $repositoryList;
    }

    /**
     * Fetches repository info
     *
     * @return array Repository info
     */
    protected static function getRepositoryInfoById( $repositoryId = false )
    {
        $repositoryList = self::fetchRepositoryList();

        $repositoryInfo = array();

        // If repositoryId is not provided fetch default
        if ( !$repositoryId )
        {
            $repositoryArray = isset( $repositoryList['default'] ) ? $repositoryList['default'] : false;
            if ( !$repositoryArray )
            {
                throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'No default repository configured' ) );
            }

            $repositoryInfo = self::fetchRepositoryInfo( $repositoryArray );
        }
        else
        {
            foreach ( $repositoryList as $key => $repository )
            {
                $info = self::fetchRepositoryInfo( $repository );
                if ( $info['repositoryId'] == $repositoryId )
                {
                    $repositoryInfo = self::fetchRepositoryInfo( $repository );
                    break;
                }
            }
        }

        return $repositoryInfo;
    }

    /**
     * Organizes repository info for \a $repositoryArray fetched from ini
     */
    protected static function fetchRepositoryInfo( $repositoryArray )
    {
        $rootNode = isset( $repositoryArray['RootNode'] ) ? $repositoryArray['RootNode'] : false;
        $name = isset( $repositoryArray['Name'] ) ? $repositoryArray['Name'] : 'Repository ' . $repositoryInfo['repositoryId'];

        if ( !$rootNode )
        {
            throw new eZCMISInvalidArgumentException( ezpI18n::tr( 'cmis', "No root node provided for repository '%name%'", null, array( '%name%' => $name ) ) );
        }

        $node = eZContentObjectTreeNode::fetch( $rootNode );
        if ( !$node )
        {
            throw new eZCMISObjectNotFoundException( ezpI18n::tr( 'cmis', "Could not fetch root node by node_id '%node_id%' for repository '%name%'", null, array( '%node_id%' => $rootNode, '%name%' => $name ) ) );
        }

        $repositoryInfo = array();
        if ( !$node->canRead() )
        {
            return $repositoryInfo;
        }

        $repositoryId = $node->attribute( 'remote_id' );

        $repositoryInfo['repositoryId'] = $repositoryId;
        $repositoryInfo['repositoryName'] = $name;
        $repositoryInfo['repositoryDescription'] = isset( $repositoryArray['Description'] ) ? $repositoryArray['Description'] : '';
        $repositoryInfo['vendorName'] = eZCMIS::VENDOR;
        $repositoryInfo['productName'] = eZCMIS::VENDOR;
        $repositoryInfo['productVersion'] = eZPublishSDK::version();
        $repositoryInfo['rootFolderId'] = $repositoryId;
        $repositoryInfo['capabilities'] = eZCMIS::getCapabilities();
        $repositoryInfo['cmisVersionSupported'] = eZCMIS::VERSION;

        return $repositoryInfo;
    }

    /**
     * Provides collection list of urls
     */
    protected static function getCollectionList( $repositoryInfo )
    {
        $repositoryId = isset( $repositoryInfo['repositoryId'] ) ? $repositoryInfo['repositoryId'] : '';

        return array( array( 'url' => eZCMISServiceURL::createURL( 'children', array( 'repositoryId' => $repositoryId, 'folderId' => $repositoryId ) ),
                             'type' => 'root',
                             'value' => 'root collection' ),
                      array( 'url' => eZCMISServiceURL::createURL( 'checkedout', array( 'repositoryId' => $repositoryId ) ),
                             'type' => 'checkedout',
                             'accept' => 'application/atom+xml;type=entry',
                             'value' => 'checkedout collection' ),
                      array( 'url' => eZCMISServiceURL::createURL( 'types', array( 'repositoryId' => $repositoryId ) ),
                             'type' => 'types',
                             'value' => 'type collection' ),
                      array( 'url' => eZCMISServiceURL::createURL( 'query', array( 'repositoryId' => $repositoryId ) ),
                             'type' => 'query',
                             'accept' => 'application/cmisquery+xml',
                             'value' => 'query collection' )
                      );

    }

    /**
     * Processes by GET http method
     */
    public function processRESTful()
    {
        $repositoryInfo = self::getRepositoryInfo();
        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'service', 'app' );
        $doc->appendChild( $root );
        $workspace = $doc->createElement( 'workspace' );
        $root->appendChild( $workspace );
        $title = $doc->createElement( 'atom:title', $repositoryInfo['repositoryName'] );
        $workspace->appendChild( $title );

        $repositoryId = isset( $repositoryInfo['repositoryId'] ) ? $repositoryInfo['repositoryId'] : '';

        // Create collection list
        $collectionList = self::getCollectionList( $repositoryInfo );

        foreach ( $collectionList as $collection )
        {
            $element = $doc->createElement( 'collection' );
            $element->setAttribute( 'href', $collection['url'] );
            $workspace->appendChild( $element );
            $value = $doc->createElement( 'atom:title', $collection['value'] );
            $element->appendChild( $value );

            if ( isset( $collection['accept'] ) )
            {
                $accept = $doc->createElement( 'accept', $collection['accept'] );
                $element->appendChild( $accept );
            }

            $type = $doc->createElement( 'cmisra:collectionType', $collection['type'] );
            $element->appendChild( $type );
        }

        $rootDescendants = $doc->createElement( 'atom:link' );
        $rootDescendants->setAttribute( 'title', 'root descendants' );
        $rootDescendants->setAttribute( 'type', 'application/cmistree+xml' );
        $rootDescendants->setAttribute( 'rel', 'http://docs.oasis-open.org/ns/cmis/link/200908/rootdescendants' );
        $rootDescendants->setAttribute( 'href', eZCMISServiceURL::createURL( 'descendants', array( 'repositoryId' => $repositoryId, 'folderId' => $repositoryId ) ) );
        $workspace->appendChild( $rootDescendants );

        $typeDescendants = $doc->createElement( 'atom:link' );
        $typeDescendants->setAttribute( 'title', 'type descendants' );
        $typeDescendants->setAttribute( 'type', 'application/cmistree+xml' );
        $typeDescendants->setAttribute( 'rel', 'http://docs.oasis-open.org/ns/cmis/link/200908/typesdescendants' );
        $typeDescendants->setAttribute( 'href', eZCMISServiceURL::createURL( 'types', array( 'repositoryId' => $repositoryId ) ) );
        $workspace->appendChild( $typeDescendants );

        $info = eZCMISAtomTools::createElementByArray( $doc, 'repositoryInfo', $repositoryInfo, 'cmisra:' );
        $workspace->appendChild( $info );

        // Create template definitions
        self::createTemplate( $doc, $workspace, 'objectbyid', eZCMISServiceURL::createURL( 'node', array( /*'repositoryId' => '{repositoryId}',*/ 'objectId' => '{id}' ) ) );
        self::createTemplate( $doc, $workspace, 'typebyid', eZCMISServiceURL::createURL( 'type', array( 'repositoryId' => '{repositoryId}', 'typeId' => '{id}' ) ) );

        return $doc->saveXML();
    }

    /**
     * Provides repository info by get param
     *
     * @return array Info
     */
    public function getRepositoryInfo()
    {
        $http = eZHTTPTool::instance();
        $repositoryId = $this->getField( 'repositoryId' )->getValue();

        $repositoryInfo = self::getRepositoryInfoById( $repositoryId );
        $remoteRootId = isset( $repositoryInfo['repositoryId'] ) ? $repositoryInfo['repositoryId'] : false;

        if ( empty( $repositoryInfo ) or !$remoteRootId )
        {
            throw new eZCMISObjectNotFoundException( ezpI18n::tr( 'cmis', 'Repository does not exist' ) );
        }

        return $repositoryInfo;
    }

    /**
     * Provides repository Id
     *
     * @return string Id
     */
    public function getRepositoryId()
    {
        $repositoryInfo = $this->getRepositoryInfo();

        return $repositoryInfo['repositoryId'];
    }

    /**
     * Creates template structure
     */
    protected static function createTemplate( DOMDocument $doc, DOMElement $workspace, $name, $uri, $mediaTypeStr = 'application/atom+xml;type=entry' )
    {
        $uriTemplate = $doc->createElement( 'cmisra:uritemplate' );
        $template = $doc->createElement( 'cmisra:template', htmlentities( $uri ) );
        $type = $doc->createElement( 'cmisra:type', $name );
        $mediaType = $doc->createElement( 'cmisra:mediatype', $mediaTypeStr );
        $uriTemplate->appendChild( $template );
        $uriTemplate->appendChild( $type );
        $uriTemplate->appendChild( $mediaType );

        $workspace->appendChild( $uriTemplate );
    }
}
?>

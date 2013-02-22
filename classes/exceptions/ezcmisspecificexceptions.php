<?php
/**
 * Definition of Specific Exceptions
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
 * The following exceptions MAY be returned by a repository in response to one or more CMIS service methods calls.
 *
 * @file ezcmisspecificexceptions.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexception.php' );

/**
 * The operation violates a Repository- or Object-level constraint defined in the CMIS domain model.
 *
 * @methods
 * Repository Service:
 *     getObjectParents
 *
 * Object Service:
 *     createDocument
 *     createFolder
 *     createRelationship
 *     createPolicy
 *     updateProperties
 *     moveObject
 *     deleteObject
 *     setContentStream
 *     deleteContentStream
 *
 * Multi-filing Services:
 *     addObjectToFolder
 *
 * Versioning Services:
 *     checkOut
 *     cancelCheckOut
 *     checkIn
 *
 * Policy Services:
 *     applyPolicy
 *     removePolicy
 */
class eZCMISConstraintViolationException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 409 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The operation attempts to set the content stream for a Document that already has a content stream without explicitly specifying the "overwrite" parmeter.
 *
 * @methods
 * Object Services:
 *     setContentStream
 */
class eZCMISContentAlreadyExistsException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 409 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The property filter input to the operation is not valid.
 *
 * @methods
 * Repository Service:
 *     getDescendants
 *     getChildren
 *     getFolderParent
 *     getObjectParents
 *     getCheckedoutDocs
 *
 * Object Service:
 *     getProperties
 *
 * Versioning Services:
 *     getPropertiesOfLatestVersion
 *     getAllVersions
 *
 * Policy Services:
 *     getAppliedPolicies
 */
class eZCMISFilterNotValidException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 400 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The operation is attempting to create an object in an "unfiled" state in a repository that does not support the "Unfiling" optional capability.
 */
class eZCMISFolderNotValidException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 400 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The repository is not able to store the object that the user is creating/updating due to an internal storage problem.
 *
 * @methods
 * Object Services:
 *     createDocument
 *     createFolder
 *     createRelationship
 *     createPolicy
 *     updateProperties
 *     moveObject
 *     setContentStream
 *     deleteContentStream
 *
 * Versioning Services:
 *     checkout
 *     checkIn
 */
class eZCMISStorageException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 500 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The operation is attempting to get or set a contentStream for a Document whose Object Type specifies that a content stream is not allowed for Document's of that type.
 *
 * @methods
 * Object Services:
 *     createDocument
 *     getContentStream
 *     setContentStream
 *
 * Versioning Services:
 *     checkIn
 */
class eZCMISStreamNotSupportedException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 403 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The operation is attempting to update an object that is no longer current (as determined by the repository).
 *
 * @methods
 * Object Services:
 *     updateProperties
 *     moveObject
 *     deleteObject
 *     deleteTree
 *     setContentStream
 *     deleteContentStream
 *
 * Versioning Services:
 *     checkout
 *     cancelCheckOut
 *     checkIn
 */
class eZCMISUpdateConflictException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 409 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The operation is attempting to perform an action on a non-current version of a Document that cannot be performed on a non-current version.
 *
 * @methods
 * Object Services:
 *     updateProperties
 *     moveObject
 *     setContentStream
 *     deleteContentStream
 *
 * Versioning Services:
 *     checkOut
 *     cancelCheckOut
 *     checkIn
 */
class eZCMISVersioningException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 409 )
    {
        parent::__construct( $message, $code );
    }
}


?>

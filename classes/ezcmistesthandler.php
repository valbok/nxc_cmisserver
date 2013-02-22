<?php
/**
 * Definition of eZCMISTestHandler class
 *
 * Created on: <1-Jun-2009 10:00:00 vd>
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
 * Test handler
 * Goal is to check and call needed test
 *
 * @file ezcmistesthandler.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISTestHandler
{

    /**
     * Result returned from tests
     *
     * @var array
     */
    protected $Result = array();

    /**
     * URL for getRepositoryInfo service
     */
    protected $ServiceURL = '';

    /**
     * Login
    *
    * @var string
    */
    protected $User = null;

    /**
     * Password
     *
     * @var string
     */
    protected $Password = '';

    /**
     * List of requested testes
     *
     * @var string
     */
    protected $TestList = array();

    /**
     * Tests params
     *
     * @var array
     */
    protected $Params = array();

    /**
     * Break symbol like "\n" or "<br>"
     *
     * @var string
     */
    protected $Break = "\n";

    /**
     * Constructor.
     *
     * @param array Parameters
     */
    public function __construct( $params = array() )
    {
        $tests = isset( $params['tests'] ) ? $params['tests'] : false;
        $endPoint = isset( $params['end_point'] ) ? $params['end_point'] : false;
        $user = isset( $params['user'] ) ? $params['user'] : false;
        $password = isset( $params['password'] ) ? $params['password'] : '';

        if ( !$tests )
        {
            eZCMISExceptions::isNotProvided( 'tests' );
        }

        if ( !$endPoint )
        {
            eZCMISExceptions::isNotProvided( 'end_point' );
        }

        if ( !$user )
        {
            eZCMISExceptions::isNotProvided( 'user' );
        }

        $this->ServiceURL = $endPoint;
        $this->User = $user;
        $this->Password = $password;
        $this->TestList = $tests == '*' ? self::getAllTests() : explode( ',', $tests );
        $this->Params = $params;
        $this->Params['break'] = $this->Break;
    }

    /**
     * Startes test process
     */
    protected function startTests()
    {
        $this->addMessage( ezpI18n::tr( 'cmis', 'Test Started at' ) . ' ' . date( 'Y-m-d H:i:s' ) );
        $this->addMessage( ezpI18n::tr( 'cmis', 'Service URL:' ) . ' ' . $this->ServiceURL );
        $this->addMessage( ezpI18n::tr( 'cmis', 'User:' ) . ' ' . $this->User );
        $this->addMessage( ezpI18n::tr( 'cmis', 'Password:' ) . ' ' . $this->Password );
        $this->addMessage( ezpI18n::tr( 'cmis', 'Tests:' ) . ' ' . implode( ', ', $this->TestList ) );
        $this->addMessage();
    }

    /**
     * Startes requested test \a $name
     */
    protected function startTest( $name )
    {
        $this->addMessage( '*** ' . ezpI18n::tr( 'cmis', 'Test started:' ) . ' ' . $name );
    }

    /**
     * Finishes requested test \a $name
     */
    protected function completeTest( $name )
    {
        $this->addMessage( '*** ' . ezpI18n::tr( 'cmis', 'Test completed:' ) . ' ' . $name );
    }

    /**
     * Addes failed test \a $name
     */
    protected function failTest( $name )
    {
        $this->addMessage( '*** ' . ezpI18n::tr( 'cmis', 'Failed:' ) . ' ' . $name );
    }

    /**
     * Addes message \a $message to result set
     */
    protected function addMessage( $message = '' )
    {
        $this->Result[] = $message;
    }

    /**
     * Addes count of failed tests
     */
    protected function addFailedCount( $failedCount )
    {
        $this->addMessage( '*** ' . ezpI18n::tr( 'cmis', 'Count of failed tests:' ) . ' ' . $failedCount );
    }

    /**
     * Processes requested test
     *
     * @return string
     */
    public function process()
    {
        $this->startTests();

        $testMap = self::testMap();
        $failedCount = 0;

        foreach ( $this->TestList as $name )
        {
            $name = trim( $name );

            if ( !isset( $testMap[$name] ) )
            {
                throw new eZCMISInvalidArgumentException( ezpI18n::tr( 'cmis', "Requested test is not available: '%test%'", null, array( '%test%' => $name ) ) );
            }

            $this->startTest( $name );

            $file = $testMap[$name]['script'];

            if ( !file_exists( $file ) )
            {
                throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Test include file does not exist: '%file%'", null, array( '%file%' => $file ) ) );
            }

            include_once( $file );

            $class = $testMap[$name]['class'];
            if ( !class_exists( $class ) )
            {
                throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class '%class%' does not exist", null, array( '%class%' => $class ) ) );
            }

            $test = new $class( $this->ServiceURL, $this->User, $this->Password, $this->Params );

            $message = '';
            try
            {
                $test->process();
                $message = $test->getResult();
            }
            catch ( Exception $error )
            {
                $this->failTest( $name );
                $message = $error->getMessage();
                $failedCount++;
            }

            if ( !empty( $message ) )
            {
                $this->addMessage( $message );
            }

            $this->completeTest( $name );
        }

        $this->addFailedCount( $failedCount );

        return $this->getResult();
    }

    /**
     * Provides test results
     *
     * @return string
     */
    public function getResult()
    {
        header( 'Content-type: text/plain' );

        return implode( $this->Break, $this->Result );
    }

    /**
     * Provides test map
     */
    public static function testMap()
    {
        return array( 'testRepository'          => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestrepository.php',
                                                          'class' => 'eZCMISTestRepository' ),
                      'testCreateDocument'      => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestcreatedocument.php',
                                                          'class' => 'eZCMISTestCreateDocument' ),
                      'testCreateFolder'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestcreatefolder.php',
                                                          'class' => 'eZCMISTestCreateFolder' ),
                      'testDeleteContentStream' => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestdeletecontentstream.php',
                                                          'class' => 'eZCMISTestDeleteContentStream' ),
                      'testDeleteObject'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestdeleteobject.php',
                                                          'class' => 'eZCMISTestDeleteObject' ),
                      'testDeleteTree'          => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestdeletetree.php',
                                                          'class' => 'eZCMISTestDeleteTree' ),
                      'testContentStream'       => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestcontentstream.php',
                                                          'class' => 'eZCMISTestContentStream' ),
                      'testDescendants'         => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestdescendants.php',
                                                          'class' => 'eZCMISTestDescendants' ),
                      'testObjectParents'       => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestobjectparents.php',
                                                          'class' => 'eZCMISTestObjectParents' ),
                      'testFolderParent'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistestfolderparent.php',
                                                          'class' => 'eZCMISTestFolderParent' ),
                      'testTypes'               => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistesttypes.php',
                                                          'class' => 'eZCMISTestTypes' ),
                      'testType'               => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/tests/ezcmistesttype.php',
                                                          'class' => 'eZCMISTestType' ),


                      );
    }

    /**
     * Provides all tests
     *
     * @return array
     */
    public static function getAllTests()
    {
        $testMap = self::testMap();
        $result = array();

        foreach ( array_keys( $testMap ) as $test )
        {
            $result[] = $test;
        }

        return $result;
    }

    /**
     * Checks if module exists
     *
     * @return bool
     */
    public static function testExists( $name )
    {
        $testMap = self::testMap();

        return isset( $testMap[$name] );
    }
}
?>
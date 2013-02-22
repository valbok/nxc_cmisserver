<?php
/**
 * Definition of eZCMISTestBase class
 *
 * Created on: <25-Apr-2009 20:59:01 vd>
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
 * Base class for tests
 *
 * @file ezcmismoduletest.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );

abstract class eZCMISTestBase
{
    /**
     * URL for getRepositoryInfo service
     *
     * @var string
     */
    protected $EndPoint = null;

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
    protected $Password = null;

    /**
     * Test result
     *
     * @var array
     */
    protected $Result = array();

    /**
     * Break symbol
     *
     * @var string
     * @TODO should be reviewed to check necessity of field
     */
    protected $Break = "";

    /**
     * List of service parameters
     *
     * @var array
     */
    protected $Params = array();

    /**
     * Trace request/response
     *
     * @var bool
     */
    protected $TraceData = false;

    /**
     * Constructor.
     *
     * @param array Parameters
     */
    public function __construct( $endPoint, $user, $password = '', $params = array() )
    {
        $this->EndPoint = $endPoint;
        $this->User = $user;
        $this->Password = $password;
        $this->Params = $params;
        $this->Break = isset( $params['break'] ) ? $params['break'] : "\n";
        $this->TraceData = isset( $params['trace_data'] ) ? $params['trace_data'] == 'true' : false;
    }

    /**
     * Checks returned HTTP code
     *
     * @param string Expected code
     * @param string Returned code
     * @param string HTTP method
     * @param string URL
     * @param string Response
     *
     * @return bool
     */
    protected function checkCode( $expected, $returned, $method, $url, $response )
    {
        if ( $returned != $expected )
        {
            $error = ezpI18n::tr( 'cmis', 'Status code %returned% returned, but expected %expected% for %url% (%method%)',
                                       null,
                                       array( '%returned%' => $returned,
                                              '%expected%' => $expected,
                                              '%url%' => $url,
                                              '%method%' => $method ) );

            $this->addMessage( $error );
            $this->addMessage( $response );
            $this->throwError();
        }
    }

    /**
     * Addes message to result set
     */
    protected function addMessage( $message )
    {
        $this->Result[] = $message;
    }

    /**
     * Checks response
     *
     * @return bool
     */
    protected function checkResponse( $response )
    {
        if ( !strlen( $response ) )
        {
            $this->throwError( ezpI18n::tr( 'cmis', 'No data returned' ) );
        }
    }

    /**
     * Addes request to result list
     */
    protected function addRequest( $url, $method )
    {
        if ( $this->TraceData )
        {
            // @TODO: Check method and is it needed to be added? Check by param
            $this->addMessage( '* ' . ezpI18n::tr( 'cmis', 'Request:' ) . ' ' . $method . ' ' . $url );
        }
    }

    /**
     * Addes response to result list
     */
    protected function addResponse( $url, $method, $code, $response )
    {
        if ( $this->TraceData )
        {
            $this->addMessage( '* ' . ezpI18n::tr( 'cmis', 'Response:' ) . ' ' . $code . ' ' . $method . ' ' . $url  );
            $this->addMessage( $response );
        }
    }

    /**
     * Tests CMIS service
     */
    abstract protected function test();

    /**
     * Handles requested module
     *
     * @return string XML or boolean
     */
    public function process()
    {
        try
        {
            $this->test();
        }
        catch ( Exception $error )
        {
            $message = $error->getMessage();
            if ( !empty( $message ) )
            {
                $this->addMessage( $error->getMessage() );
            }

            $this->throwError( $this->getResult() );
        }
    }

    /**
     * Throws error
     */
    protected function throwError( $message = '' )
    {
        throw new eZCMISRuntimeException( $message );
    }

    /**
     * Makes HTTP request
     */
    public function httpRequest( $serviceURL, $user, $password, $headers = array(), $method = 'GET', $data = null )
    {
        $this->addRequest( $serviceURL, $method );

        // Prepare curl session
        $session = curl_init( $serviceURL );
        curl_setopt( $session, CURLOPT_VERBOSE, 1 );

        // Add additonal headers
        curl_setopt( $session, CURLOPT_HTTPHEADER, $headers );

        // Don't return HTTP headers. Do return the contents of the call
        curl_setopt( $session, CURLOPT_HEADER, false );
        curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

        curl_setopt( $session, CURLOPT_USERPWD, "$user:$password" );


        switch ( $method )
        {
            case 'CUSTOM-POST':
            {
                curl_setopt( $session, CURLOPT_CUSTOMREQUEST, 'POST' );
                curl_setopt( $session, CURLOPT_POSTFIELDS, $data );
                curl_setopt( $session, CURLOPT_ERRORBUFFER, 1 );

            } break;

            case 'CUSTOM-PUT':
            {
                curl_setopt( $session, CURLOPT_CUSTOMREQUEST, 'PUT' );
                curl_setopt( $session, CURLOPT_POSTFIELDS, $data );
                curl_setopt( $session, CURLOPT_ERRORBUFFER, 1 );

            } break;

            case 'CUSTOM-DELETE':
            {
                curl_setopt( $session, CURLOPT_CUSTOMREQUEST, 'DELETE' );
                curl_setopt( $session, CURLOPT_ERRORBUFFER, 1 );

            } break;
        }

        // Make the call
        $response = curl_exec( $session );

        // Get return http status code
        $code = curl_getinfo( $session, CURLINFO_HTTP_CODE );

        // Close HTTP session
        curl_close( $session );

        $result = new stdClass();
        $result->code = $code;
        $result->response = $response;

        $this->addResponse( $serviceURL, $method, $code, $response );

        return $result;
    }


    /**
     *
     */
    protected function addError( $error )
    {
        $this->Errors[] = $error;
    }

    /**
     * Provides tests result
     *
     * @return string
     */
    public function getResult()
    {
        return implode( $this->Break, $this->Result );
    }
}

?>
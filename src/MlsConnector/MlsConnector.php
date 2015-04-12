<?php

namespace MlsConnector;

class MlsConnector {

    protected $endPoint;
    protected $key;
    protected $token;  // private token
    protected $etoken; // hashed token

    protected $authenticated = FALSE;
    protected $errors   = []; // erros saved on array
    protected $version  = 'v2';
    protected $uri;
    protected $sourceid;

    public function __construct( $key , $token, $endPoint , $version = 'v2' )
    {
        $endPoint = substr( $endPoint , -1 ) == '/' ? $endPoint : $endPoint.'/' ;

        $this->endPoint  =     $endPoint;
        $this->key	  	 =     $key;
        $this->token  	 =     $token;
        $this->version 	 =     $version;

    }

    private function checkCredentials()
    {
        if( empty( $this->key ) || empty( $this->token ) ){
            $this->errors[] = ' Key or Token must not be empty';
            return false;
        }

        if( empty( $endPoint ) ){
            $this->errors[] = ' Invalid endpoint url';
            return false;
        }

        return true;
    }
    /**
     * @param array $request
     *
     *    possible query fields
     *    q  = location query
     *    bathrooms = minimum number of bath rooms
     *    bedrooms = minimum number of bedrooms
     *    min_listprice = Minimum list price
     *    max_listprice = Maximum list price
     *    status = Property Status ( 'Active' , 'Backup Offer' , 'Pending Sale' , 'Closed Sale'  )
     *    transaction = 'Rent','Sale'
     *    limit  = maximum number of properties to fetch. Defaults to 20
     *    page = handy for pagination
     *
     * @return string json_encoded property list
     */
    public function getProperties( $data )
    {
        return $this->sendRequest( 'getproperties' , $data );
    }

    public function getPropertyByMLSID( $mls_id )
    {
        $data['mls_id'] = $mls_id;
        return $this->sendRequest( 'getPropertyByMLSID' , $data );
    }

    public function getPropertyByMatrixID( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        $response =  $this->sendRequest( 'getPropertyByMatrixID' , $data );

        return $response ;

    }

    public function getHighResPhotosByMatrixID( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        return $this->sendRequest( 'getHighResPhotosByMatrixID' , $data );
    }

    public function getHighResPhotosObjectByMatrixID( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        return $this->sendRequest( 'getHighResPhotosObjectByMatrixID' , $data );
    }

    public function importHighResPhotos( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        return $this->sendRequest( 'importHighResPhotos' , $data );
    }

    public function getPhotosByMatrixID( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        return $this->sendRequest( 'getPhotosByMatrixID' , $data );
    }

    public function getPhotosObjectByMatrixID( $matrix_id )
    {
        $data['matrix_id'] = $matrix_id;
        return $this->sendRequest( 'getPhotosObjectByMatrixID' , $data );
    }

    public function getPropertiesByPropertyIDs( $ids = array() )
    {
        //$data['matrix_id'] = $matrix_id;
        //return $this->sendRequest( 'getPhotosObjectByMatrixID' , $data );
    }

    public function getPropertyTypes()
    {
        return $this->sendRequest( 'getPropertyTypes' , array() );
    }

    public function getLatestPropertiesByZip( $zipCode , $data = array() )
    {
        $data['ZipCode'] = $zipCode;
        return $this->sendRequest( 'getLatestPropertiesByZip' , $data );
    }

    public function getHighResPhotos()
    {
        //$post['matrix_id'] = $matrix_id;
        //return $this->getRequest( 'getHighResPhotosByMatrixID' , $post );
    }

    public function getRelatedPropertiesByMatrixID( $matrix_id )
    {
        if( ! $matrix_id ){
            return array(
                'result' => 'fail',
                'messsage' => ' Invalid Propertyid ',
            );
        }

        $data['matrix_id'] 	=  $matrix_id;

        return $this->sendRequest( 'getRelatedPropertiesByMatrixID' , $data );
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getCoverageLookup( $mls )
    {
        $data[ 'mls' ] = $mls;
        $data[ 'verb' ] = 'GET';
        return $this->sendRequest( 'getCoverageLookup' , $data );
    }

    public function sendRequest( $request , $data )
    {

        if( ! $this->checkCredentials() ){
            return [
                'result'    => 'fail',
                'success'    => 0,
                'message'   => 'Error found',
                'errors'    => $this->errors
            ];
        }

        $data['key']      = $this->key;
        $data['request']  = $request;
        $data['sourceid'] = $this->sourceid;

        $uri    =   strtolower( $this->endPoint.$this->version.'/'.$request );
        $this->uri =  $uri;

        $data	=	http_build_query( $data );
        $etoken =   hash_hmac( 'sha256' , $data , $this->token ) ;

        $uri_with_data = $uri.'/?'.$data;

        $ch     =   curl_init( $uri );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
        curl_setopt( $ch, CURLOPT_USERPWD, "$this->key:$etoken" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS,  $data );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        $this->uri =  $uri;

        if( isset( $data['verb'] ) && $data['verb'] == 'POST' ){

        }else{
            /**
            $ch     =   curl_init( $uri );
            curl_setopt( $ch, CURLOPT_POSTFIELDS,  $data );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            $this->uri =  $uri;
             */
            //$ch     =   curl_init();
            //curl_setopt( $ch, CURLOPT_URL,  $uri_with_data );
        }

        try {
            $curl_output = curl_exec($ch);

        }catch( \Exception $e ){
            curl_close($ch);

            return array(
                'result' => 'fail',
                'message' => 'CURL error: '.$e->getMessage(),
                'uri' =>  $uri
            );
        }

        if( curl_errno( $ch ) ){
            $this->errors[] = curl_error( $ch );
            return array(
                'result' => 'fail',
                'message' => curl_error( $ch ),
                'uri' =>  $uri
            );
        }

        curl_close($ch);

        if( ! $curl_output ){
            return array(
                'result' => 'fail',
                'message' => ' Application error. No output returned'
            );
        }

        $decode =  json_decode( $curl_output );

        if( $decode === null ){
            return array(
                'result' => 'fail',
                'messsage' => ' No output returned. Most probably an Application Error '.$uri_with_data,
            );
        }

        return $decode;
    }
}
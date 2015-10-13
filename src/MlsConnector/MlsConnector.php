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

    protected $data = array();

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

        if( empty( $this->endPoint ) ){
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
    /**
     * @param array $data
     *
     *    possible property criteria fields
     *
     *    mls: abbreviation of the mls id MFR, HL, CRMLS
     *    city: city name
     *    community: community name
     *    subdivision: subdivision name
     *    min_listprice: Minimum list price
     *    max_listprice:  Maximum list price
     *    min_beds:
     *    max_beds:
     *    min_baths:
     *    max_baths:
     *    min_garage:
     *    transaction: 'rent','sale'
     * @return string json_encoded property list
     */
    public function addPropertyAlert( $data )
    {
        return $this->sendRequest( 'addPropertyAlert' , $data );
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

    public function getComparable( $listing_id )
    {
        $data['listing_id'] 	=  $listing_id;
        return $this->sendRequest( 'getComparable' , $data );
    }

    public function getFeatured()
    {
        return $this->sendRequest( 'getFeatured' , array() );
    }

    public function getCoverageLookup( $mls )
    {
        $data[ 'mls' ] =  $mls;
        $data[ 'verb' ] = 'GET';
        return $this->sendRequest( 'getCoverageLookup' , $data );
    }

    public function getCitiesByMls( $mls )
    {
        $data[ 'mls' ] =  $mls;
        return $this->sendRequest( 'getCitiesByMls' , $data );
    }

    public function getCommunitiesByCityId( $cityid )
    {
        $data[ 'cityid' ] =  $cityid;
        return $this->sendRequest( 'getCommunitiesByCityId' , $data );
    }

    /***
     * Adding data key value pair
     * @param $key
     * @param $value
     */
    public function addData( $key , $value )
    {
        //check if both key and value has values
        if( ! $key || ! $value ){
            throw new \Exception( 'Invalid key value pair' );
        }

        // check if key is str
        if( ! is_string( $key ) ){
            throw new \Exception( 'Keys must be a string data type' );
        }

        if( is_string( $value ) || is_array( $value ) || is_bool( $value ) ){
            // do nothing
        }else{
            throw new \Exception( 'Value must be a string, array or boolean data type only' );
        }

        $this->data[ $key ] = $value;
    }

    private function sendRequest( $request , $data )
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

        foreach( $this->data as $k => $v ){
            $data[$k] = $v;
        }

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
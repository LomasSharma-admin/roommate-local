<?php

# Include Rest Controller for rest api #
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends REST_Controller {
    public $baseUrl;
    public $tokenHeaderName;
    public $userTokenHeaderName;

	public function __construct() {
        parent:: __construct();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['jwt', 'authorization']);
        $this->load->helper('date');
        $this->load->model("User_model",'user');

        $this->baseUrl= $_SERVER['SERVER_NAME'];
        $this->tokenHeaderName= 'token';
        $this->userTokenHeaderName= 'usertoken' ;
        
        date_default_timezone_set('UTC');
    }

    # Generate JWT Token Function #
    public function token_get(){
    
        $getToken               = $this->user->getToken(11); // GET TOKEN //
        $tokenData['id']        = 12; //TODO: Replace with data for token
        $tokenData['token']     = $getToken;
        $tokenData['timestamp'] = now();
        $userToken              = AUTHORIZATION::generateToken($tokenData);
        
        # Return True Response #
        $this->response([
                    'status'  => TRUE,
                    'message' => 'success',
                    'data'    => array('token'=> $userToken)
              ], REST_Controller::HTTP_OK);
    }

    public function testing_get(){
        $headers = apache_request_headers();
        print_r($headers);
    }

    # @ Register Function #     
    public function register_post(){
        # HEADERS PARAMS #
        $headers = apache_request_headers();

      
        # Validate Header Params #
        if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
            # Verify Access Token #
            $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
            $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

            # If Access Token Is Verified or not#
            if ($decodedTokenCheck != []) {
                # Validate Requested Parames #
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 
                    extract($jsonArray);
                  
                    if(isset($image)  && isset($firstName) && !empty($firstName) && isset($lastName) && !empty($lastName) && 
                       isset($email) && !empty($email) && isset($countryCode) && !empty($countryCode)   && isset($password) && !empty($password) && isset($phoneNumber) && !empty($phoneNumber) && isset($deviceToken) && !empty($deviceToken)){

                        # Validate Email and Mobile Duplicacy #


                        $user = $this->user->getUserByEmailOrMobile($email,$phoneNumber);
                        if(empty($user)){
                            $imageName = $this->user->uploadBase64Image($image,'./uploads/userProfilePics/');
                            if(!empty($imageName)){
                                # Update Same Device Token #
                                $updateDeviceToken = $this->user->update_data('appUsers',['deviceToken'=>''],['deviceToken'=>$deviceToken]);

                                # Generate User and email Token #
                                $userToken = $this->user->getToken(16);
                                $sessionToken = $this->user->getToken(16);


                                # Insert Parameter #
                                $insData['userToken']                       = $userToken;
                                $insData['sessionToken']                    = $sessionToken;
                                $insData['firstName']                       = $firstName;
                                $insData['lastName']                        = $lastName;
                                $insData['image']                           = $imageName;
                                $insData['email']                           = $email;
                                $insData['password']                        = md5($password);
                                $insData['countryCode']                     = $countryCode;
                                $insData['phoneNumber']                     = $phoneNumber;
                                $insData['deviceToken']                     = $deviceToken;
                                $insData['addedOn']                         = date('Y-m-d H:i:s');
                                $insData['addedOnTimeStamp']                = (string)time();

                                # Insert New User #
                                $userId = $this->user->insertToAnyTable('appUsers',$insData);
                                if($userId){
                                        
                                        
                                        # Send Email for verification #
                                        // $subject = 'Email Verification - Edify';
                                        // $message = 'For Email verification click on this link: '.$this->baseUrl.'/edify/EmailVerification/?hash='.$emailHash;
                                         // $message = $this->user->verificationUserHtml($firstName.' '.$lastName,$otpCode);

                                        // Always set content-type when sending HTML email
                                        // if($this->user->sendSendgridMail($emailAddress,$firstName.' '.$lastName,$subject,$message)){
                                                # Return Success Response #
                                                // unset($insData['emailHash']);
                                                // unset($insData['emailHashExpireTimeStamp']);
                                                unset($insData['deviceToken']);
                                                unset($insData['addedOnTimeStamp']);
                                                unset($insData['addedOn']);
                                                unset($insData['password']);
                                                $insData['emailVerificationStatus'] = (string)'1';
                                                $this->response([
                                                    'status'  => true,
                                                    'message' => 'Registered Successfully',
                                                    'data'    => $insData
                                                ], REST_Controller::HTTP_OK);
                                        // }else{
                                        //     # Return Success Response #
                                        //     $this->response([
                                        //         'status'  => true,
                                        //         'message' => 'Unable to sent mail',
                                        //         'data'    => $insData
                                        //     ], REST_Controller::HTTP_OK);
                                        // }

                                        // if(mail($emailAddress,$subject,$message,$headers)){
                                                
                                        // }else{
                                                
                                        // }
                                         
                                }else{
                                        # Return Register Error Response #
                                        $this->response([
                                            'status'  => false,
                                            'message' => 'Error while registering new user',
                                            'data'    => (Object)[]
                                        ], REST_Controller::HTTP_BAD_REQUEST);
                                }
                            }else{
                                    # Return Image not upload Response #
                                    $this->response([
                                            'status'  => false,
                                            'message' => 'Error while uploading profile image',
                                            'data'    =>  (Object)[]
                                    ], REST_Controller::HTTP_BAD_REQUEST);
                            }
                        }else{
                                # Return Invalid Requested Param Response #
                                $this->response([
                                    'status'  => false,
                                    'message' => 'Email/Mobile already exist',
                                    'data'    => (Object)[]
                                ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                    'status'  => false,
                                    'message' => 'Please provide missing parameter',
                                    'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
            }else{
                    # Return Token Invalid Response #
                    $this->response([
                            'status'  => false,
                            'message' => 'Token invalid',
                            'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }else{
                # Return Missing Header Response #
                $this->response([
                        'status'  => false,
                        'message' => 'Token not found',
                        'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    # @ LOGIN Function #
    public function loginByEmail_post(){
        # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 
                    extract($jsonArray);
                    # Valdiate Params #
                    if(isset($email) && !empty($email) && isset($password) && !empty($password) && isset($deviceToken) && !empty($deviceToken)){
                        $user = $this->user->getUserByEmail($email);
                        if($user){
                            if($user->password == md5($password)){
                                if($user->activeStatus!='1'){
                                    # Return Account Blocked #
                                    $this->response([
                                        'status'  => false,
                                        'message' => 'Your account is blocked',
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_OK);
                                }
                                # Remove other user same device token #
                                $updateOtherDeviceToken = $this->user->update_data('appUsers',['deviceToken'=>''],['deviceToken'=>$deviceToken]);
                                
                                # User usertoken and deviceToken update #
                                $sessionToken = $this->user->getToken(16);
                                $updUserData['sessionToken']        = $sessionToken;
                                $updUserData['deviceToken']      = $deviceToken;
                                // $updUserData['loginOn']          = date('Y-m-d H:i:s');
                                // $updUserData['loginOnTimeStamp'] = time();

                                $updateUser = $this->user->update_data('appUsers',$updUserData,['id'=>$user->id]);
                                unset($user->sessionToken);
                               
                                # Return Success Response #
                                $user->sessionToken = $sessionToken;
                              
                                     # Unset Other Values #
                                    unset($user->password);
                                    unset($user->deviceToken);
                                    unset($user->addedOn);
                                    unset($user->addedOnTimeStamp);
                                    unset($user->id);
                                    $user->emailVerificationStatus = (string)'1';
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Login Successfully',
                                        'data'    => $user
                                    ], REST_Controller::HTTP_OK);
                                
                            }else{
                                    # Return Invalid Email Response #
                                    $this->response([
                                        'status'  => false,
                                        'message' => 'Entered password is incorrect',
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_BAD_REQUEST);
                            }
                        }else{
                                # Return Invalid Email Response #
                                $this->response([
                                    'status'  => false,
                                    'message' => 'Entered email not registered',
                                    'data'    => (object)[]
                                ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    public function loginByPhoneNumber_post(){
        # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 
                    extract($jsonArray);
                    # Valdiate Params #
                    if(isset($countryCode) && !empty($countryCode) && isset($phoneNumber) && !empty($phoneNumber) && isset($deviceToken) && !empty($deviceToken)){
                        $user = $this->user->getUserByCountryCodeAndPhoneNumber($countryCode,$phoneNumber);
                        if($user){
                                if($user->activeStatus!='1'){
                                    # Return Account Blocked #
                                    $this->response([
                                        'status'  => false,
                                        'message' => 'Your account is blocked',
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_OK);
                                }
                                # Remove other user same device token #
                                $updateOtherDeviceToken = $this->user->update_data('appUsers',['deviceToken'=>''],['deviceToken'=>$deviceToken]);
                                
                                # User usertoken and deviceToken update #
                                $sessionToken = $this->user->getToken(16);
                                $updUserData['sessionToken']        = $sessionToken;
                                $updUserData['deviceToken']      = $deviceToken;
                                // $updUserData['loginOn']          = date('Y-m-d H:i:s');
                                // $updUserData['loginOnTimeStamp'] = time();

                                $updateUser = $this->user->update_data('appUsers',$updUserData,['id'=>$user->id]);
                                unset($user->sessionToken);
                               
                                # Return Success Response #
                                $user->sessionToken = $sessionToken;
                              
                                     # Unset Other Values #
                                    unset($user->password);
                                    unset($user->deviceToken);
                                    unset($user->addedOn);
                                    unset($user->addedOnTimeStamp);
                                    unset($user->id);
                                    $user->emailVerificationStatus = (string)'1';
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Login Successfully',
                                        'data'    => $user
                                    ], REST_Controller::HTTP_OK);
                                
                            
                        }else{
                                # Return Invalid Email Response #
                                $this->response([
                                    'status'  => false,
                                    'message' => 'Entered email not registered',
                                    'data'    => (object)[]
                                ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    public function addUserRoomItem_post(){
          # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 
                    extract($jsonArray);
                    # Valdiate Params #
                    if(isset($itemName) && !empty($itemName)){
                                
                                # Remove other user same device token #
                                
                                # User usertoken and deviceToken update #
                                $insData['userId']        = $decodedTokenCheck['id'];
                                $insData['name']        = $itemName;

                                if(isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])){

                                    $uploads_dir = './uploads/roomItemImages/';
                                    $tmp_name = $_FILES["image"]["tmp_name"];
                                    $imageName =  $this->user->getToken(11).'_'.$_FILES["image"]["name"];

                                    move_uploaded_file($tmp_name, "$uploads_dir/$imageName");
                                    $insData['image']        = $imageName;
                                }

                                $roomItemId = $this->user->insertToAnyTable('appUserRoomItems',$insData);
                               
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Item added Successfully',
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_OK);
                                
                            
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function viewUserRoomItem_get(){
          # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #

                                $roomItems = $this->user->getUserRoomItemsByUserId($decodedTokenCheck['id']);
                               
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Item added Successfully',
                                        'data'    => array('items'=>$roomItems)
                                    ], REST_Controller::HTTP_OK);
                                
                            
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function createPartner_post(){
         # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 
                    extract($jsonArray);
                    # Valdiate Params #
                    if(isset($relationshipId) && !empty($relationshipId) 
                        && isset($propertyTitle) && !empty($propertyTitle)
                        && isset($propertyTypeIds) && !empty($propertyTypeIds)
                        && isset($dealTypeIds) && !empty($dealTypeIds) 
                        && isset($bedRoomCount) 
                        && isset($bathRoomCount) 
                        && isset($parkingCount) 
                        && isset($movingDate) && !empty($movingDate) 
                        && isset($preferLocations) && !empty($preferLocations) 
                        && isset($preferBedRoomIds)  
                        && isset($preferBathRoomIds) 
                        && isset($roomIncludedIds) 
                        && isset($roomItemIds) 
                        && isset($budgetFrom) && !empty($budgetFrom) 
                        && isset($fixRentStatus) 
                        && isset($budgetTo) && !empty($budgetTo) 
                        && isset($rent) && !empty($rent) 
                        && isset($noOfPeople) && !empty($noOfPeople) 
                        && isset($numberOfKid) 
                        && isset($petStatus) 
                        && isset($smokingStatus) 
                        && isset($drinkingStatus) 
                        && isset($personalNote) 
                        && isset($contactTypeId)) 
                    {

                        $insData['userId'] = $decodedTokenCheck['id'];
                        $insData['relationId'] = $relationshipId;
                        $insData['propertyTitle'] = $propertyTitle;
                        $insData['propertyTypeIds'] = $propertyTypeIds;
                        $insData['dealTypeIds'] = $dealTypeIds;
                        $insData['bedRoomCount']  = $bedRoomCount;
                        $insData['bathRoomCount'] = $bathRoomCount;
                        $insData['parkingCount'] = $parkingCount;
                        $insData['movingDate'] = $movingDate;
                        $insData['preferBedRoomIds'] = $preferBedRoomIds;
                        $insData['preferBathRoomIds'] = $preferBathRoomIds;
                        $insData['roomIds'] = $roomIncludedIds;
                        $insData['budgetFrom'] = $budgetFrom;
                        $insData['budgetTo'] = $budgetTo;
                        $insData['budgetRent'] = $rent;
                        $insData['fixRentStatus'] = $fixRentStatus;
                        $insData['noOfPeople']  = $noOfPeople;
                        $insData['addedOn'] = date('Y-m-d H:i:s');
                        $insData['addedOnTimeStamp'] = time();
                        
                        $partnerId = $this->user->insertToAnyTable('appUserPartners',$insData);
                        if($partnerId){
                            $insPartnerDetailData['partnerId'] = $partnerId;
                            $insPartnerDetailData['numberOfKid'] = $numberOfKid;
                            $insPartnerDetailData['petStatus'] = $petStatus;
                            $insPartnerDetailData['smokingStatus'] = $smokingStatus;
                            $insPartnerDetailData['drinkingStatus'] = $drinkingStatus;
                            $insPartnerDetailData['personalNote'] = $personalNote;
                            $insPartnerDetailData['contactId'] = $contactTypeId;

                            $partnerDetailId = $this->user->insertToAnyTable('appUserPartnerDetails',$insPartnerDetailData);

                            if(!empty($preferLocations)){
                                foreach($preferLocations as $location){
                                    $insLocData['partnerId'] = $partnerId;
                                    $insLocData['location'] = $location;

                                    $locationId = $this->user->insertToAnyTable('appUserPartnerPreferLocations',$insLocData);
                                }
                            }

                            if(!empty($roomItemIds)){
                                $roomItemIdArr = explode(',', $roomItemIds);
                                foreach($roomItemIdArr as $roomItem){
                                    $insItemData['partnerId'] = $partnerId;
                                    $insItemData['roomItemId'] = $roomItem;
                                    $partnerItemId = $this->user->insertToAnyTable('appUserPartnerItems',$insItemData);
                                }
                            }

                            $this->response([
                                'status'  => true,
                                'message' => 'Partner added Successfully',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Unable to add partner',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function partners_get(){
          # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    # Valdiate Params #
                    if(isset($_GET) && !empty($_GET)){
                        extract($_GET);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $partners = $this->user->getPartnersNotIn($decodedTokenCheck['id']);
                            if($partners){
                                foreach($partners as $key=>$value){
                                    $partners[$key]['preferLocations'] = $this->user->getPartnerPreferLocationByPartnerId($value['id']);
                                    $partners[$key]['roomItems'] = $this->user->getPartnerRoomItemByPartnerId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($partners);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($partners, $st2, 20 );

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('totalPages'=>$totalPages,'list'=>$list)
                                    ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                         $this->response([
                        'status'  => false,
                        'message' => 'Please provide all parameters',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    public function addRemovePartnerFav_get(){
           # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    # Valdiate Params #
                    if(isset($_GET) && !empty($_GET)){
                        extract($_GET);
                        if(isset($partnerId) && !empty($partnerId)){
                            $partnerDetails = $this->user->getUserFavPartnerById($decodedTokenCheck['id'],$partnerId);
                            if($partnerDetails){
                                $this->user->deleteFromAnyTable('appUserFavPartners',['id'=>$partnerDetails->id]);
                                $message = "Partner Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $decodedTokenCheck['id'];
                                $insData['partnerId'] = $partnerId;

                                $favId = $this->user->insertToAnyTable('appUserFavPartners',$insData);
                                $message = "Partner Favourite Successfully";
                            }
                            

                                    $this->response([
                                        'status'  => true,
                                        'message' => $message,
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                         $this->response([
                        'status'  => false,
                        'message' => 'Please provide all parameters',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    public function viewFavPartners_get(){
        $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    # Valdiate Params #
                    if(isset($_GET) && !empty($_GET)){
                        extract($_GET);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $partners = $this->user->getUserFavPartners($decodedTokenCheck['id']);
                            
                            if($partners){
                                foreach($partners as $key=>$value){
                                    $partners[$key]['preferLocations'] = $this->user->getPartnerPreferLocationByPartnerId($value['id']);
                                    $partners[$key]['roomItems'] = $this->user->getPartnerRoomItemByPartnerId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($partners);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($partners, $st2, 20 );

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('totalPages'=>$totalPages,'list'=>$list)
                                    ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }else{
                         $this->response([
                        'status'  => false,
                        'message' => 'Please provide all parameters',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function createRental_post(){
          # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    extract($_POST);
                    # Valdiate Params #
                    if(isset($propertyTitle) 
                       && isset($propertyAddress)
                       && isset($propertyType)
                       && isset($dealType)
                       && isset($propertyBed)
                       && isset($propertyRoom)
                       && isset($propertyParking)
                       && isset($unitSharing)
                       && isset($roomSharing)
                       && isset($roomSharingGender)
                       && isset($offeringBedRoom)
                       && isset($offeringBathRoom)
                       && isset($offeringRoomIncluded)
                       && isset($alsoSharing)
                       && isset($rentRelationType)
                       && isset($rentBond)
                       && isset($rentBondRequiredStatus)
                       && isset($rentStart)
                       && isset($rentEnd)
                       && isset($rentRequiredStatus)
                       && isset($rent)
                       && isset($coupleAmount)
                       && isset($oneGirlAmount)
                       && isset($oneBoyAmount)
                       && isset($twoGirlAmount)
                       && isset($twoBoyAmount)
                       && isset($rentIncluding)
                       && isset($rentExluding)
                       && isset($nearBy)
                       && isset($dateFrom)
                       && isset($personalNote)
                       && isset($contact)
                       && isset($imageCount)){

                        $insData['userId'] = $decodedTokenCheck['id'];
                        $insData['propertyTitle'] = $propertyTitle;
                        $insData['propertyAddress'] = $propertyAddress;
                        $insData['dealType'] = $dealType;
                        $insData['propertyBed'] = $propertyBed;
                        $insData['propertyRoom']  = $propertyRoom;
                        $insData['propertyParking'] = $propertyParking;
                        $insData['rentRelationType'] = $rentRelationType;
                        $insData['rentBond'] = $rentBond;
                        $insData['rentBondRequiredStatus'] = $rentBondRequiredStatus;
                        $insData['rentStart'] = $rentStart;
                        $insData['rentEnd'] = $rentEnd;
                        $insData['rentRequiredStatus'] = $rentRequiredStatus;
                        $insData['rent'] = $rent;
                        $insData['coupleAmount'] = $coupleAmount;
                        $insData['oneGirlAmount'] = $oneGirlAmount;
                        $insData['oneBoyAmount']  = $oneBoyAmount;
                        $insData['twoBoyAmount']  = $twoBoyAmount;
                        $insData['dateFrom'] = $dateFrom;
                        $insData['rentIncluding'] = $rentIncluding;
                        $insData['rentExluding'] = $rentExluding;
                        $insData['personalNote'] = $personalNote;
                        $insData['contact'] = $contact;

                        $insData['unitSharing'] = $unitSharing;
                        $insData['roomSharing'] = $roomSharing;
                        $insData['roomSharingGender'] = $roomSharingGender;
                        $insData['offeringBedRoom'] = $offeringBedRoom;
                        $insData['alsoSharing'] = $alsoSharing;
                        $insData['offeringBathRoom'] = $offeringBathRoom;
                        $insData['offeringRoomIncluded'] = $offeringRoomIncluded;


                        $insData['addedOn'] = date('Y-m-d H:i:s');
                        $insData['addedOnTimeStamp'] = time();
                        
                        $rentId = $this->user->insertToAnyTable('appUsersRents',$insData);
                        if($rentId){
                            $nearByArr = json_decode($nearBy,true);
                            if(!empty($nearByArr)){
                                foreach($nearByArr as $nearByKey=>$nearByVal){
                                    $insNearbyData['rentId'] = $rentId;
                                    $insNearbyData['nearBy'] = $nearByVal['nearBy'];
                                    $insNearbyData['title'] = $nearByVal['title'];
                                    $insNearbyData['distance'] = $nearByVal['distance'];
                                    $insNearbyData['walkingStatus'] = $nearByVal['walkingStatus'];
                                    $insNearbyData['drivingStatus'] = $nearByVal['drivingStatus'];

                                    $nearById = $this->user->insertToAnyTable('appUsersRentNearBy',$insNearbyData);
                                }

                                $uploads_dir = './uploads/rentImages';
                                for($i=0; $i<$imageCount; $i++){
                                    $tmp_name = $_FILES["image".$i]["tmp_name"];
                                    $imageName =  $this->user->getToken(11).'_'.$_FILES["image".$i]["name"];

                                    move_uploaded_file($tmp_name, "$uploads_dir/$imageName");

                                    $insImageData['rentId'] = $rentId;
                                    $insImageData['path'] = 'uploads/rentImages/'.$imageName;

                                    $imageId = $this->user->insertToAnyTable('appUserRentImages',$insImageData);
                                }
                            }

                            $this->response([
                                'status'  => true,
                                'message' => 'Rent added Successfully',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Unable to add Rent',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function addRentSharing_post(){
           # HEADERS PARAMS #
       $headers = apache_request_headers();

       # Validate Header Params #
       if(isset($headers[$this->tokenHeaderName]) && !empty($headers[$this->tokenHeaderName])){
           # Verify Access Token #
           $decodedToken = AUTHORIZATION::validateTimestamp($headers[$this->tokenHeaderName]);
           $decodedTokenCheck = json_decode(json_encode($decodedToken), True);

           # If Access Token Is Verified or not#
           if ($decodedTokenCheck != []) {
                # Validate Requesting params #
                    extract($_POST);
                    # Valdiate Params #
                    if(isset($name)  && !empty($name) ){

                        $insData['userId'] = $decodedTokenCheck['id'];
                        $insData['name'] = $name;
                        $insData['addedOn'] = date('Y-m-d H:i:s');
                        $insData['addedOnTimeStamp'] = time();


                        if(isset($_FILES["image"]["name"]) && !empty($_FILES["image"]["name"])){
                            $uploads_dir = './uploads/rentSharingImages';
                            $tmp_name = $_FILES["image"]["tmp_name"];
                            $imageName =  $this->user->getToken(11).'_'.$_FILES["image"]["name"];

                            move_uploaded_file($tmp_name, "$uploads_dir/$imageName");

                            $insData['image'] = 'uploads/rentSharingImages/'.$imageName;

                        }
                        
                        $rentSharingId = $this->user->insertToAnyTable('appUsersRentSharing',$insData);
                        if($rentSharingId){

                            $this->response([
                                'status'  => true,
                                'message' => 'Rent Sharing added Successfully',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                'status'  => false,
                                'message' => 'Unable to add Rent Sharing',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_OK);
                        }
                    }else{
                            # Return Invalid Requested Param Response #
                            $this->response([
                                'status'  => false,
                                'message' => 'Please provide all parameters',
                                'data'    => (object)[]
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                
           }else{
                    # Return Token Invalid Response #
                    $this->response([
                        'status'  => false,
                        'message' => 'Token invalid',
                        'data'    => (object)[]
                    ], REST_Controller::HTTP_BAD_REQUEST);
           }
        }else{
                # Return Missing Header Response #
                $this->response([
                    'status'  => false,
                    'message' => 'Token not found',
                    'data'    => (object)[]
                ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
   
 
}

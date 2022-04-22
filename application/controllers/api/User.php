<?php
error_reporting(0);
ini_set('display_errors', 0);

# Include Rest Controller for rest api #
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends REST_Controller {
    public $baseUrl;
    public $tokenHeaderName;
    public $userTokenHeaderName;

    # Default Constructor function #
	public function __construct() {
        parent:: __construct();
        // $this->load->helper(array('form', 'url'));
        $this->load->helper('date');
        $this->load->model("User_model",'user');
        $this->load->helper(['jwt', 'authorization']);

        $this->baseUrl= $_SERVER['SERVER_NAME'];
        $this->tokenHeaderName= 'token';
        $this->userTokenHeaderName= 'sessiontoken' ;
        
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
                            $imageName = '';
                            if(!empty($image)){
                                $imageName = $this->user->uploadBase64Image($image,'./uploads/userProfilePics/');
                                if(empty($imageName)){
                                    $this->response([
                                        'status'  => false,
                                        'message' => 'Error while uploading profile image',
                                        'data'    =>  (Object)[]
                                    ], REST_Controller::HTTP_BAD_REQUEST);
                                }
                            }
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
                                        
                                         
                                }else{
                                        # Return Register Error Response #
                                        $this->response([
                                            'status'  => false,
                                            'message' => 'Error while registering new user',
                                            'data'    => (Object)[]
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

    # @ Login By Email Function #
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
                                $sessionToken = $this->user->getToken(16).$user->id;
                                $updUserData['sessionToken']        = $sessionToken;
                                $updUserData['deviceToken']      = $deviceToken;
                               
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

    # Login By Phone Number Function #
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
                                $sessionToken = $this->user->getToken(16).$user->id;
                                $updUserData['sessionToken']        = $sessionToken;
                                $updUserData['deviceToken']      = $deviceToken;
                             
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
                                    'message' => 'Entered phone number does not registered',
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

    # Social Login Function #
    public function socialLogin_post(){
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
                    if(isset($socialType) && !empty($socialId) && isset($email) && isset($deviceToken) && !empty($deviceToken)){

                        if(!empty($email)){
                            $validateEmail = $this->user->getUserByEmailAndSocialType($email,$socialId);
                            if($validateEmail){
                                $this->response([
                                        'status'  => false,
                                        'message' => 'Email already exist with other platform',
                                        'data'    => (object)[]
                                    ], REST_Controller::HTTP_OK);
                            }
                        }
                        $user = $this->user->getSocialUserBySocialId($socialId,$socialType);
                        if($user){
                                
                                # Remove other user same device token #
                                $updateOtherDeviceToken = $this->user->update_data('appUsers',['deviceToken'=>''],['deviceToken'=>$deviceToken]);
                                
                                # User usertoken and deviceToken update #
                                $sessionToken = $this->user->getToken(16).$user->id;
                                $updUserData['sessionToken']        = $sessionToken;
                                $updUserData['deviceToken']      = $deviceToken;

                                if(isset($firstName) && !empty($firstName)){
                                     $updUserData['firstName'] = $firstName;
                                }

                                if(isset($lastName) && !empty($lastName)){
                                     $updUserData['lastName'] = $lastName;
                                }


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
                                $updateOtherDeviceToken = $this->user->update_data('appUsers',['deviceToken'=>''],['deviceToken'=>$deviceToken]);
                                $userToken = $this->user->getToken(16);

                                $insData['userToken'] = $userToken;
                                $insData['socialId'] = $socialId;
                                $insData['socialType'] = $socprofile_ialType;
                                $insData['deviceToken'] = $deviceToken;
                                $insData['email'] = $email;
                                $sessionToken = $this->user->getToken(16);
                                $insData['sessionToken']        = $sessionToken;
                                $insData['addedOn']        = date('Y-m-d H:i:s');
                                $insData['addedOnTimeStamp']        = time();

                                if(isset($firstName) && !empty($firstName)){
                                     $insData['firstName'] = $firstName;
                                }

                                if(isset($lastName) && !empty($lastName)){
                                     $insData['lastName'] = $lastName;
                                }


                                $userId = $this->user->insertToAnyTable('appUsers',$insData);
                                $insData['emailVerificationStatus']        = "1";
                                $insData['countryCode']        = "";
                                $insData['phoneNumber']        = "";

                                $this->response([
                                        'status'  => true,
                                        'message' => 'Login Successfully',
                                        'data'    => $insData
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

    # Get Profile Function #
    public function profile_get(){
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
                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                    $decodedTokenCheck = json_decode(json_encode($loginCheck),true);
                    
                            $this->response([
                                            'status'  => true,
                                            'message' => 'Successfuly',
                                            'data'    => $loginCheck
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

    # Update User Profile Function #
    public function updateUserProfile_post(){
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
                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                            $jsonArray = json_decode(file_get_contents('php://input'),true); 

                            extract($jsonArray);

                            if(isset($firstName) && !empty($firstName) && isset($lastName) && !empty($lastName)){
                                $updData['firstName'] = $firstName;
                                $updData['lastName'] = $lastName;

                                $updateUser = $this->user->update_data('appUsers',$updData,['id'=>$loginCheck->id]);

                                $this->response([
                                            'status'  => true,
                                            'message' => 'Successfully',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
                            }else{  
                                $this->response([
                                            'status'  => false,
                                            'message' => 'Please provide all parameters',
                                            'data'    =>  (object)[]
                                        ], REST_Controller::HTTP_OK);
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

    # Update User Profile Image Function #
    public function updateUserProfileImage_post(){
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
                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                            $jsonArray = json_decode(file_get_contents('php://input'),true); 

                            extract($jsonArray);

                            if(isset($image) && !empty($image) ){
                                $imageName = $this->user->uploadBase64Image($image,'./uploads/userProfilePics/');

                                if(!empty($imageName)){
                                    @unlink('./uploads/userProfilePics/'.$loginCheck->image);
                                    $updData['image'] = $imageName;

                                    $updateUser = $this->user->update_data('appUsers',$updData,['id'=>$loginCheck->id]);

                                    $this->response([
                                                'status'  => true,
                                                'message' => 'Successfully',
                                                'data'    => (object)[]
                                            ], REST_Controller::HTTP_OK);
                                }else{
                                     $this->response([
                                                'status'  => false,
                                                'message' => 'Invalid Image format provided',
                                                'data'    => (object)[]
                                            ], REST_Controller::HTTP_OK);
                                }
                            }else{  
                                $this->response([
                                            'status'  => false,
                                            'message' => 'Please provide all parameters',
                                            'data'    =>  (object)[]
                                        ], REST_Controller::HTTP_OK);
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

    # Change Password Function #
    public function changePassword_post(){
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
                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                    if(isset($oldPassword) && !empty($oldPassword) && isset($newPassword) && !empty($newPassword)){
                        $userDetails = $this->user->select_single_row('appUsers','id',$loginCheck->id);
                        if(md5($oldPassword) == $userDetails->password){
                            $updData['password'] = md5($newPassword);
                            $updateUser = $this->user->update_data('appUsers',$updData,['userToken'=>$loginCheck->userToken]);

                            $this->response([
                                            'status'  => true,
                                            'message' => 'Successfully',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
                        }else{
                            $this->response([
                                            'status'  => false,
                                            'message' => 'Old password does not matched',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
                        }
                    }else{
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Please provide all parameters',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
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

    # User Mobile Update Function # 
    public function userMobileUpdate_get(){
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
                    extract($_GET);
                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                    if(isset($countryCode) && !empty($countryCode) && isset($phoneNumber) && !empty($phoneNumber)){
                        $userDetails = $this->user->select_single_row('appUsers','id',$loginCheck->id);
                       
                            $updData['countryCode'] = $countryCode;
                            $updData['phoneNumber'] = $phoneNumber;
                            $updateUser = $this->user->update_data('appUsers',$updData,['id'=>$loginCheck->id]);
                       
                            $this->response([
                                            'status'  => true,
                                            'message' => 'Successfully',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
                       
                    }else{
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Please provide all parameters',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
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

    # Add User Room Item Function #
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                # Remove other user same device token #
                                
                                # User usertoken and deviceToken update #
                                $insData['userId']        = $loginCheck['id'];
                                $insData['name']        = $itemName;
                                $insData['iconName']        = $iconName;

                             

                                $roomItemId = $this->user->insertToAnyTable('appUserRoomItems',$insData);
                                 $insData['id'] = $roomItemId;
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Item added Successfully',
                                        'data'    => $insData
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

    # View User Room Item Function #
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                $roomItems = $this->user->getUserRoomItemsByUserId($loginCheck['id']);
                               
                                    
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

    # Create Partner Function #
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
                    if(isset($relationshipId) 
                        && isset($propertyTitle) 
                        && isset($propertyTypeIds) 
                        && isset($dealTypeIds) 
                        && isset($bedRoomCount) 
                        && isset($bathRoomCount) 
                        && isset($parkingCount) 
                        && isset($movingDate)  
                        && isset($preferLocations) 
                        && isset($preferBedRoomIds)  
                        && isset($preferBathRoomIds) 
                        && isset($roomIncludedIds) 
                        && isset($roomItemIds) 
                        && isset($budgetFrom) 
                        && isset($fixRentStatus) 
                        && isset($budgetTo) 
                        && isset($rent)  
                        && isset($noOfPeople) 
                        && isset($numberOfKid) 
                        && isset($petStatus) 
                        && isset($smokingStatus) 
                        && isset($drinkingStatus) 
                        && isset($personalNote) 
                        && isset($contactTypeId)) 
                    {
                        $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                        $loginCheck = json_decode(json_encode($loginCheck),true);
                        if(!$loginCheck){
                            $this->response([
                                                'status'  => false,
                                                'message' => 'Session expired, Please relogin',
                                                'data'    => (object)[]
                                            ], 401);
                            die();
                        }

                        $insData['userId'] = $loginCheck['id'];
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
                        $insData['roomItemIds'] = $roomItemIds;
                        $insData['budgetFrom'] = $budgetFrom;
                        $insData['budgetTo'] = $budgetTo;
                        $insData['budgetRent'] = $rent;
                        $insData['fixRentStatus'] = $fixRentStatus;
                        $insData['noOfPeople']  = $noOfPeople;
                        

                        if(isset($editId) && !empty($editId)){
                            $partnerUpdate = $this->user->update_data('appUserPartners',$insData,['id'=>$editId]);

                            $partnerId = $editId;

                        }else{
                            $insData['addedOn'] = date('Y-m-d H:i:s');
                            $insData['addedOnTimeStamp'] = time();
                            $partnerId = $this->user->insertToAnyTable('appUserPartners',$insData);

                        }
                        
                
                            $insPartnerDetailData['partnerId'] = $partnerId;
                            $insPartnerDetailData['numberOfKid'] = $numberOfKid;
                            $insPartnerDetailData['petStatus'] = $petStatus;
                            $insPartnerDetailData['smokingStatus'] = $smokingStatus;
                            $insPartnerDetailData['drinkingStatus'] = $drinkingStatus;
                            $insPartnerDetailData['personalNote'] = $personalNote;
                            $insPartnerDetailData['contactId'] = $contactTypeId;

                            if(isset($editId) && !empty($editId)){
                                $partnerDetailId = $this->user->update_data('appUserPartnerDetails',$insPartnerDetailData,['partnerId'=>$editId]);

                            }else{
                                $partnerDetailId = $this->user->insertToAnyTable('appUserPartnerDetails',$insPartnerDetailData);

                            }

                            if(isset($editId) && !empty($editId)){
                                    $this->user->deleteFromAnyTable('appUserPartnerPreferLocations',['partnerId'=>$editId]);
                            }

                            if(!empty($preferLocations)){
                                

                                foreach($preferLocations as $key=>$value){
                                    $insLocData['partnerId'] = $partnerId;
                                    $insLocData['location'] = $value['location'];
                                    $insLocData['latitude'] = $value['latitude'];
                                    $insLocData['longitude'] = $value['longitude'];

                                    $locationId = $this->user->insertToAnyTable('appUserPartnerPreferLocations',$insLocData);
                                }

                            }

                            if(isset($editId) && !empty($editId)){
                                    $this->user->deleteFromAnyTable('appUserPartnerItems',['partnerId'=>$editId]);
                            }

                            if(!empty($roomItemIds)){

                                $roomItemIdArr = explode(',', $roomItemIds);
                                foreach($roomItemIdArr as $roomItem){
                                    if(is_numeric($roomItem)){
                                        $insItemData['partnerId'] = $partnerId;
                                        $insItemData['roomItemId'] = $roomItem;
                                        $partnerItemId = $this->user->insertToAnyTable('appUserPartnerItems',$insItemData);
                                    }
                                    
                                }
                            }

                            $this->response([
                                'status'  => true,
                                'message' => 'Partner added Successfully',
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

    # Delete Partner Function #
    public function deletePartner_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                                $partners = $this->user->update_data('appUserPartners',['deleteStatus'=>'1'],['id'=>$partnerId]);
                            
                            

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
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

    # Get Parnters Function #
    public function partners_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                            if(isset($location) && isset($dealType)){
                                $partners = $this->user->getPartnersNotIn($loginCheck['id'],$location,$dealType);
                            }else{
                                $partners = $this->user->getPartnersNotIn($loginCheck['id']);
                            }

                            if($partners){
                                foreach($partners as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $partners[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
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

    # My Parnter Function # 
    public function myPartners_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                                $partners = $this->user->getPartnersIn($loginCheck['id']);
                            
                            if($partners){
                                foreach($partners as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $partners[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
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

    # Add Remove Partner Fav Function #
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $partnerDetails = $this->user->getUserFavPartnerById($loginCheck['id'],$partnerId);
                            if($partnerDetails){
                                $this->user->deleteFromAnyTable('appUserFavPartners',['id'=>$partnerDetails->id]);
                                $message = "Partner Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $loginCheck['id'];
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

    # View Fav Partners Function #
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $partners = $this->user->getUserFavPartners($loginCheck['id']);
                            
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

    # Create Rental Function #
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
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 

                    extract($jsonArray);
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
                       && isset($longitude)
                       && isset($latitude)
                       && isset($imageCount)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], REST_Controller::HTTP_OK);
                                die();
                            }

                        $insData['userId'] = $loginCheck['id'];
                        $insData['propertyTitle'] = $propertyTitle;
                        $insData['propertyType'] = $propertyType;

                        $insData['propertyAddress'] = $propertyAddress;
                        $insData['latitude'] = $latitude;
                        $insData['longitude'] = $longitude;
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
                        $insData['twoGirlAmount'] = $twoGirlAmount;

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


                        
                        

                        if(isset($editId) && !empty($editId)){
                            $rentUpdate = $this->user->update_data('appUsersRents',$insData,['id'=>$editId]);
                            $rentId = $editId;
                        }else{
                            $insData['addedOn'] = date('Y-m-d H:i:s');
                            $insData['addedOnTimeStamp'] = time();
                            $rentId = $this->user->insertToAnyTable('appUsersRents',$insData);
                        }

                        
                            if(isset($editId) && !empty($editId)){
                                $this->user->deleteFromAnyTable('appUsersRentNearBy',['rentId'=>$editId]);
                            }

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

                                if(isset($deleteImageIds) && !empty($deleteImageIds)){
                                    $imageIds = explode(',',$deleteImageIds);

                                    foreach($imageIds as $deleteImageId){
                                        $this->user->deleteFromAnyTable('appUserRentImages',['id'=>$deleteImageId]);
                                    }
                                    

                                    if(isset($deleteImagesPath) && !empty($deleteImagesPath)){
                                        $imagePaths = explode(',',$deleteImagesPath);

                                        foreach($imagePaths as $deleteImagePath){
                                            @unlink('./'.$deleteImagePath);
                                        }
                                    }
                                }

                                $uploads_dir = './uploads/rentImages';
                                for($i=0; $i<$imageCount; $i++){
                                    $imageName =  $this->user->saveBase64ImagePng($jsonArray['image'.$i],'./uploads/rentImages/');


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

    # Add Rent Sharing Function #
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
                    $jsonArray = json_decode(file_get_contents('php://input'),true); 

                # Validate Requesting params #
                    extract($jsonArray);
                    # Valdiate Params #
                    if(isset($name)  && !empty($name) ){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                        $insData['userId'] = $loginCheck['id'];
                        $insData['name'] = $name;
                       
                        if(isset($iconName) && !empty($iconName)){
                            $insData['iconName'] = $iconName;
                        }

                        $insData['addedOn'] = date('Y-m-d H:i:s');
                        $insData['addedOnTimeStamp'] = time();


                       
                        
                        $rentSharingId = $this->user->insertToAnyTable('appUsersRentSharing',$insData);
                        if($rentSharingId){
                            $insData['id'] = $rentSharingId;
                            $this->response([
                                'status'  => true,
                                'message' => 'Rent Sharing added Successfully',
                                'data'    => $insData
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

    # Add Rent Including Function #
    public function addRentIncluding_post(){
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
                    if(isset($name)  && !empty($name) && isset($type) && !empty($type)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                        $insData['userId'] = $loginCheck['id'];
                        $insData['name'] = $name;
                        $insData['type'] = $type;
                       
                        if(isset($iconName) && !empty($iconName)){
                            $insData['iconName'] = $iconName;
                        }

                        $insData['addedOn'] = date('Y-m-d H:i:s');
                        $insData['addedOnTimeStamp'] = time();


                        
                        $rentSharingId = $this->user->insertToAnyTable('appUsersRentIncludingsAndExcludings',$insData);
                        if($rentSharingId){
                            $insData['id'] = $rentSharingId;

                            $this->response([
                                'status'  => true,
                                'message' => 'Successfully',
                                'data'    => $insData
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

    # Get Rentals Function #
    public function rentals_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            if(isset($location) && isset($propertyType) && isset($dealType)){
                                $rents = $this->user->getRentalNotIn($loginCheck['id'],$location,$propertyType,$dealType);
                            }else{
                                $rents = $this->user->getRentalNotIn($loginCheck['id']);
                            }
                            if($rents){
                                foreach($rents as $key=>$value){
                                    $alsoSharing = ($value['alsoSharing'] != '') ? $value['alsoSharing'] : 0; 

                                    $arrAlsoSharing = explode(',',$alsoSharing);
                                    $alsoPushedArr = array();
                                    foreach($arrAlsoSharing as $alsoKey=>$alsoVal){
                                        if(is_numeric($alsoVal)){
                                            $alsoSharingDetails = $this->user->getRentSharingById($alsoVal);
                                            if($alsoSharingDetails){
                                                array_push($alsoPushedArr,$alsoSharingDetails);
                                            }
                                        }
                                        
                                    }
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $rents[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $rents[$key]['alsoSharings'] = $alsoPushedArr;

                                    $arrRentExluding = explode(',',$value['rentExluding']);
                                    $rentExludingPushedArr = array();
                                    foreach($arrRentExluding as $excludingKey=>$excludingVal){
                                        if(is_numeric($excludingVal)){
                                            $excludingDetails = $this->user->getExcludingsAndIncludingsByTypeAndId($excludingVal,2);
                                            if($excludingDetails){
                                                array_push($rentExludingPushedArr,$excludingDetails);
                                            }
                                        }
                                        
                                    }
                                    $rents[$key]['excludings'] = $rentExludingPushedArr;

                                    $arrRentIncludings = explode(',',$value['rentIncluding']);
                                    $rentIncludingPushedArr = array();
                                    foreach($arrRentIncludings as $includingKey=>$includingVal){
                                        if(is_numeric($includingVal)){
                                            $includingDetails = $this->user->getExcludingsAndIncludingsByTypeAndId($includingVal,1);
                                            if($includingDetails){
                                                array_push($rentIncludingPushedArr,$includingDetails);
                                            }
                                        }
                                        
                                    }
                                    $rents[$key]['includings'] = $rentIncludingPushedArr;
                                    $rents[$key]['rentalImages'] = $this->user->getRentalImages($value['id']);
                                    $rents[$key]['nearBy'] = $this->user->getRentNearBy($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($rents);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($rents, $st2, 20 );

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

    # Delete Rental Function #
    public function deleteRental_get(){
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
                        if(isset($rentalId) && !empty($rentalId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                                $rents = $this->user->update_data('appUsersRents',['deleteStatus'=>'1'],['id'=>$rentalId]);
                          

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
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

    # My Rentals Function #
    public function myRentals_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                                $rents = $this->user->getRentalIn($loginCheck['id']);
                            if($rents){
                                foreach($rents as $key=>$value){
                                    $alsoSharing = ($value['alsoSharing'] != '') ? $value['alsoSharing'] : 0; 

                                    $arrAlsoSharing = explode(',',$alsoSharing);
                                    $alsoPushedArr = array();
                                    foreach($arrAlsoSharing as $alsoKey=>$alsoVal){
                                        if(is_numeric($alsoVal)){
                                            $alsoSharingDetails = $this->user->getRentSharingById($alsoVal);
                                            if($alsoSharingDetails){
                                                array_push($alsoPushedArr,$alsoSharingDetails);
                                            }
                                        }
                                        
                                    }
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $rents[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $rents[$key]['alsoSharings'] = $alsoPushedArr;

                                    $arrRentExluding = explode(',',$value['rentExluding']);
                                    $rentExludingPushedArr = array();
                                    foreach($arrRentExluding as $excludingKey=>$excludingVal){
                                        if(is_numeric($excludingVal)){
                                            $excludingDetails = $this->user->getExcludingsAndIncludingsByTypeAndId($excludingVal,2);
                                            if($excludingDetails){
                                                array_push($rentExludingPushedArr,$excludingDetails);
                                            }
                                        }
                                        
                                    }
                                    $rents[$key]['excludings'] = $rentExludingPushedArr;

                                    $arrRentIncludings = explode(',',$value['rentIncluding']);
                                    $rentIncludingPushedArr = array();
                                    foreach($arrRentIncludings as $includingKey=>$includingVal){
                                        if(is_numeric($includingVal)){
                                            $includingDetails = $this->user->getExcludingsAndIncludingsByTypeAndId($includingVal,1);
                                            if($includingDetails){
                                                array_push($rentIncludingPushedArr,$includingDetails);
                                            }
                                        }
                                        
                                    }
                                    $rents[$key]['includings'] = $rentIncludingPushedArr;
                                    $rents[$key]['rentalImages'] = $this->user->getRentalImages($value['id']);
                                    $rents[$key]['nearBy'] = $this->user->getRentNearBy($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($rents);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($rents, $st2, 20 );

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

    # Add Remove Rental Fav Function # 
    public function addRemoveRentalFav_get(){
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
                        if(isset($rentId) && !empty($rentId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $rentDetails = $this->user->getUserFavRentById($loginCheck['id'],$rentId);
                            if($rentDetails){
                                $this->user->deleteFromAnyTable('appUserFavRents',['id'=>$rentDetails->id]);
                                $message = "Rental Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $loginCheck['id'];
                                $insData['rentId'] = $rentId;

                                $favId = $this->user->insertToAnyTable('appUserFavRents',$insData);
                                $message = "Rental Favourite Successfully";
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

    # View Also Sharings Function #
    public function viewAlsoSharings_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $sharings = $this->user->getAlsoSharing($loginCheck['id']);
                            
                            

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('totalPages'=>1,'list'=>$sharings)
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

    # View Includings or excludings Function #
    public function viewIncludingsOrExcludings_get(){
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
                    extract($_GET);
                    # Valdiate Params #
                    if(isset($type) && !empty($type)){
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }   
                                $list = $this->user->getExcludingsAndIncludingsByType($type);
                               
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('totalPages'=>1,'list'=>$list)
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

    # View User Fav Rentals Functions #
    public function viewUserFavRentals_get(){
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
                    extract($_GET);
                    # Valdiate Params #
                    if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $rents = $this->user->getUserFavRental($loginCheck['id']);
                            if($rents){
                                foreach($rents as $key=>$value){
                                    $alsoSharing = ($value['alsoSharing'] == '') ? $value['alsoSharing'] : 0; 
                                    $rents[$key]['preferLocations'] = $this->user->getRentSharingByIn($alsoSharing);
                                    $rents[$key]['excludings'] = $this->user->getExcludingsAndIncludings($value['rentExluding']);
                                    $rents[$key]['includings'] = $this->user->getExcludingsAndIncludings($value['rentIncluding']);
                                    $rents[$key]['rentalImages'] = $this->user->getRentalImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($rents);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($rents, $st2, 20 );

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

    # Get User By Email Function  #
    public function getUserByEmail_post(){
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
                    if(isset($email) && !empty($email)){
                            $user = $this->user->getUserByEmail($email);

                            if($user){

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('userToken'=>$user->userToken,'phoneNumber'=>(string)($user->countryCode.$user->phoneNumber))
                                    ], REST_Controller::HTTP_OK);
                            }else{

                                    $this->response([
                                        'status'  => false,
                                        'message' => 'Entered email is not registered',
                                        'data'    => array('userToken'=>"0",'phoneNumber'=>"0")
                                    ], REST_Controller::HTTP_OK);
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

    # Reset Password Function #
    public function resetPassword_post(){
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
                    $loginCheck = $this->user->getUserByUserTok($headers[$this->userTokenHeaderName]);
                    if(!$loginCheck){
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Session expired, Please relogin',
                                            'data'    => (object)[]
                                        ], 401);
                        die();
                    }
                    if(isset($newPassword) && !empty($newPassword)){
                        $userDetails = $this->user->select_single_row('appUsers','id',$loginCheck->id);
                            $updData['password'] = md5($newPassword);
                            $updateUser = $this->user->update_data('appUsers',$updData,['userToken'=>$loginCheck->userToken]);

                            $this->response([
                                            'status'  => true,
                                            'message' => 'Successfully',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
                        
                    }else{
                        $this->response([
                                            'status'  => false,
                                            'message' => 'Please provide all parameters',
                                            'data'    => (object)[]
                                        ], REST_Controller::HTTP_OK);
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

    # Create Job Function # 
    public function createJob_post(){
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
                    if(isset($jobTitle) 
                        && isset($location) 
                        && isset($latitude) 
                        && isset($longitude) 
                        && isset($role) 
                        && isset($categoriesIds) 
                        && isset($jobDetails) 
                        && isset($totalNoEmployee)  
                        && isset($gender) 
                        && isset($jobTypes)  
                        && isset($workingDays) 
                        && isset($hourlyRate) 
                        && isset($paymentMethod)  )
                    {
                        $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                        $loginCheck = json_decode(json_encode($loginCheck),true);
                        if(!$loginCheck){
                            $this->response([
                                                'status'  => false,
                                                'message' => 'Session expired, Please relogin',
                                                'data'    => (object)[]
                                            ],401);
                            die();
                        }

                        $insData['userId'] = $loginCheck['id'];
                        $insData['jobTitle'] = $jobTitle;
                        $insData['location'] = $location;
                        $insData['latitude'] = $latitude;
                        $insData['longitude'] = $longitude;
                        $insData['role'] = $role;
                        $insData['categoriesIds']  = $categoriesIds;
                        $insData['jobDetails'] = $jobDetails;
                        $insData['totalNoEmployee'] = $totalNoEmployee;
                        $insData['gender'] = $gender;
                        $insData['jobTypes'] = $jobTypes;
                        $insData['workingDays'] = $workingDays;
                        $insData['hourlyRate'] = $hourlyRate;
                        $insData['paymentMethod'] = $paymentMethod;
                        

                        if(isset($editId) && !empty($editId)){
                            $jobUpdate = $this->user->update_data('appUserJobs',$insData,['id'=>$editId]);
                            $message = "Job edited successfully";
                            $jobId = $editId;

                        }else{
                            $insData['addedOn'] = date('Y-m-d H:i:s');
                            $insData['addedOnTimeStamp'] = time();
                            $jobId = $this->user->insertToAnyTable('appUserJobs',$insData);
                            $message = "Job added successfully";

                        }
                        
                        
                            
                            if(isset($editId) && !empty($editId)){
                                $this->user->deleteFromAnyTable('appUserJobCategories',['jobId'=>$editId]);
                            }

                            if(!empty($categoriesIds)){
                                $categoriesIdArr = explode(',', $categoriesIds);
                                foreach($categoriesIdArr as $catId){
                                    if(is_numeric($catId)){
                                        $insItemData['jobId'] = $jobId;
                                        $insItemData['categoryId'] = $catId;
                                        $jobCategoryId = $this->user->insertToAnyTable('appUserJobCategories',$insItemData);
                                    }
                                    
                                }
                            }

                            $this->response([
                                'status'  => true,
                                'message' => $message,
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

    # Delete Job Function #
    public function deleteJob_get(){
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
                    extract($_GET);
                    # Valdiate Params #
                    if(isset($jobId) && !empty($jobId)){
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                             

                                $catId = $this->user->update_data('appUserJobs',['deleteStatus'=>'1'],['id'=>$jobId]);
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
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

    # Add User Job Category Function #
    public function addUserJobCategory_post(){
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
                    if(isset($categoryName) && !empty($categoryName)){
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                # Remove other user same device token #
                                
                                # User usertoken and deviceToken update #
                                $insData['userId']        = $loginCheck['id'];
                                $insData['name']        = $categoryName;
                                $insData['iconName']        = $iconName;

                                

                                $catId = $this->user->insertToAnyTable('appUserDefineCategories',$insData);
                                 $insData['id'] = $catId;
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Category added Successfully',
                                        'data'    => $insData
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

    # Get Jobs Function #
    public function jobs_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                          
                                $jobs = $this->user->getJobByNotIn($loginCheck['id']);
                            
                            if($jobs){
                                foreach($jobs as $key=>$value){

                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $jobs[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;

                                    
                                    $jobs[$key]['jobCategories'] = $this->user->getJobCategoriesByJobId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalRecordCount=count($jobs);
                            $totalPages = ceil($totalRecordCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($jobs, $st2, 20 );

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

    # Filtered Jobs Function #
    public function filteredJobs_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber) && isset($search) && isset($categoriesIds)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            
                                $jobs = $this->user->getJobByNotInWithFilter($loginCheck['id'],$search,$categoriesIds);
                            
                            if($jobs){
                                foreach($jobs as $key=>$value){

                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $jobs[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;

                                    
                                    $jobs[$key]['jobCategories'] = $this->user->getJobCategoriesByJobId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalRecordCount=count($jobs);
                            $totalPages = ceil($totalRecordCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($jobs, $st2, 20 );

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

    # My Jobs Function #
    public function myJobs_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                            $jobs = $this->user->getJobByUserIdIn($loginCheck['id']);
                            if($jobs){
                                foreach($jobs as $key=>$value){

                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $jobs[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;

                                    
                                    $jobs[$key]['jobCategories'] = $this->user->getJobCategoriesByJobId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($jobs);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($jobs, $st2, 20 );

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

    # View User JOb Categories Function #
    public function viewUserJobCategories_get(){
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                $categories = $this->user->getUserJobCategoriesByUserId($loginCheck['id']);
                               
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
                                        'data'    => array('items'=>$categories)
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

    # Add Remove Job Fav Function #
    public function addRemoveJobFav_get(){
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
                        if(isset($jobId) && !empty($jobId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $jobDetails = $this->user->getUserFavJobById($loginCheck['id'],$jobId);
                            if($jobDetails){
                                $this->user->deleteFromAnyTable('appUserFavJobs',['id'=>$jobDetails->id]);
                                $message = "Job Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $loginCheck['id'];
                                $insData['jobId'] = $jobId;

                                $favId = $this->user->insertToAnyTable('appUserFavJobs',$insData);
                                $message = "job Favourite Successfully";
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

    # View Fav Jobs Function #
    public function viewFavJobs_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);

                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                           
                                $jobs = $this->user->getUserFavJobs($loginCheck['id']);
                            
                            if($jobs){
                                foreach($jobs as $key=>$value){

                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $jobs[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;

                                    
                                    $jobs[$key]['jobCategories'] = $this->user->getJobCategoriesByJobId($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalRecordCount=count($jobs);
                            $totalPages = ceil($totalRecordCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($jobs, $st2, 20 );

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

    # Create Buy and sell Function #
    public function createBuyAndSell_post(){
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
                    if(isset($propertyTitle) 
                        && isset($productCategoryIds) 
                        && isset($imageCount) 
                        && isset($productPrice) 
                        && isset($productCondition) 
                        && isset($productNote) 
                        && isset($deliveryStatus)  
                        && isset($pickupStatus)
                        && isset($deliveryFreeStatus)
                        && isset($pickupAddress)
                        && isset($deliveryPrice) ) 
                    {
                        $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                        $loginCheck = json_decode(json_encode($loginCheck),true);
                        if(!$loginCheck){
                            $this->response([
                                                'status'  => false,
                                                'message' => 'Session expired, Please relogin',
                                                'data'    => (object)[]
                                            ], 401);
                            die();
                        }

                        $insData['userId'] = $loginCheck['id'];
                        $insData['propertyTitle'] = $propertyTitle;
                        $insData['productCategoryIds'] = $productCategoryIds;
                        $insData['productPrice'] = $productPrice;
                        $insData['productCondition'] = $productCondition;
                        $insData['productNote'] = $productNote;
                        $insData['deliveryStatus'] = $deliveryStatus;
                        $insData['deliveryFreeStatus'] = $deliveryFreeStatus;
                        $insData['pickupStatus'] = $pickupStatus;

                        $insData['deliveryPrice'] = $deliveryPrice;
                        $insData['pickupAddress'] = $pickupAddress;

                        if(isset($pickupLatitude) && !empty($pickupLatitude)){
                             $insData['pickupLatitude'] = $pickupLatitude;
                        }

                        if(isset($pickupLongitude) && !empty($pickupLongitude)){
                            $insData['pickupLongitude'] = $pickupLongitude;
                        }

                        
                        if(isset($editId) && !empty($editId)){
                            $buySellUpdate = $this->user->update_data('appUserBuyAndSells',$insData,['id'=>$editId]);

                            $buySellId = $editId;

                        }else{
                            $insData['addedOn'] = date('Y-m-d H:i:s');
                            $insData['addedOnTimeStamp'] = time();
                            
                            
                            $buySellId = $this->user->insertToAnyTable('appUserBuyAndSells',$insData);
                        }
                        
                        
                            
                            if(isset($deleteImageIds) && !empty($deleteImageIds)){
                                    $imageIds = explode(',',$deleteImageIds);

                                    foreach($imageIds as $deleteImageId){
                                        $this->user->deleteFromAnyTable('appUserBuyAndSellImages',['id'=>$deleteImageId]);
                                    }
                                    

                                    if(isset($deleteImagesPath) && !empty($deleteImagesPath)){
                                        $imagePaths = explode(',',$deleteImagesPath);

                                        foreach($imagePaths as $deleteImagePath){
                                            @unlink('./'.$deleteImagePath);
                                        }
                                    }
                            }

                            $uploads_dir = './uploads/rentImages';
                                for($i=0; $i<$imageCount; $i++){
                                    $imageName =  $this->user->saveBase64ImagePng($jsonArray['image'.$i],'./uploads/buySellImages/');


                                    $insImageData['buySellId'] = $buySellId;
                                    $insImageData['path'] = 'uploads/buySellImages/'.$imageName;

                                    $imageId = $this->user->insertToAnyTable('appUserBuyAndSellImages',$insImageData);
                                }

                            

                            $this->response([
                                'status'  => true,
                                'message' => 'Buy Sell added Successfully',
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

    # Delete Buy and Sell Function  #
    public function deleteBuyAndSell_get(){
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
                        if(isset($buySellId) && !empty($buySellId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                            $buySellUpdate = $this->user->update_data('appUserBuyAndSells',['deleteStatus'=>'1'],['id'=>$buySellId]);
                            

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
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

    # Buy And Sell Function  #
    public function buyAndSells_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                           
                                $buySells = $this->user->getBuySellNotIn($loginCheck['id']);
                           

                            if($buySells){
                                foreach($buySells as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $buySells[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    
                                    $buySells[$key]['images'] = $this->user->getBuySellImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($buySells);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($buySells, $st2, 20 );

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
    
    # Add Remove Buy sell fav Function #
    public function addRemoveBuySellFav_get(){
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
                        if(isset($buySellId) && !empty($buySellId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $buysellDetails = $this->user->getUserFavBuySellByUserIdAndBuySellId($loginCheck['id'],$buySellId);
                            if($buysellDetails){
                                $this->user->deleteFromAnyTable('appUserFavBuyAndSells',['id'=>$buysellDetails->id]);
                                $message = "Buy Sell Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $loginCheck['id'];
                                $insData['buySellId'] = $buySellId;

                                $favId = $this->user->insertToAnyTable('appUserFavBuyAndSells',$insData);
                                $message = "Buy Sell Favourite Successfully";
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

    # View Fav Buy Sell Function  #
    public function viewFavBuySells_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $buySells = $this->user->getUserFavBuySells($loginCheck['id']);
                            
                            if($buySells){
                                foreach($buySells as $key=>$value){
                                     $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                     $buySells[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                     $buySells[$key]['images'] = $this->user->getBuySellImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($buySells);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($buySells, $st2, 20 );

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
    
    # My Buy Sell Function  #
    public function myBuySells_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                          
                                $buySells = $this->user->getBuySellInUserId($loginCheck['id']);
                           

                            if($buySells){
                                foreach($buySells as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $buySells[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    
                                    $buySells[$key]['images'] = $this->user->getBuySellImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($buySells);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($buySells, $st2, 20 );

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

    # Filtered Buy and Sell Function #
    public function filteredBuyAndSells_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber) && isset($search) && isset($categoriesIds)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                            
                                $buySells = $this->user->getBuySellNotInAndFilter($loginCheck['id'],$search,$categoriesIds);
                            

                            if($buySells){
                                foreach($buySells as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $buySells[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    
                                    $buySells[$key]['images'] = $this->user->getBuySellImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($buySells);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($buySells, $st2, 20 );

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

    # Create Service Function #
    public function createService_post(){
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
                    if(isset($businessName) 
                        && isset($address) 
                        && isset($latitude) 
                        && isset($longitude) 
                        && isset($companyName) 
                        && isset($phoneNumber) 
                        && isset($email)  
                        && isset($website)
                        && isset($serviceAreaIds)
                        && isset($hourJson)
                        && isset($buisnessCategoryIds)
                        && isset($aboutUs) 
                        && isset($imageCount)) 
                    {
                        $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                        $loginCheck = json_decode(json_encode($loginCheck),true);
                        if(!$loginCheck){
                            $this->response([
                                                'status'  => false,
                                                'message' => 'Session expired, Please relogin',
                                                'data'    => (object)[]
                                            ], 401);
                            die();
                        }

                        $insData['userId'] = $loginCheck['id'];
                        $insData['businessName'] = $businessName;
                        $insData['address'] = $address;
                        $insData['latitude'] = $latitude;
                        $insData['longitude'] = $longitude;
                        $insData['companyName'] = $companyName;
                        $insData['phoneNumber'] = $phoneNumber;
                        $insData['email'] = $email;
                        $insData['website'] = $website;

                        $insData['serviceAreaIds'] = $serviceAreaIds;
                        $insData['hourJson'] = json_encode($hourJson);
                        $insData['buisnessCategoryIds'] = $buisnessCategoryIds;
                        $insData['aboutUs'] = $aboutUs;
                        

                        if(isset($editId) && !empty($editId)){
                            $serviceUpdate = $this->user->update_data('appUserServices',$insData,['id'=>$editId]);

                            $serviceId = $editId;

                        }else{
                            $insData['addedOn'] = date('Y-m-d H:i:s');
                            $insData['addedOnTimeStamp'] = time();
                            
                            
                            $serviceId = $this->user->insertToAnyTable('appUserServices',$insData);
                        }
                        
                       

                                if(isset($deleteImageIds) && !empty($deleteImageIds)){
                                            $imageIds = explode(',',$deleteImageIds);

                                            foreach($imageIds as $deleteImageId){
                                                $this->user->deleteFromAnyTable('appUserServiceImages',['id'=>$deleteImageId]);
                                            }
                                            

                                            if(isset($deleteImagesPath) && !empty($deleteImagesPath)){
                                                $imagePaths = explode(',',$deleteImagesPath);

                                                foreach($imagePaths as $deleteImagePath){
                                                    @unlink('./'.$deleteImagePath);
                                                }
                                            }
                                }

                                $uploads_dir = './uploads/serviceImages';
                                for($i=0; $i<$imageCount; $i++){
                                    $imageName =  $this->user->saveBase64ImagePng($jsonArray['image'.$i],'./uploads/serviceImages/');


                                    $insImageData['serviceId'] = $serviceId;
                                    $insImageData['path'] = 'uploads/serviceImages/'.$imageName;

                                    $imageId = $this->user->insertToAnyTable('appUserServiceImages',$insImageData);
                                }
                           
                            
                            
                            if(isset($editId) && !empty($editId)){
                                $this->user->deleteFromAnyTable('appUserServiceBuisnessCategories',['serviceId'=>$serviceId]);

                            }
                           
                            if(!empty($buisnessCategoryIds)){
                                $buisnessCategoryIdArr = explode(',', $buisnessCategoryIds);
                                foreach($buisnessCategoryIdArr as $bussId){
                                    if(is_numeric($bussId)){
                                        $insCatData['serviceId'] = $serviceId;
                                        $insCatData['buisnessCategoryId'] = $bussId;
                                        $serviceItemId = $this->user->insertToAnyTable('appUserServiceBuisnessCategories',$insCatData);
                                    }
                                    
                                }
                            }
                            

                            $this->response([
                                'status'  => true,
                                'message' => 'Service added Successfully',
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

    # View Service Function  #
    public function services_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                           
                                $services = $this->user->getServicesNotIn($loginCheck['id']);
                            

                            if($services){
                                foreach($services as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $services[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $services[$key]['hourJson'] = json_decode($value['hourJson']);
                                    
                                    $services[$key]['buisnessCategories'] = $this->user->getUserServiceBuisnessCategories($value['id']);
                                    $services[$key]['serviceImages'] = $this->user->getServiceImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($services);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($services, $st2, 20 );

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

    # Delete Service Function #
    public function deleteService_get(){
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
                    if(isset($_GET) ){

                        extract($_GET);
                        if(isset($serviceId) && !empty($serviceId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                          
                                $services = $this->user->update_data('appUserServices',['deleteStatus'=>'1'],['id'=>$serviceId]);
                            

                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Successfully',
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

    # My Service Function  #
    public function myServices_get(){
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
                    if(isset($_GET) ){

                        extract($_GET);
                        if(isset($pageNumber) && !empty($pageNumber)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }

                            
                                $services = $this->user->getServicesIn($loginCheck['id']);
                            

                            if($services){
                                foreach($services as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $services[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $services[$key]['hourJson'] = json_decode($value['hourJson']);
                                    
                                    $services[$key]['buisnessCategories'] = $this->user->getUserServiceBuisnessCategories($value['id']);

                                    $services[$key]['serviceImages'] = $this->user->getServiceImages($value['id']);
                                    
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($services);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($services, $st2, 20 );

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

    # Filtered Service Function #
    public function filteredServices_post(){
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
                    if(isset($_POST) ){
                        $jsonArray = json_decode(file_get_contents('php://input'),true); 

                        extract($jsonArray);
                        if(isset($pageNumber) && !empty($pageNumber) && isset($search) && isset($categoriesIds) && isset($serviceAreaIds)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ],401);
                                die();
                            }

                            
                                $services = $this->user->getServicesByNotInAndFilter($loginCheck['id'],$search,$categoriesIds,$serviceAreaIds);
                            

                            if($services){
                                foreach($services as $key=>$value){
                                    $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                    $services[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $services[$key]['hourJson'] = json_decode($value['hourJson']);
                                    
                                    $services[$key]['buisnessCategories'] = $this->user->getUserServiceBuisnessCategories($value['id']);
                                    $services[$key]['serviceImages'] = $this->user->getServiceImages($value['id']);
                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($services);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($services, $st2, 20 );

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

    # Add Remove Service Fav Function #
    public function addRemoveServiceFav_get(){
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
                        if(isset($serviceId) && !empty($serviceId)){
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $serviceDetails = $this->user->getUserFavServiceByUserIdAndServiceId($loginCheck['id'],$serviceId);
                            if($serviceDetails){
                                $this->user->deleteFromAnyTable('appUserFavServices',['id'=>$serviceDetails->id]);
                                $message = "Service Unfavourite Successfully";

                            }else{
                                $insData['userId'] = $loginCheck['id'];
                                $insData['serviceId'] = $serviceId;

                                $favId = $this->user->insertToAnyTable('appUserFavServices',$insData);
                                $message = "Service Favourite Successfully";
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

    # View User Fav Services Function #
    public function viewUserFavServices_get(){
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
                            $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                            $loginCheck = json_decode(json_encode($loginCheck),true);
                            if(!$loginCheck){
                                $this->response([
                                                    'status'  => false,
                                                    'message' => 'Session expired, Please relogin',
                                                    'data'    => (object)[]
                                                ], 401);
                                die();
                            }
                            $services = $this->user->getUserFavServices($loginCheck['id']);
                            
                            if($services){
                                foreach($services as $key=>$value){
                                     $postedUserDetails = $this->user->select_single_row('appUsers','id',$value['userId']);

                                     $services[$key]['userPhoneNumber'] = $postedUserDetails->countryCode.$postedUserDetails->phoneNumber;
                                    $services[$key]['hourJson'] = json_decode($value['hourJson']);

                                     $services[$key]['buisnessCategories'] = $this->user->getUserServiceBuisnessCategories($value['id']);

                                }
                            }
                            # Total Classes #
                            $totalPartnerCount=count($services);
                            $totalPages = ceil($totalPartnerCount/20); 
                        
                            $st2=($pageNumber*20)-20;
                            $list = array_slice($services, $st2, 20 );

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

    # Add User Buisness Category Function #
    public function addUserBuisnessCategory_post(){
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
                    if(isset($categoryName) && !empty($categoryName)){
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                # Remove other user same device token #
                                
                                # User usertoken and deviceToken update #
                                $insData['userId']        = $loginCheck['id'];
                                $insData['name']        = $categoryName;
                                $insData['iconName']        = $iconName;

                                

                                $roomItemId = $this->user->insertToAnyTable('appUserBuisnessCategories',$insData);
                                 $insData['id'] = $roomItemId;
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Category added Successfully',
                                        'data'    => $insData
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

    # View User Buisness Categories Function #
    public function viewUserBuisnessCategories_get(){
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                $roomItems = $this->user->getUserBuisnessCategoriesByUserId($loginCheck['id']);
                               
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Category added Successfully',
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

    # Get Privacy Policy Html Function #
    public function getPrivacyPolicyHtml_get(){
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                $privacyPolicy = $this->user->getPrivacyPolicyHtml();

                                if($privacyPolicy){
                                    $privacyPolicyHtml = $privacyPolicy->description;
                                }else{
                                    $privacyPolicyHtml = "";
                                }
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Privacy Policy fected successfully',
                                        'data'    => array('privacyPolicyHtml'=>$privacyPolicyHtml)
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

    # Get Terms And Conditions Html Function #
    public function getTermsAndConditionsHtml_get(){
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
                                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                    $loginCheck = json_decode(json_encode($loginCheck),true);
                                    if(!$loginCheck){
                                        $this->response([
                                                            'status'  => false,
                                                            'message' => 'Session expired, Please relogin',
                                                            'data'    => (object)[]
                                                        ], 401);
                                        die();
                                    }
                                    
                                    $termAndCondition = $this->user->getTermsAndConditionsHtml();

                                    if($termAndCondition){
                                        $termAndConditionHtml = $termAndCondition->description;
                                    }else{
                                        $termAndConditionHtml = "";
                                    }
                                        
                                        $this->response([
                                            'status'  => true,
                                            'message' => 'Terms and Conditions fected successfully',
                                            'data'    => array('termsAndConditions'=>$termAndConditionHtml)
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

    # Add Contact Us Request Function  #
    public function addContactUsRequest_post(){
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
                    if(isset($email) && !empty($email) && isset($name) && !empty($name) && isset($description) && !empty($description)){
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                # Remove other user same device token #
                                
                                # User usertoken and deviceToken update #
                                $insData['userId']        = $loginCheck['id'];
                                $insData['name']        = $name;
                                $insData['email']        = $email;
                                $insData['description']        = $description;
                                $insData['addedOn']        = date('Y-m-d H:i:s');
                                $insData['addedOnTimeStamp']        =  time();

                              

                                $contactUsId = $this->user->insertToAnyTable('appUserContactUsRequests',$insData);
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Success',
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

    # Add Rating Function #
    public function addRating_post(){
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
                        if(isset($rating) && !empty($rating) && isset($description) && !empty($description)){
                                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                    $loginCheck = json_decode(json_encode($loginCheck),true);
                                    if(!$loginCheck){
                                        $this->response([
                                                            'status'  => false,
                                                            'message' => 'Session expired, Please relogin',
                                                            'data'    => (object)[]
                                                        ], 401);
                                        die();
                                    }
                                    
                                    # Remove other user same device token #
                                    
                                    # User usertoken and deviceToken update #
                                    $insData['userId']        = $loginCheck['id'];
                                    $insData['rating']        = $rating;
                                    $insData['description']        = $description;
                                    $insData['addedOn']        = date('Y-m-d H:i:s');
                                    $insData['addedOnTimeStamp']        =  time();

                                    

                                    $contactUsId = $this->user->insertToAnyTable('appUserRatings',$insData);
                                        $this->response([
                                            'status'  => true,
                                            'message' => 'Success',
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

    # Report Bug Function  #
    public function reportBug_post(){
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
                        if(isset($topic) && !empty($topic) && isset($attachment) && !empty($attachment) && isset($description) && !empty($description)){
                                    $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                    $loginCheck = json_decode(json_encode($loginCheck),true);
                                    if(!$loginCheck){
                                        $this->response([
                                                            'status'  => false,
                                                            'message' => 'Session expired, Please relogin',
                                                            'data'    => (object)[]
                                                        ], 401);
                                        die();
                                    }
                                    
                                    # Remove other user same device token #
                                    
                                    # User usertoken and deviceToken update #
                                    $insData['userId']        = $loginCheck['id'];
                                    $insData['topic']        = $topic;
                                    $insData['description']        = $description;
                                    

                                        $imageName = $this->user->uploadBase64Image($attachment,'./uploads/bugAttachments/');

                                        $insData['attachment']        = 'uploads/bugAttachments/'.$imageName;

                                    $insData['addedOn']        = date('Y-m-d H:i:s');
                                    $insData['addedOnTimeStamp']        =  time();
                                  
                                    $reportId = $this->user->insertToAnyTable('appUserReportedBugs',$insData);
                                        $this->response([
                                            'status'  => true,
                                            'message' => 'Success',
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

    # Logout Function #
    public function logout_get(){
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
                                $loginCheck = $this->user->getUserBySessionTok($headers[$this->userTokenHeaderName]);
                                $loginCheck = json_decode(json_encode($loginCheck),true);
                                if(!$loginCheck){
                                    $this->response([
                                                        'status'  => false,
                                                        'message' => 'Session expired, Please relogin',
                                                        'data'    => (object)[]
                                                    ], 401);
                                    die();
                                }
                                
                                $updData['sessionToken'] = "";
                                $updateUser = $this->user->update_data('appUsers',$updData,['id'=>$loginCheck['id']]);
                                    
                                    $this->response([
                                        'status'  => true,
                                        'message' => 'Logout successfully',
                                        'data'    => (object)[]
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
}


<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailVerification extends CI_Controller {

    public function __construct() {
        parent:: __construct();
        $this->load->helper(array('form', 'url'));
        $this->load->model("User_model",'user');
        date_default_timezone_set('UTC');
    }

	public function index(){
        if(isset($_GET['hash']) && !empty($_GET['hash'])){
            $user = $this->user->getSingleRowByOneColunmCompare('appUsers','emailHash',$_GET['hash']);
            if(!empty($user)){
                if($user->emailHashExpireTimeStamp > time()){
                    $emailHash =  $this->user->getToken(25);
                    $verified = $this->user->update_data('appUsers',['emailVerificationStatus'=>'1','emailHash'=>$emailHash],['id'=>$user->id]);
                    $data['text'] = 'Email Verified!';
                }else{
                    $data['text'] = 'Verification of email is expired!';
                }
            }else{
                $data['text'] = 'Unauthorized!';
            }
        }else{
            $data['text'] = 'Not found!';
        }
        $this->load->view('emailVerification/index',$data);
    }
}

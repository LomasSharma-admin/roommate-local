<?php 
class User_model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }


    # @ Insert Data to any table #
    public function insertToAnyTable($table, $data) {
        $this->db->insert($table, $data);
        return (string)$this->db->insert_id();
    }


    # @ Update Any Table Data #
    public function update_data($table, $data, $where) {
        $query = $this->db->where($where)
                ->update($table, $data);
        if ($query) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function getPrivacyPolicyHtml(){
        $qry = "select * from appUserPrivacyPolicy LIMIT 1";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getTermsAndConditionsHtml(){
        $qry = "select * from appTermsAndConditions LIMIT 1";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getRentSharingByIn($ids){
        if($ids == ''){
            $ids = 0;
        }
        $qry = "select * from appUsersRentSharing a where a.id IN (".$ids.")";
        $query = $this->db->query($qry);

        return $query->result_array();
    }

    public function getRentSharingById($id){
       
        $qry = "select * from appUsersRentSharing a where a.id ='".$id."' ";
        $query = $this->db->query($qry);

        return $query->row();
    }

    public function getRentalImages($rentId){
        $qry = "select * from appUserRentImages where rentId='".$rentId."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavRentById($userId,$rentId){
        $qry = "select * from appUserFavRents a where userId=".$userId." and rentId='".$rentId."' ";
        $query = $this->db->query($qry);

        return $query->row();
    }

    public function getExcludingsAndIncludings($ids){
        $arr = explode(',', $ids);
        $commaSepId = "'0'";
        foreach($arr as $key=>$value){
            if(isset($commaSepId)){
                $commaSepId = $commaSepId.",'".$value."'";
            }else{
                $commaSepId = "'".$value."'";
            }
        }

        // if(empty($commaSepId)){
        //      $commaSepId = 0;
        // }

        $qry = "select * from appUsersRentIncludingsAndExcludings where id IN (".$commaSepId.")";
        $query = $this->db->query($qry);

        return $query->result_array();
    }

    public function getAlsoSharing($userId){
        $qry = "select * from appUsersRentSharing where userId='".$userId."' order by id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getServiceImages($id){
        $qry = "select id,path from appUserServiceImages where serviceId='".$id."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getRentalNotIn($id,$location='',$propertyType='',$dealType=''){
        $addOnQuery = '';

        if($dealType != ''){
            $addOnQuery = " and FIND_IN_SET(a.dealType,'".$dealType."')  ";
        }

        if($propertyType != ''){
            $addOnQuery = $addOnQuery." and FIND_IN_SET(a.propertyType,'".$propertyType."')  ";
        }

        if($location != ''){
            $addOnQuery = $addOnQuery." and a.propertyAddress LIKE '%".$location."%' ";

        }

        $qry = "select a.*,ifnull((select 1 from appUserFavRents where userId='".$id."' and rentId=a.id LIMIT 1),0) as isFav from appUsersRents a where a.userId NOT IN (".$id.") ".$addOnQuery." and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }
    
    public function getRentalIn($id,$location='',$propertyType='',$dealType=''){
        $addOnQuery = '';

        if($dealType != ''){
            $addOnQuery = " and a.dealType='".$dealType."' ";
        }

        if($propertyType != ''){
            $addOnQuery = $addOnQuery." and a.unitSharing ='".$propertyType."' ";
        }

        if($location != ''){
            $addOnQuery = $addOnQuery." and a.propertyAddress ='".$location."' ";

        }

        $qry = "select a.*,ifnull((select 1 from appUserFavRents where userId='".$id."' and rentId=a.id LIMIT 1),0) as isFav from appUsersRents a where a.userId  IN (".$id.") ".$addOnQuery." and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getServicesNotIn($userId){
        $qry = "select a.*,ifnull((select 1 from appUserFavServices where userId='".$userId."' and serviceId=a.id LIMIT 1),0) as isFav from appUserServices a where a.userId NOT IN (".$userId.") and a.deleteStatus=0  order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getServicesByNotInAndFilter($userId,$search,$categoriesIds,$serviceAreaIds){
        $addedOnQry = '';

        if($search != ''){
            $addedOnQry = " and a.businessName LIKE '%".$search."%' ";
        }

        if($categoriesIds !='' ){
            $innerAddedOn = '';
            $categoryArr = explode(',',$categoriesIds);
            foreach($categoryArr as $categoryId){
                if($innerAddedOn == ''){
                    $innerAddedOn = " and ( (FIND_IN_SET('".$categoryId."',a.buisnessCategoryIds) ) ";
                }else{
                    $innerAddedOn = $innerAddedOn." OR (FIND_IN_SET('".$categoryId."',a.buisnessCategoryIds) ) ";
                }
            }
            $addedOnQry = $addedOnQry." ".$innerAddedOn." )";
        }

        if($serviceAreaIds != ''){
            $innerAddedOn1 = '';
            $serviceAreaArr = explode(',',$serviceAreaIds);
            foreach($serviceAreaArr as $serviceAreaId){
                if($innerAddedOn1 == ''){
                    $innerAddedOn1 = " and ( (FIND_IN_SET('".$serviceAreaId."',a.serviceAreaIds) ) ";
                }else{
                    $innerAddedOn1 = $innerAddedOn1." OR (FIND_IN_SET('".$serviceAreaId."',a.serviceAreaIds) ) ";
                }
            }
            $addedOnQry = $addedOnQry." ".$innerAddedOn1." )";
        }
        
        $qry = "select a.*,ifnull((select 1 from appUserFavServices where userId='".$userId."' and serviceId=a.id LIMIT 1),0) as isFav from appUserServices a where a.userId NOT IN (".$userId.") ".$addedOnQry." and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getServicesIn($userId){
        $qry = "select a.*,ifnull((select 1 from appUserFavServices where userId='".$userId."' and serviceId=a.id LIMIT 1),0) as isFav from appUserServices a where a.userId  IN (".$userId.") and a.deleteStatus=0  order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserBuisnessCategoriesByUserId($userId){
        $qry="select * from appUserBuisnessCategories a where a.userId='".$userId."' order by a.id desc ";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavServices($userId){
        $qry = "select b.*,1 as isFav,a.id as favServiceId from appUserFavServices a , appUserServices b where a.userId='".$userId."' and b.id=a.serviceId and b.deleteStatus=0 order by a.id desc ";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavServiceByUserIdAndServiceId($userId,$serviceId){
        $qry = "select  * from appUserFavServices where userId='".$userId."' and serviceId='".$serviceId."' ";

        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserServiceBuisnessCategories($serviceId){
        $qry = "select a.id as serviceBuisnessId,b.* from appUserServiceBuisnessCategories a,appUserBuisnessCategories b where a.serviceId=".$serviceId." and b.id=a.buisnessCategoryId ";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getJobCategoriesByJobId($id){
        $qry = "select c.*,a.id as jobCategoryId from appUserJobCategories a, appUserJobs b, appUserDefineCategories c where a.jobId=b.id and c.id=a.categoryId and b.id='".$id."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserJobCategoriesByUserId($id){
        $qry = "select * from appUserDefineCategories where userId='".$id."' order by id asc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getJobByNotIn($id){
        $qry = "select a.*,ifnull((select 1 from appUserFavJobs where userId='".$id."' and jobId=a.id LIMIT 1),0) as isFav from appUserJobs a where a.userId NOT IN (".$id.") and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getJobByNotInWithFilter($id,$search,$categoriesIds){
        $addedOnQry = '';

        if($search != ''){
            $addedOnQry = " and location LIKE '%".$search."%' ";
        }

        if($categoriesIds !='' ){
            $innerAddedOn = '';
            $categoryArr = explode(',',$categoriesIds);
            foreach($categoryArr as $categoryId){
                if($innerAddedOn == ''){
                    $innerAddedOn = " and ( (FIND_IN_SET('".$categoryId."',categoriesIds) ) ";
                }else{
                    $innerAddedOn = $innerAddedOn." OR (FIND_IN_SET('".$categoryId."',categoriesIds) ) ";
                }
            }
            $addedOnQry = $addedOnQry." ".$innerAddedOn." )";
        }
        $qry = "select a.*,ifnull((select 1 from appUserFavJobs where userId='".$id."' and jobId=a.id LIMIT 1),0) as isFav from appUserJobs a where a.userId NOT IN (".$id.") ".$addedOnQry." and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getJobByUserIdIn($id){
        $qry = "select a.*,ifnull((select 1 from appUserFavJobs where userId='".$id."' and jobId=a.id LIMIT 1),0) as isFav from appUserJobs a where a.userId  IN (".$id.") and a.deleteStatus=0 order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavJobs($id){
        $qry = "select a.*,ifnull((select 1 from appUserFavJobs where userId='".$id."' and jobId=a.id LIMIT 1),0) as isFav from appUserJobs a where a.id IN (select jobId from appUserFavJobs where jobId=a.id and userId='".$id."') and a.deleteStatus=0  order by a.id desc";

        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserByEmailOrMobileNotInSelfUserId($email,$mobile,$userId,$printStatus=FALSE){
        $qry = "select * from appUsers a where (a.email IN (".$email.") OR a.phoneNumber IN (".$mobile.")) and a.id NOT IN (".$userId.") ";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getPartnersNotIn($userId,$location='',$dealId=''){
        $addOnQuery = '';
        if($dealId != ''){
            $addOnQuery = " and a.dealTypeIds='".$dealId."' ";
        }

        if($location != ''){
            $addOnQuery = $addOnQuery." and a.id IN (select partnerId from appUserPartnerPreferLocations where location LIKE '%".$location."%' group by partnerId) ";
        }
        $qry = "select a.*,b.*,ifnull((select 1 from appUserFavPartners where partnerId=a.id and userId='".$userId."'),0) as isFav from appUserPartners a, appUserPartnerDetails b where b.partnerId=a.id and a.userId NOT IN (".$userId.") ".$addOnQuery." and a.deleteStatus=0 order by a.id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getBuySellNotIn($userId){
        $qry = "select a.*,ifnull((select 1 from appUserFavBuyAndSells where buySellId=a.id and userId='".$userId."'),0) as isFav from appUserBuyAndSells a where a.userId NOT IN (".$userId.") and a.deleteStatus=0 order by a.id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getBuySellNotInAndFilter($userId,$search,$categoriesIds){
        $addedOnQry = '';

        if($search != ''){
            $addedOnQry = " and a.propertyTitle LIKE '%".$search."%' ";
        }

        if($categoriesIds !='' ){
            $innerAddedOn = '';
            $categoryArr = explode(',',$categoriesIds);
            foreach($categoryArr as $categoryId){
                if($innerAddedOn == ''){
                    $innerAddedOn = " and ( (FIND_IN_SET('".$categoryId."',a.productCategoryIds) ) ";
                }else{
                    $innerAddedOn = $innerAddedOn." OR (FIND_IN_SET('".$categoryId."',a.productCategoryIds) ) ";
                }
            }
            $addedOnQry = $addedOnQry." ".$innerAddedOn." )";
        }
        $qry = "select a.*,ifnull((select 1 from appUserFavBuyAndSells where buySellId=a.id and userId='".$userId."'),0) as isFav from appUserBuyAndSells a where a.userId NOT IN (".$userId.") ".$addedOnQry." and a.deleteStatus=0  order by a.id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getBuySellInUserId($userId){
        $qry = "select a.*,ifnull((select 1 from appUserFavBuyAndSells where buySellId=a.id and userId='".$userId."'),0) as isFav from appUserBuyAndSells a where a.userId IN (".$userId.") and a.deleteStatus=0 order by a.id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getBuySellImages($id){
        $qry = "select * from appUserBuyAndSellImages a where a.buySellId='".$id."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getPartnersIn($userId,$location='',$dealId=''){
        $addOnQuery = '';
        if($dealId != ''){
            $addOnQuery = " and a.dealTypeIds='".$dealId."' ";
        }

        if($location != ''){
            $addOnQuery = $addOnQuery." and a.id IN (select partnerId from appUserPartnerPreferLocations where location = '".$location."' group by partnerId) ";
        }
        $qry = "select a.*,b.*,ifnull((select 1 from appUserFavPartners where partnerId=a.id and userId='".$userId."'),0) as isFav from appUserPartners a, appUserPartnerDetails b where b.partnerId=a.id and a.userId  IN (".$userId.") ".$addOnQuery." and a.deleteStatus=0 order by a.id desc";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getPartnerPreferLocationByPartnerId($partnerId){
        $qry = "select * from appUserPartnerPreferLocations a where a.partnerId='".$partnerId."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }


  

    
  


    public function deleteFromAnyTable($tableName,$compareArray){
        $query = $this->db->delete($tableName,$compareArray); 
        if($query){
            return true;
        }else{
            return false;
        }
    }

    # @ Generate Random Token #
    public function getToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); 

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }

        return $token;
    } 
    
   

    # @ Generate Random Numeric #
    public function generateNumericOTP($length) { 
        // Take a generator string which consist of 
        // all numeric digits 
        $generator = "1357902468"; 
        $result = ""; 
    
        for ($i = 1; $i <= $length; $i++) { 
            $result .= substr($generator, (rand()%(strlen($generator))), 1); 
        } 
        return $result; 
    } 

    # Select Single Row #
    public function select_single_row($table_name,$column_name1,$value1){
        $qry="select * from ".$table_name." where ".$column_name1 ."='".$value1."'";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserFavJobById($userId,$jobId){
        $qry = "select a.*  from appUserFavJobs a where a.userId='".$userId."' and a.jobId='".$jobId."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserBySessionTok($sessionToken){
        $qry = "select a.id,a.userToken,a.firstName,a.lastName,a.image,a.countryCode,a.phoneNumber,a.email  from appUsers a where a.sessionToken='".$sessionToken."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserByUserTok($sessionToken){
        $qry = "select a.id,a.userToken,a.firstName,a.lastName,a.image,a.countryCode,a.phoneNumber,a.email  from appUsers a where a.userToken='".$sessionToken."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getRentNearBy($id){
        $qry = "select * from appUsersRentNearBy where rentId='".$id."' ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

   

    public function getUserFavPartnerById($userId,$partnerId){
        $qry = "select * from appUserFavPartners a where a.userId='".$userId."' and a.partnerId='".$partnerId."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserFavPartners($userId){
        $qry = "select 1 as isFav,b.*,c.* from appUserFavPartners a, appUserPartners b, appUserPartnerDetails c where a.userId='".$userId."' and b.id=a.partnerId and c.partnerId=b.id and b.deleteStatus=0 order by a.id desc ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavBuySells($userId){
        $qry = "select 1 as isFav,b.* from appUserFavBuyAndSells a, appUserBuyAndSells b  where a.userId='".$userId."' and b.id=a.buySellId and b.deleteStatus=0  order by a.id desc ";
        $query = $this->db->query($qry);
        return $query->result_array();
    }
    
    # @ Get user by email or mobile #
    public function getUserByEmailOrMobile($email,$mobile,$printStatus=FALSE){
        $qry = "select * from appUsers a where (a.email='".$email."' OR a.phoneNumber='".$mobile."')";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->result_array();
    }



    # @ Upload base 64 image #
    public function uploadBase64Image($base64Image,$path){
        $image = base64_decode($base64Image);
        // decoding base64 string value
        $image_name = md5(uniqid(rand(), true));// image name generating with random number with 32 characters
        $filename = $image_name . '.' . 'png';
        //rename file name with random number
        file_put_contents($path . $filename, $image);
        return $filename;
    }

    public function getUserByUserToken($userToken,$printStatus=FALSE){
        $qry = "select * from appUsers a where a.userToken='".$userToken."'";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        $data = $query->row();
        if(!empty($data)){
            if($data->activeStatus==1){
                return $data;
            }else{
                return false;
            }
        }else{
            return $data;
        }
    }

    public function getUserByUserId($userId,$printStatus=FALSE){
        $qry = "select * from appUsers a where a.id='".$userId."'";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserByEmail($email,$printStatus=FALSE){
        $qry = "select * from appUsers a where a.email='".$email."' and a.socialType='1' ";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getUserByEmailAndSocialType($email,$socialId){
        $qry = "select * from appUsers  where email='".$email."' and socialId!='".$socialId."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getSocialUserBySocialId($socialId,$socialType){
        $qry = "select * from appUsers where socialId='".$socialId."' and socialType='".$socialType."' ";
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function saveBase64ImagePng($base64Image, $imageDir)
    {
        //set name of the image file

        $fileName =  $this->user->getToken(11).'.png';

        $base64Image = trim($base64Image);
        $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/gif;base64,', '', $base64Image);
        $base64Image = str_replace(' ', '+', $base64Image);

        $imageData = base64_decode($base64Image);
        //Set image whole path here 
        $filePath = $imageDir . $fileName;


       file_put_contents($filePath, $imageData);

       return $fileName;


    }

    public function getUserRoomItemsByUserId($userId){
         $qry = "select * from appUserRoomItems a where a.userId='".$userId."' order by a.id asc";
        
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getUserFavRental($userId){
        $qry = "select a.id as favRentId,1 as isFav,b.* from appUserFavRents a,appUsersRents b where a.userId='".$userId."' and a.rentId=b.id and b.deleteStatus=0 order by a.id desc ";   
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getExcludingsAndIncludingsByTypeAndId($id,$type){
        $qry = "select * from appUsersRentIncludingsAndExcludings a where a.type='".$type."' and a.id='".$id."' ";
        
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getExcludingsAndIncludingsByType($type){
         $qry = "select * from appUsersRentIncludingsAndExcludings a where a.type='".$type."'  order by a.id desc";
        
        $query = $this->db->query($qry);
        return $query->result_array();
    }

    public function getPartnerRoomItemByPartnerId($id){
        $qry = "select a.id,b.name,b.iconName,b.image from appUserPartnerItems a, appUserRoomItems b where a.partnerId='".$id."' and b.id=a.roomItemId";
        $query = $this->db->query($qry);
        return $query->result_array();
    }
    public function getUserByCountryCodeAndPhoneNumber($countryCode,$phoneNumber,$printStatus=FALSE){
        $qry = "select * from appUsers a where a.countryCode='".$countryCode."' and  a.phoneNumber='".$phoneNumber."' ";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->row();
    }

    public function getSingleRowByOneColunmCompare($table,$colName,$colVal,$printStatus=FALSE){
        $qry = "select * from ".$table." where ".$colName."='".$colVal."' ";
        if($printStatus == true){
            echo $qry;
        }
        $query = $this->db->query($qry);
        return $query->row();
    }
















    public function getUserFavBuySellByUserIdAndBuySellId($userId,$buySellId){
        $qry = "select * from appUserFavBuyAndSells a where a.userId='".$userId."' and a.buySellId='".$buySellId."' ";

        $query = $this->db->query($qry);
        return $query->row();
    }

    public function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    

   

    

   


    public function sendGCMPushNotification($data, $target) {

        //API URL of FCM
        $url = 'https://fcm.googleapis.com/fcm/send';
        $tokens = json_encode(array($target));
        $payloadData = json_encode($data['data']);
        
        $fields = '{
            "registration_ids" : ' . $tokens . ',
            "notification" : {
            "body" : "'.$data['message'].'",
            "title" : "'.$data['title'].'",
            "data" : '.$payloadData.',
            "content_available" : true,
            "priority" : "high"
            }
        }';

        
        //header includes Content type and api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key=AAAAfTcI1oY:APA91bFMXCfB-3as2PjK7FYtO1sB5YU161QtUzn6SRIy-c-PqGAfC6P4o-ZDfo9GiexGvKGhQFhmZnIk9TmP3oUa180qcu8ye0RpKQIRZ6g4zagguODx4OrjK1B6jXtfeSPeat-CSPL7'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

   
    
    


    public function sendSimpleMail($email,$subject,$message){
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        // More headers
        $headers .= 'From: <no-reply@example>' . "\r\n";

        if(mail($email,$subject,$message,$headers)){
            return true;
        }else{
            return false;
        }
    }
}


?>
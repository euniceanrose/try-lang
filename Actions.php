<?php 
session_start();
require_once('DBConnection.php');

Class Actions extends DBConnection{
    function __construct(){
        parent::__construct();
    }
    function __destruct(){
        parent::__destruct();
    }
    function login(){
        extract($_POST);
        $sql = "SELECT * FROM admin_list where username = '{$username}' and `password` = '".md5($password)."' ";
        @$qry = $this->query($sql)->fetchArray();
        if(!$qry){
            $resp['status'] = "failed";
            $resp['msg'] = "Invalid username or password.";
        }else{
            $resp['status'] = "success";
            $resp['msg'] = "Login successfully.";
            foreach($qry as $k => $v){
                if(!is_numeric($k))
                $_SESSION[$k] = $v;
            }
        }
        return json_encode($resp);
    }
    function logout(){
        session_destroy();
        header("location:./admin");
    }
    function save_admin(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
        if(!in_array($k,array('id'))){
            if(!empty($id)){
                if(!empty($data)) $data .= ",";
                $data .= " `{$k}` = '{$v}' ";
                }else{
                    $cols[] = $k;
                    $values[] = "'{$v}'";
                }
            }
        }
        if(empty($id)){
            $cols[] = 'password';
            $values[] = "'".md5($username)."'";
        }
        if(isset($cols) && isset($values)){
            $data = "(".implode(',',$cols).") VALUES (".implode(',',$values).")";
        }
        

       
        @$check= $this->query("SELECT count(admin_id) as `count` FROM admin_list where `username` = '{$username}' ".($id > 0 ? " and admin_id != '{$id}' " : ""))->fetchArray()['count'];
        if(@$check> 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Username already exists.";
        }else{
            if(empty($id)){
                $sql = "INSERT INTO `admin_list` {$data}";
            }else{
                $sql = "UPDATE `admin_list` set {$data} where admin_id = '{$id}'";
            }
            @$save = $this->query($sql);
            if($save){
                $resp['status'] = 'success';
                if(empty($id))
                $resp['msg'] = 'New User successfully saved.';
                else
                $resp['msg'] = 'User Details successfully updated.';
            }else{
                $resp['status'] = 'failed';
                $resp['msg'] = 'Saving User Details Failed. Error: '.$this->lastErrorMsg();
                $resp['sql'] =$sql;
            }
        }
        return json_encode($resp);
    }
    function delete_admin(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `admin_list` where rowid = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'User successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function update_credentials(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id','old_password')) && !empty($v)){
                if(!empty($data)) $data .= ",";
                if($k == 'password') $v = md5($v);
                $data .= " `{$k}` = '{$v}' ";
            }
        }
        if(!empty($password) && md5($old_password) != $_SESSION['password']){
            $resp['status'] = 'failed';
            $resp['msg'] = "Old password is incorrect.";
        }else{
            $sql = "UPDATE `admin_list` set {$data} where admin_id = '{$_SESSION['admin_id']}'";
            @$save = $this->query($sql);
            if($save){
                $resp['status'] = 'success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Credential successfully updated.';
                foreach($_POST as $k => $v){
                    if(!in_array($k,array('id','old_password')) && !empty($v)){
                        if(!empty($data)) $data .= ",";
                        if($k == 'password') $v = md5($v);
                        $_SESSION[$k] = $v;
                    }
                }
            }else{
                $resp['status'] = 'failed';
                $resp['msg'] = 'Updating Credentials Failed. Error: '.$this->lastErrorMsg();
                $resp['sql'] =$sql;
            }
        }
        return json_encode($resp);
    }
    function save_settings(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k=> $v){
            if(!is_numeric($v))
            $v = $this->escapeString($v);
            if(!empty($data)) $data .=", ";
            $data .= "('{$k}','{$v}')";
        }
        $sql = "INSERT INTO `system_info` (`meta_field`,`meta_value`) VALUES {$data}";
        if(!empty($data))
        $this->query("DELETE FROM `system_info`");
        $save = $this->query($sql);
        if($save){
            $resp['status'] = "success";
            $resp['msg'] = "Settings successfully updated.";
            foreach($_POST as $k=> $v){
                $_SESSION['system_info'][$k] = $v;
            }
            if(isset($_FILES['logo']['tmp_name']) && !empty($_FILES['logo']['tmp_name'])){
                $fname = __DIR__."/uploads/logo.png";
                $upload = $_FILES['logo']['tmp_name'];
                $type = mime_content_type($upload);
                $allowed = array('image/png','image/jpeg');
                if(!in_array($type,$allowed)){
                    $resp['msg'].=" But Image failed to upload due to invalid file type.";
                }else{
                    $gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
                    if($gdImg){
                         if(is_file($fname))
                         unlink($fname);
                         $uploaded = imagepng($gdImg,$fname);
                         imagedestroy($gdImg);
                    }else{
                    $resp['msg'].=" But Image failed to upload due to unkown reason.";
                    }
                }
            }
            $_SESSION['flashdata']['type'] = "success";
            $_SESSION['flashdata']['msg'] = $resp['msg'];
        }else{
            $resp['status'] = "failed";
            $resp['msg'] = "Failed to update settings.";
        }
        return json_encode($resp);
    }
    function save_position(){
        extract($_POST);
        $data = "";
        if(isset($is_approver)){
            $_POST['is_approver'] = 1;
        }else{
            $_POST['is_approver'] = 0;
        }
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `position_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `position_list` set {$data} where position_id = '{$id}'";
        }

        $check = $this->query("SELECT count(position_id) as `count` FROM `position_list` where `position` = '{$position}' ".($id > 0 ? " and position_id != '{$id}'" : ""))->fetchArray()['count'];
        if($check >0){
            $resp['status']="failed";
            $resp['msg'] = "Position name is already exists.";
        }else{
            @$save = $this->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id)){
                    $resp['msg'] = "Position successfully saved.";
                    $pid = $this->query("SELECT last_insert_rowid()")->fetchArray()[0];
                }else{
                    $resp['msg'] = "Position successfully updated.";
                    $pid = $id;
                }
                if($is_approver == 1){
                    $this->query("UPDATE `position_list` set is_approver = 0 where position_id != '{$id}' ");
                }
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Position Failed.";
                else
                    $resp['msg'] = "Updating Position Failed.";
                    $resp['error']=$this->lastErrorMsg();
                    $resp['sql']=$sql;
            }
        }

        return json_encode($resp);
    }
    function delete_position(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `position_list` where position_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Position successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_purok(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `purok_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `purok_list` set {$data} where purok_id = '{$id}'";
        }

        $check = $this->query("SELECT count(purok_id) as `count` FROM `purok_list` where `purok` = '{$purok}' ".($id > 0 ? " and purok_id != '{$id}'" : ""))->fetchArray()['count'];
        if($check >0){
            $resp['status']="failed";
            $resp['msg'] = "Purok name is already exists.";
        }else{
            @$save = $this->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Purok successfully saved.";
                else
                    $resp['msg'] = "Purok successfully updated.";
                $_SESSION['flashdata']['type'] = "success";
                $_SESSION['flashdata']['msg'] = $resp['msg'];
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Purok Failed.";
                else
                    $resp['msg'] = "Updating Purok Failed.";
                    $resp['error']=$this->lastErrorMsg();
                    $resp['sql']=$sql;
            }
        }

        return json_encode($resp);
    }
    function delete_purok(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `purok_list` where purok_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Purok successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_household(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `household_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `household_list` set {$data} where household_id = '{$id}'";
        }

        $check = $this->query("SELECT count(household_id) as `count` FROM `household_list` where `house_no` = '{$house_no}' ".($id > 0 ? " and household_id != '{$id}'" : ""))->fetchArray()['count'];
        if($check >0){
            $resp['status']="failed";
            $resp['msg'] = "Household number is already exists.";
        }else{
            @$save = $this->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Household successfully saved.";
                else
                    $resp['msg'] = "Household successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Household Failed.";
                else
                    $resp['msg'] = "Updating Household Failed.";
                    $resp['error']=$this->lastErrorMsg();
                    $resp['sql']=$sql;
            }
        }

        return json_encode($resp);
    }
    function delete_household(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `household_list` where household_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Household successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_official(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `official_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `official_list` set {$data} where official_id = '{$id}'";
        }

        @$save = $this->query($sql);
        if($save){
            $resp['status']="success";
            if(empty($id))
                $resp['msg'] = "Appointed Official successfully added.";
            else
                $resp['msg'] = "Appointed Official Details successfully updated.";
                
                $_SESSION['flashdata']['type'] = "success";
                $_SESSION['flashdata']['msg'] = $resp['msg'];
        }else{
            $resp['status']="failed";
            if(empty($id))
                $resp['msg'] = "Saving New official Failed.";
            else
                $resp['msg'] = "Updating official Failed.";
                $resp['error']=$this->lastErrorMsg();
                $resp['sql']=$sql;
        }

        return json_encode($resp);
    }
    function delete_official(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `official_list` where official_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Appointed Official Details successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_clearance(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `clearance_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `clearance_list` set {$data} where clearance_id = '{$id}'";
        }

        @$save = $this->query($sql);
        if($save){
            $resp['status']="success";
            if(empty($id))
                $resp['msg'] = "Clearance successfully added.";
            else
                $resp['msg'] = "Clearance Details successfully updated.";
                
                $_SESSION['flashdata']['type'] = "success";
                $_SESSION['flashdata']['msg'] = $resp['msg'];
        }else{
            $resp['status']="failed";
            if(empty($id))
                $resp['msg'] = "Saving New clearance Failed.";
            else
                $resp['msg'] = "Updating clearance Failed.";
                $resp['error']=$this->lastErrorMsg();
                $resp['sql']=$sql;
        }

        return json_encode($resp);
    }
    function delete_clearance(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `clearance_list` where clearance_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Clearance Details successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_business_clearance(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `business_clearance_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `business_clearance_list` set {$data} where business_clearance_id = '{$id}'";
        }

        @$save = $this->query($sql);
        if($save){
            $resp['status']="success";
            if(empty($id))
                $resp['msg'] = "Business Clearance successfully added.";
            else
                $resp['msg'] = "Business Clearance Details successfully updated.";
                
                $_SESSION['flashdata']['type'] = "success";
                $_SESSION['flashdata']['msg'] = $resp['msg'];
        }else{
            $resp['status']="failed";
            if(empty($id))
                $resp['msg'] = "Saving New Business clearance Failed.";
            else
                $resp['msg'] = "Updating Business clearance Failed.";
                $resp['error']=$this->lastErrorMsg();
                $resp['sql']=$sql;
        }

        return json_encode($resp);
    }
    function delete_business_clearance(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `business_clearance_list` where business_clearance_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Business Clearance Details successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
    function save_complaint(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id'))){
                $v = trim($v);
                $v = $this->escapeString($v);
                $$k = $v;
            if(empty($id)){
                $cols[] = "`{$k}`";
                $vals[] = "'{$v}'";
            }else{
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '{$v}' ";
            }
            }
        }
        if(isset($cols) && isset($vals)){
            $cols_join = implode(",",$cols);
            $vals_join = implode(",",$vals);
        }
        
        if(empty($id)){
            $sql = "INSERT INTO `complaint_list` ({$cols_join}) VALUES ($vals_join)";
        }else{
            $sql = "UPDATE `complaint_list` set {$data} where complaint_id = '{$id}'";
        }

        @$save = $this->query($sql);
        if($save){
            $resp['status']="success";
            if(empty($id))
                $resp['msg'] = "Complaint successfully added.";
            else
                $resp['msg'] = "Complaint Details successfully updated.";
                
                $_SESSION['flashdata']['type'] = "success";
                $_SESSION['flashdata']['msg'] = $resp['msg'];
        }else{
            $resp['status']="failed";
            if(empty($id))
                $resp['msg'] = "Saving New Complaint Failed.";
            else
                $resp['msg'] = "Updating Complaint Failed.";
                $resp['error']=$this->lastErrorMsg();
                $resp['sql']=$sql;
        }

        return json_encode($resp);
    }
    function delete_complaint(){
        extract($_POST);

        @$delete = $this->query("DELETE FROM `complaint_list` where complaint_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Complaint Details successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->lastErrorMsg();
        }
        return json_encode($resp);
    }
}
$a = isset($_GET['a']) ?$_GET['a'] : '';
$action = new Actions();
switch($a){
    case 'login':
        echo $action->login();
    break;
    case 'logout':
        echo $action->logout();
    break;
    case 'save_admin':
        echo $action->save_admin();
    break;
    case 'delete_admin':
        echo $action->delete_admin();
    break;
    case 'update_credentials':
        echo $action->update_credentials();
    break;
    case 'save_settings':
        echo $action->save_settings();
    break;
    case 'save_position':
        echo $action->save_position();
    break;
    case 'delete_position':
        echo $action->delete_position();
    break;
    case 'save_purok':
        echo $action->save_purok();
    break;
    case 'delete_purok':
        echo $action->delete_purok();
    break;
    case 'save_household':
        echo $action->save_household();
    break;
    case 'delete_household':
        echo $action->delete_household();
    break;
    case 'save_official':
        echo $action->save_official();
    break;
    case 'delete_official':
        echo $action->delete_official();
    break;
    case 'save_clearance':
        echo $action->save_clearance();
    break;
    case 'delete_clearance':
        echo $action->delete_clearance();
    break;
    case 'save_business_clearance':
        echo $action->save_business_clearance();
    break;
    case 'delete_business_clearance':
        echo $action->delete_business_clearance();
    break;
    case 'save_complaint':
        echo $action->save_complaint();
    break;
    case 'delete_complaint':
        echo $action->delete_complaint();
    break;
    default:
    // default action here
    break;
}
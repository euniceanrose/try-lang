<?php
date_default_timezone_set('Asia/Manila');
if(!is_dir(__DIR__.'./db'))
    mkdir(__DIR__.'./db');
if(!defined('db_file')) define('db_file',__DIR__.'./db/baranagay_management_db.db');
function my_udf_md5($string) {
    return md5($string);
}

Class DBConnection extends SQLite3{
    protected $db;
    function __construct(){
        $this->open(db_file);
        $this->createFunction('md5', 'my_udf_md5');
        $this->exec("PRAGMA foreign_keys = ON;");

        $this->exec("CREATE TABLE IF NOT EXISTS `admin_list` (
            `admin_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `fullname` INTEGER NOT NULL,
            `username` TEXT NOT NULL,
            `password` TEXT NOT NULL,
            `type` INTEGER NOT NULL Default 1,
            `status` INTEGER NOT NULL Default 1,
            `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"); 

        //User Comment
        // Type = [ 1 = Administrator, 2 = Cashier]
        // Status = [ 1 = Active, 2 = Inactive]
        $this->exec("CREATE TABLE IF NOT EXISTS `system_info` (
            `meta_field` TEXT NOT NULL,
            `meta_value` TEXT NOT NULL
        ) ");
        
        $this->exec("CREATE TABLE IF NOT EXISTS `position_list` (
            `position_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `position` TEXT NOT NULL,
            `is_approver` INTEGER NOT NULL DEFAULT 0
        ) ");

        $this->exec("CREATE TABLE IF NOT EXISTS `purok_list` (
            `purok_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `purok` TEXT NOT NULL
        ) ");

        $this->exec("CREATE TABLE IF NOT EXISTS `household_list` (
            `household_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `house_no` INTEGER NOT NULL,
            `purok_id` INTEGER NOT NULL,
            `firstname` TEXT NOT NULL,
            `lastname` TEXT NOT NULL,
            `middlename` TEXT NOT NULL,
            `contact` TEXT NOT NULL,
            `email` TEXT NULL,
            `occupation` TEXT NOT NULL,
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(`purok_id`) REFERENCES `purok_list`(`purok_id`) ON DELETE CASCADE
        ) ");

        $this->exec("CREATE TABLE IF NOT EXISTS `official_list` (
            `official_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `position_id` INTEGER NOT NULL,
            `firstname` TEXT NOT NULL,
            `lastname` TEXT NOT NULL,
            `middlename` TEXT NOT NULL,
            `contact` TEXT NOT NULL,
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(`position_id`) REFERENCES `position_list`(`position_id`) ON DELETE CASCADE
        ) ");

        $this->exec("CREATE TABLE IF NOT EXISTS `clearance_list` (
            `clearance_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `firstname` TEXT NOT NULL,
            `lastname` TEXT NOT NULL,
            `middlename` TEXT NOT NULL,
            `age` INTEGER NOT NULL,
            `contact` TEXT NOT NULL,
            `purpose` TEXT NOT NULL,
            `or_no` TEXT NOT NULL,
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ");
        $this->exec("CREATE TABLE IF NOT EXISTS `business_clearance_list` (
            `business_clearance_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `owner_name` TEXT NOT NULL,
            `business_name` TEXT NOT NULL,
            `business_type` TEXT NOT NULL,
            `tin` TEXT NOT NULL,
            `or_no` TEXT NOT NULL,
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ");

        $this->exec("CREATE TABLE IF NOT EXISTS `complaint_list` (
            `complaint_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `complainant_name` TEXT NOT NULL,
            `appellant` TEXT NOT NULL,
            `description` TEXT NOT NULL,
            `status` TEXT NOT NULL,
            `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ");
       
        // $this->exec("CREATE TRIGGER IF NOT EXISTS updatedTime_prod AFTER UPDATE on `vacancy_list`
        // BEGIN
        //     UPDATE `vacancy_list` SET date_updated = CURRENT_TIMESTAMP where vacancy_id = vacancy_id;
        // END
        // ");
        $this->exec("INSERT or IGNORE INTO `admin_list` VALUES (1,'Administrator','admin',md5('admin123'),1,1, CURRENT_TIMESTAMP)");

        $information = $this->query("SELECT * FROM `system_info`");
            while($row = $information->fetchArray()){
                $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
        }
    }
    function isMobileDevice(){
        $aMobileUA = array(
            '/iphone/i' => 'iPhone', 
            '/ipod/i' => 'iPod', 
            '/ipad/i' => 'iPad', 
            '/android/i' => 'Android', 
            '/blackberry/i' => 'BlackBerry', 
            '/webos/i' => 'Mobile'
        );
    
        //Return true if Mobile User Agent is detected
        foreach($aMobileUA as $sMobileKey => $sMobileOS){
            if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
                return true;
            }
        }
        //Otherwise return false..  
        return false;
    }
    function __destruct(){
         $this->close();
    }
}

$conn = new DBConnection();
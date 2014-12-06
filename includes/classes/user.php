<?php

/**
 * coreUser Class
 * 
 * allows for loading and editing of user information and authentication
 * 
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreUser{
  public $id = 0;               /**< User ID (0 if not logged in) */
  public $login = '';           /**< User Login */
  public $firstname = '';       /**< First name */
  public $lastname = '';        /**< Last name */
  public $email = '';           /**< Email address */
  public $created = '';         /**< Date that the user was created */
  public $desc = '';            /**< Description of user */
  public $groups = array();     /**< Groups that the user is a member of */
  public $roles = array();      /**< Roles that a user is a member of */
  public $message = '';         /**< Message to view when editing or viewing a profile */
  public $error = 0;            /**< Error (zero means no error) */
  
  /**
   * Constructs user based on user ID in database, or makes an empty user
   *
   * @param int $id Optional user ID to lookup in the database, or create new
   */
  public function __construct($id = 0){
    if ($id>0){
      // Lookup user by ID
      $db = new coreDb(DB_NAME);
      $db->fetch('users', null, array('id' => $id));
      if ($db->affected_rows == 1){
        $this->id = $db->output[0]['id'];
        $this->login = $db->output[0]['login'];
        $this->firstname = $db->output[0]['firstname'];
        $this->lastname = $db->output[0]['lastname'];
        $this->created = $db->output[0]['created'];
        $this->email = $db->output[0]['email'];
        $this->desc = $db->output[0]['desc'];
        $db->fetch('user_roles', null, array('user_id' => $id), null, null, 'role_id');
        $this->roles = array();
        if ($db->affected_rows > 0){
          foreach ($db->output as $key => $value){
            $this->roles[$key] = $value['role_id'];
          }
        }
        $db->fetch('user_groups', null, array('user_id' => $id), null, null, 'group_id');
        $this->groups = array();
        if ($db->affected_rows > 0){
          foreach ($db->output as $key => $value){
            $this->groups[$key] = $value['group_id'];
          }
        }
      }
      else{
        $this->clear();
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
    }
  }
  
  /**
   * Removes User information from object, but does not destroy the object
   */
  public function clear(){
    $this->id = 0;
    $this->login = '';
    $this->firstname = '';
    $this->lastname = '';
    $this->email = '';
    $this->created = '';
    $this->desc = '';
    $this->groups = array();
    $this->roles = array();
  }
  
  /**
   * Sets user information using login credentials
   *
   * @param string $username Login name for the user
   * @param string $password Unhashed password from the user
   */
  public function login($username, $password){
    //cleanse input
    $user = trim(strtolower($username));
    $pass = trim($password);
    $db = new coreDb(DB_NAME);
    
    //lookup the user by ID
    $db->fetch('users', array('id'), array('login' => $user));
    if ($db->affected_rows > 0){
      $id = $db->output[0]['id'];
      $db->fetch('passwords', NULL, array('user_id' => $id), NULL, array('valid_date'));
      //Check for password
      if ($db->affected_rows>0){
        foreach ($db->output as $pwd){
          $hashed = $pwd['hashed'];
          $valid_date = $pwd['valid_date'];
          $passwords[$valid_date] = $hashed;
        }
        if (isset($hashed)){
          if (password_verify($pass, $hashed)){
            //Create the user!
            $this->__construct($id);
          }
        }
      }
    }
  }
  
  /**
   * Sets a password for a user (User must be loaded)
   * 
   * @param string $pass password
   */
  public function setpassword($user_id, $pass=null){
    if (is_null($pass) && $this->id > 0){
      // Create a random password
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
      $len = strlen($chars);
      $pass = '';
      for ($i = 0; $i<10; $i++){
        $num = rand(0,$len-1);
        $reset_code .= substr($chars, $num, 1);
      }
    }
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $current_date = date("Y-m-d H:i:s");
    $db = new DB(DB_NAME);
    $db->write('passwords', array('user_id' => $user_id, 'valid_date' => $current_date, 'hashed' => $hashed));
  }
  
  /**
   * Resets a user's lost password
   * 
   * @param string $email User's email address
   *
   */
  public function resetpassword($email){
    $db = new DB(DB_NAME);
    $db->fetch('users', NULL, array('email' => $email));
    if ($db->affected_rows > 0){
      $id = $db->output[0]['id'];
      $login = $db->output[0]['login'];
      $db->fetch('passwords', array('user_id', 'valid_date'), array('user_id' => $id), NULL, array('valid_date'));
      if ($db->affected_rows > 0){
        foreach ($db->output as $data){
          $user_id = $data['user_id'];
          $valid_date = $data['valid_date'];
        }
      }
      $loop = TRUE;
      while ($loop){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $len = strlen($chars);
        $reset_code = "";
        for ($i = 0; $i<80; $i++){
          $num = rand(0,$len-1);
          $reset_code .= substr($chars, $num, 1);
        }
        $db->fetch('passwords', array('reset_code'), array('reset_code' => $reset_code));
        if ($db->affected_rows == 0){
          $loop = FALSE;
        }
      }
      $sql = "UPDATE `passwords` SET `reset` = 1 , `reset_code`='{$reset_code}' WHERE `user_id` = {$user_id} and `valid_date` = '{$valid_date}'";
      $db->write_raw($sql);
      if ($db->error > 0){
        echo $db->error;
        echo "Fail!\n";
      }
      else{
        $headers = "From: LibreWebTools <noreply@transitguru.limited>\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8";
        mail($email, "Password Reset", "Username: {$login}\r\nPlease visit the following url to reset your password:\r\nhttp://LibreWebTools.org/forgot/{$reset_code}/", $headers);
      }
    }
  }

  /**
   * Renders a user profile editing page (intended for one's own user)
   */
  public function viewProfile(){
    if ($this->id > 0){
      // Use Field object to build form...
      
      
      // Render the form ...
      
    }
  }
  
  /**
   * Writes a user profile
   */
  public function write(){
    $db = new coreDb(DB_NAME);
    $inputs['login'] = $this->login;
    $inputs['firstname'] = $this->firstname;
    $inputs['lastname'] = $this->lastname;
    $inputs['email'] = $this->email;
    $inputs['desc'] = $this->desc;
    if ($this->id > 0){
      $db->write('users', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    elseif ($this->id < 0){
      $inputs['created'] = date('Y-m-d H:i:s');
      $this->created = $inputs['created'];
      $db->write('users', $inputs);      
      $this->error = $db->error;
      $this->message = $db->message;
      if (!$db->error){
        $this->id = $db->insert_id;
      }
    }
    else{
      $this->error = 1;
      $this->message = 'Cannot write user number 0';
    }
    if (!$this->error){
      // Empty out groups and roles database tables
      $db->write_raw("DELETE FROM `user_groups` WHERE `user_id` = {$this->id}");
      $db->write_raw("DELETE FROM `user_roles` WHERE `user_id` = {$this->id}");

      // Write the new roles and groups
      foreach ($this->groups as $group){
        $db->write('user_groups', array('group_id' => $group, 'user_id' => $this->id));
      }
      foreach ($this->roles as $role){
        $db->write('user_roles', array('role_id' => $role, 'user_id' => $this->id));
      }
    }
  }

  /**
   * Deletes the record, then clears the object
   */
  public function delete(){
    if ($this->id > 0){
      $db = new coreDb(DB_NAME);
      $db->write_raw("DELETE FROM `users` WHERE `id`={$this->id}");
      if(!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}


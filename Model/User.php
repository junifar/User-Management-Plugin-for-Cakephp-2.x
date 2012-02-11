<?php

/*
    This file is part of UserMgmt.

    Author: Chetan Varshney (http://ektasoftwares.com)
    
    UserMgmt is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    UserMgmt is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/
App::uses('UserMgmtAppModel', 'Usermgmt.Model');
App::uses('CakeEmail', 'Network/Email');

class User extends UserMgmtAppModel
{
	var $belongsTo = array('Usermgmt.UserGroup');
	var $hasMany = array('LoginToken'=>array('className'=>'Usermgmt.LoginToken','limit' =>1));
	var $validate = array();
	function LoginValidate()
	{
		$validate1 = array(
				'email'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter email or username')
					),
				'password'=>array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter password')
					)
			);
		$this->validate=$validate1;
		return $this->validates();
	}
    function RegisterValidate()
	{
		$validate1 = array(
				"user_group_id" => array(
					'rule' => array('comparison', '!=', 0),				
					'message'=> 'Please select group'),
				'username'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter username',
						'last'=>true),
					'mustUnique'=>array(
						'rule' =>'isUnique',
						'message' =>'This username already taken',
					'last'=>true),
					'mustBeLonger'=>array(
						'rule' => array('minLength', 4),
						'message'=> 'Username must be greater than 3 characters',
						'last'=>true),
					),
				'first_name'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter first name')
					),
				'last_name'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'on' => 'create',
						'message'=> 'Please enter last name')
					),
				'email'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter email',
						'last'=>true),
					'mustBeEmail'=> array(
						'rule' => array('email'),
						'message' => 'Please enter valid email',
						'last'=>true),
					'mustUnique'=>array(
						'rule' =>'isUnique',
						'message' =>'This email is already registered',
						)
					),
				'password'=>array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter password',
						'on' => 'create',
						'last'=>true),
					'mustBeLonger'=>array(
						'rule' => array('minLength', 6),
						'message'=> 'Password must be greater than 5 characters',
						'on' => 'create',
						'last'=>true),
					'mustMatch'=>array(
						'rule' => array('verifies'),
						'message' => 'Both passwords must match'),
						//'on' => 'create'
					)
			);
		$this->validate=$validate1;
		return $this->validates();
	}
	protected function verifies()
	{
        return ($this->data['User']['password']===$this->data['User']['cpassword']);
    }	

	function sendRegistrationMail($user)
	{
        // send email to newly created user
		$userId=$user['User']['id'];
		$email = new CakeEmail();
		$fromConfig = emailFromAddress;
		$fromNameConfig = emailFromName;
		$email->from(array( $fromConfig => $fromNameConfig));
		$email->sender(array( $fromConfig => $fromNameConfig));
		$email->to($user['User']['email']);
		$email->subject('Your registration is complete');
		//$email->transport('Debug');
		$body="Welcome ".$user['User']['first_name'].", Thank you for your registration on ".SITE_URL." \n\n Thanks,\n".emailFromName;
		try{
		$result = $email->send($body);
		} catch (Exception $ex){
			// we could not send the email, ignore it
			$result="Could not send registration email to userid-".$userId;
		}
		$this->log($result, LOG_DEBUG);
    }
	function sendVerificationMail($user)
	{
		$userId=$user['User']['id'];
		$email = new CakeEmail();
		$fromConfig = emailFromAddress;
		$fromNameConfig = emailFromName;
		$email->from(array( $fromConfig => $fromNameConfig));
		$email->sender(array( $fromConfig => $fromNameConfig));
		$email->to($user['User']['email']);
		$email->subject('Email Verification Mail');
		$activate_key = $this->getActivationKey($user['User']['password']);
		$link = Router::url("/userVerification?ident=$userId&activate=$activate_key",true);
		$body="Hi ".$user['User']['first_name'].", Click the link below to complete your registration \n\n ".$link;
		try{
			$result = $email->send($body);
		} catch (Exception $ex){
			// we could not send the email, ignore it
			$result="Could not send verification email to userid-".$userId;
		}
		$this->log($result, LOG_DEBUG);
	}
	function getActivationKey($password)
	{
		$salt = Configure::read ( "Security.salt" );
		return md5(md5($password).$salt);
	}
	function forgotPassword($user)
	{
		$userId=$user['User']['id'];
		$email = new CakeEmail();
		$fromConfig = emailFromAddress;
		$fromNameConfig = emailFromName;
		$email->from(array( $fromConfig => $fromNameConfig));
		$email->sender(array( $fromConfig => $fromNameConfig));
		$email->to($user['User']['email']);
		$email->subject(emailFromName.': Request to Reset Your Password');
		$activate_key = $this->getActivationKey($user['User']['password']);
		$link = Router::url("/activatePassword?ident=$userId&activate=$activate_key",true);
		$body= "Welcome ".$user['User']['first_name'].", let's help you get signed in 

You have requested to have your password reset on ".emailFromName.". Please click the link below to reset your password now :

".$link."


If above link does not work please copy and paste the URL link (above) into your browser address bar to get to the Page to reset password

Choose a password you can remember and please keep it secure. 

Thanks,\n".

emailFromName;
		try{
			$result = $email->send($body);
		} catch (Exception $ex){
			// we could not send the email, ignore it
			$result="Could not send forgot password email to userid-".$userId;
		}
		$this->log($result, LOG_DEBUG);
	}
	function authsomeLogin($type, $credentials = array())
	{
		switch ($type) {
			case 'guest':
				// You can return any non-null value here, if you don't
				// have a guest account, just return an empty array
				return array();
			case 'cookie':
				list($token, $userId) = split(':', $credentials['token']);
				$duration = $credentials['duration'];

				$loginToken = $this->LoginToken->find('first', array(
					'conditions' => array(
						'user_id' => $userId,
						'token' => $token,
						'duration' => $duration,
						'used' => false,
						'expires <=' => date('Y-m-d H:i:s', strtotime($duration)),
					),
					'contain' => false
				));
				if (!$loginToken) {
					return false;
				}
				$loginToken['LoginToken']['used'] = true;
				$this->LoginToken->save($loginToken);

				$conditions = array(
					'User.id' => $loginToken['LoginToken']['user_id']
				);
            break;
			default:
				return array();
		}
        return $this->find('first', compact('conditions'));
    }
	function authsomePersist($userId, $duration)
	{
		$token = md5(uniqid(mt_rand(), true));
		$this->LoginToken->create(array(
			'user_id' => $userId,
			'token' => $token,
			'duration' => $duration,
			'expires' => date('Y-m-d H:i:s', strtotime($duration)),
		));
		$this->LoginToken->save();
		return "${token}:${userId}";
	}
	function getNameById($userId)
	{
		$res = $this->findById($userId);
		$name='';
		if(!empty($res))
		{
			$name=$res['User']['first_name'].' '.$res['User']['last_name'];
		}
		return $name;
	}
}
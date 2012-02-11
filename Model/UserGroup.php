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
class UserGroup extends UserMgmtAppModel
{
	var $hasMany = array('Usermgmt.UserGroupPermission');
	var $validate = array();
	function addValidate()
	{
		$validate1 = array(
				'name'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter group name',
						'last'=>true),
					'mustUnique'=>array(
						'rule' =>'isUnique',
						'message' =>'This group name already added',
						'on'=>'create',
						'last'=>true),
					),
				'alias_name'=> array(
					'mustNotEmpty'=>array(
						'rule' => 'notEmpty',
						'message'=> 'Please enter alias group name',
						'last'=>true),
					'mustUnique'=>array(
						'rule' =>'isUnique',
						'message' =>'This alias group name already added',
						'on'=>'create',
						'last'=>true),
					),
				);
		$this->validate=$validate1;
		return $this->validates();
	}
	/*
		Function-isUserGroupAccess()
		Arguments-
		@controller- controller name 
		@action- action name 
		@userGroupID- group id
		Description- Used to check permissions of group
	*/
	function isUserGroupAccess($controller, $action, $userGroupID)
	{
		$includeGuestPermission=false;
		if(!PERMISSIONS)
			return true;
		if($userGroupID==ADMIN_GROUP_ID && !ADMIN_PERMISSIONS)
			return true;
		//if (empty($access) || $access=='/' || substr($access,0,4)=='css/')
		//	return true;

		$permissions = $this->getPermissions($userGroupID,$includeGuestPermission);
		$access =str_replace(' ','',ucwords(str_replace('_',' ',$controller))).'/'.$action;
		if(in_array($access, $permissions))
		{
			return true;
		}
		return false;
	}
	/*
		Function-isGuestAccess()
		Arguments-
		@controller- controller name 
		@action- action name 
		Description- Used to check permissions of guest group
	*/
	function isGuestAccess($controller, $action)
	{
		if(PERMISSIONS)
			return $this->isUserGroupAccess($controller, $action, GUEST_GROUP_ID);
		else
			return true;
	}
	/*
		Function-getPermissions()
		Arguments-
		@userGroupID- group id
		Description- Used to get permissions from cache or database of a group1
	*/
	function getPermissions($userGroupID)
	{
		$permissions = array();
		// using the cake cache to store rules
		$cacheKey = 'rules_for_group_'.$userGroupID;
		$actions = Cache::read($cacheKey, 'UserMgmt');
		if ($actions === false) 
		{
			$actions = $this->UserGroupPermission->find('all',array('conditions'=>'UserGroupPermission.user_group_id = '.$userGroupID.' AND UserGroupPermission.allowed = 1'));
			Cache::write($cacheKey, $actions, 'UserMgmt');
		}
		foreach ($actions as $action)
		{
			$permissions[] = $action['UserGroupPermission']['controller'].'/'.$action['UserGroupPermission']['action'];
		}
		return $permissions;
	}
	/*
		Function-getGroupNames()
		Arguments-
		Description- Used to get group names
	*/
	function getGroupNames()
	{
		$this->unbindModel(array('hasMany' => array('UserGroupPermission')));
		$result=$this->find("all", array("order"=>"id"));
		$i=0;
		$user_groups=array();
		foreach($result as $row)
		{
			$user_groups[$i]=$row['UserGroup']['name'];
			$i++;
		}
		return $user_groups;
	}	
	/*
		Function-getGroupNamesAndIds()
		Arguments-
		Description- Used to get group names with ids
	*/
	function getGroupNamesAndIds()
	{
		$this->unbindModel(array('hasMany' => array('UserGroupPermission')));
		$result=$this->find("all", array("order"=>"id"));
		$i=0;
		foreach($result as $row)
		{
			$data['id']=$row['UserGroup']['id'];
			$data['name']=$row['UserGroup']['name'];
			$user_groups[$i]=$data;
			$i++;
		}
		return $user_groups;
	}
	/*
		Function-getGroups()
		Arguments-
		Description- Used to get group names with ids without guest group
	*/
	function getGroups()
	{
		$this->unbindModel(array('hasMany' => array('UserGroupPermission')));
		$result=$this->find("all", array("order"=>"id", "conditions"=>array('name !='=>"Guest")));
		$user_groups=array();
		$user_groups[0]='Select';
		foreach($result as $row)
		{
			$user_groups[$row['UserGroup']['id']]=$row['UserGroup']['name'];
		}
		return $user_groups;
	}
	/*
		Function-getGroupsForRegistration()
		Arguments-
		Description- Used to get group names with ids for registration
	*/
	function getGroupsForRegistration()
	{
		$this->unbindModel(array('hasMany' => array('UserGroupPermission')));
		$result=$this->find("all", array("order"=>"id", "conditions"=>array('allowRegistration'=>1)));
		$user_groups=array();
		$user_groups[0]='Select';
		foreach($result as $row)
		{
			$user_groups[$row['UserGroup']['id']]=$row['UserGroup']['name'];
		}
		return $user_groups;
	}
	/*
		Function-isAllowedForRegistration()
		Arguments-
		Description- Used to check group is available for registration
	*/
	function isAllowedForRegistration($groupId)
	{
		$result=$this->findById($groupId);
		if(!empty($result))
		{
			if($result['UserGroup']['allowRegistration']==1)
				return true;
		}
		return false;
	}
}
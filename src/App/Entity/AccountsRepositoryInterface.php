<?php

namespace App\Entity;

interface AccountsRepositoryInterface
{
	public function getGroups();
	
	public function getGroup($id);
	
	public function updateGroup($id, $name, $image, $prefix, $suffix);
	
	public function addGroup($name, $image, $prefix, $suffix);
	
	public function deleteGroup($id);
	
	public function getUsers();
	
	public function registerUser($login, $password, $email);
	
	public function registeredUser($login, $password, $email);
	
	public function addUser($login, $password, $email, $avatar, $group, $permissions, $responsibilities);
    
    public function checkUser($login);
	
	public function checkUserExists($login, $email);
    
    public function getUser($id);
	
	public function updateUser($id, $login, $password, $email, $avatar, $group, $permissions, $responsibilities);
	
	public function updatePassword($id, $password);
	
	public function getUsersResponsibilities();
	
	public function getUserResponsibilities($id);
	
	public function getResponsibilities($id);
}


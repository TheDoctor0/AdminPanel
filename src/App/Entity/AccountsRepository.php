<?php

namespace App\Entity;

class AccountsRepository implements AccountsRepositoryInterface
{
    private $db;
    
    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }
	
	public function getGroups()
    {
        return $this->db->query("SELECT id, name FROM groups ORDER BY id ASC");
    }
	
	public function getGroup($id)
	{
		$query = $this->db->prepare("SELECT * FROM groups WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function updateGroup($id, $name, $image, $prefix, $suffix)
	{
		$query = $this->db->prepare("UPDATE groups SET name = :name, image = :image, prefix = :prefix, suffix = :suffix WHERE id = :id");
        $query->bindParam(":id", $id);
		$query->bindParam(":name", $name);
		$query->bindParam(":image", $image);
		$query->bindParam(":prefix", $prefix);
		$query->bindParam(":suffix", $suffix);
        $query->execute();
	}
	
	public function addGroup($name, $image, $prefix, $suffix)
	{
        $query = $this->db->prepare("INSERT INTO groups VALUES(NULL, :name, :image, :prefix, :suffix)");
		$query->bindParam(":name", $name);
        $query->bindParam(":image", $image);
		$query->bindParam(":prefix", $prefix);
		$query->bindParam(":suffix", $suffix);
        $query->execute();
    }
	
	public function deleteGroup($id)
	{
        $query = $this->db->prepare("DELETE FROM groups WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
		$query = $this->db->prepare("UPDATE users SET group = '0' WHERE group = :id");
        $query->bindParam(":id", $id);
        $query->execute();
    }
	
	public function getUsers()
    {
        return $this->db->query("SELECT id, login FROM users");
    }
	
	public function registerUser($login, $password, $email)
	{
        $query = $this->db->prepare("INSERT INTO users (login, password, email) VALUES (:login, :password, :email)");
        $query->bindParam(":login", $login);
        $query->bindParam(":password", $password);
        $query->bindParam(":email", $email);
        $query->execute();

        $id = $this->db->lastInsertId();
		
		$query = $this->db->prepare("INSERT INTO responsibilities (id, responsibilities) VALUES (:id, '')");
		$query->bindParam(":id", $id);
		$query->execute();
    }
	
	public function registeredUser($login, $password, $email)
	{
        $query = $this->db->prepare("UPDATE users SET password = :password WHERE login = :login AND email = :email");
        $query->bindParam(":login", $login);
        $query->bindParam(":password", $password);
		$query->bindParam(":email", $email);
        $query->execute();
    }
	
	public function addUser($login, $password, $email, $avatar, $group, $permissions, $responsibilities)
	{
        if(!$password)
			$query = $this->db->prepare("INSERT INTO users (login, email, avatar, users.group) VALUES (:login, :email, :avatar, :group)");
		else
		{
			$query = $this->db->prepare("INSERT INTO users (login, password, email, avatar, users.group, permissions) VALUES (:login, :password, :email, :avatar, :group, :permissions)");
			$query->bindParam(":password", $password);
			$query->bindParam(":permissions", $permissions);
		}
		$query->bindParam(":login", $login);
		$query->bindParam(":email", $email);
		$query->bindParam(":avatar", $avatar);
		$query->bindParam(":group", $group);
        $query->execute();
		
		$id = $this->db->lastInsertId();
		
		$query = $this->db->prepare("INSERT INTO responsibilities (id, responsibilities) VALUES (:id, :responsibilities)");
		$query->bindParam(":id", $id);
		$query->bindParam(":responsibilities", $responsibilities);
		$query->execute();
    }
    
    public function checkUser($login)
    {
        $query = $this->db->prepare("SELECT id, login, password, permissions FROM users WHERE login = :login");
        $query->bindParam(":login", $login);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
	
	public function checkUserExists($login, $email)
    {
        $query = $this->db->prepare("SELECT id, password FROM users WHERE login = :login OR email = :email");
        $query->bindParam(":login", $login);
		$query->bindParam(":email", $email);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function getUser($id)
    {
        $query = $this->db->prepare("SELECT id, login, password, avatar, email, a.group, permissions FROM users a WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
	
	public function updateUser($id, $login, $password, $email, $avatar, $group, $permissions, $responsibilities)
	{
		if(!$password)
			$query = $this->db->prepare("UPDATE users SET login = :login, email = :email, avatar = :avatar, users.group = :group, permissions = :permissions WHERE id = :id");
		else
		{
			$query = $this->db->prepare("UPDATE users SET login = :login, password = :password, email = :email, avatar = :avatar, users.group = :group, permissions = :permissions WHERE id = :id");
			$query->bindParam(":password", $password);
		}
        $query->bindParam(":id", $id);
		$query->bindParam(":login", $login);
		$query->bindParam(":email", $email);
		$query->bindParam(":avatar", $avatar);
		$query->bindParam(":group", $group);
		$query->bindParam(":permissions", $permissions);
        $query->execute();
		
		$query = $this->db->prepare("UPDATE responsibilities SET responsibilities = :responsibilities WHERE id = :id");
		$query->bindParam(":id", $id);
		$query->bindParam(":responsibilities", $responsibilities);
		$query->execute();
	}
	
	public function updatePassword($id, $password)
	{
		$query = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $query->bindParam(":id", $id);
		$query->bindParam(":password", $password);
        $query->execute();
	}
	
	public function getUsersResponsibilities()
	{
		$query = $this->db->prepare("SELECT b.id, b.login, a.prefix, a.suffix FROM groups a JOIN users b ON a.id = b.group WHERE b.group > 0 ORDER BY b.group ASC");
        $query->execute();
		
        return $query;
	}
	
	public function getUserResponsibilities($id)
    {
        $query = $this->db->prepare("SELECT responsibilities FROM responsibilities WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
    }
	
	public function getResponsibilities($id)
	{
		$query = $this->db->prepare("SELECT a.login, a.avatar, b.image, b.prefix, b.suffix, c.responsibilities FROM users a JOIN groups b ON a.group = b.id JOIN responsibilities c ON a.id = c.id WHERE a.id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
		return $query->fetch(\PDO::FETCH_ASSOC);
	}
}


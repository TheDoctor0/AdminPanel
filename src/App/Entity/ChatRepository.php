<?php

namespace App\Entity;

class ChatRepository implements ChatRepositoryInterface
{
    private $db;
    
    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }
	
	public function getMessages($id)
	{
		$query = $this->db->prepare("DELETE FROM server_chat WHERE data + 43200 < UNIX_TIMESTAMP()");
		$query->execute();
		
		$query = $this->db->prepare("SELECT * FROM server_chat WHERE server = :server ORDER BY data DESC LIMIT 50");
		$query->bindParam(":server", $id);
		$query->execute();
		
		return $query->fetchAll();
	}
}
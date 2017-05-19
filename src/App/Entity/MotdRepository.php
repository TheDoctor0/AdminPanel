<?php

namespace App\Entity;

class ServersRepository implements ServersRepositoryInterface
{
    private $db;
    
    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }
	
	public function getMOTD()
	{
		$query = $this->db->prepare("SELECT motd FROM motd");
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function saveMOTD($motd)
	{
		$query = $this->db->prepare("UPDATE motd SET motd = :motd");
		$query->bindParam(":motd", $motd);
		$query->execute();
	}
}
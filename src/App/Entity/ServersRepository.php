<?php

namespace App\Entity;

class ServersRepository implements ServersRepositoryInterface
{
    private $db;
    
    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }
	
	public function getServers()
	{
		return $this->db->query("SELECT id, name, CONCAT(ip, ':', port) as ip FROM ss_servers ORDER BY type, name");
	}
	
	public function getCSServers()
	{
		return $this->db->query("SELECT id, name, CONCAT(ip, ':', port) as ip FROM ss_servers WHERE type = 'amxx' ORDER BY name");
	}
}
<?php

namespace App\Entity;

class AdminsRepository implements AdminsRepositoryInterface
{
    private $db;
    
    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }
    
    public function getAdmins()
	{
		$query = $this->db->prepare("DELETE FROM ss_admins WHERE date > 0 AND date + 1209600 < UNIX_TIMESTAMP()");
		$query->execute();
		
		return $this->db->query("SELECT a.id, a.name, a.steamid, a.contact, b.name AS server, a.date, a.type, (CASE WHEN a.date > UNIX_TIMESTAMP() OR (a.type >= 1 AND a.date = 0) THEN '1' ELSE '0' END) AS active FROM ss_admins a LEFT JOIN ss_servers b ON a.server = b.id ORDER BY a.server ASC, a.type DESC, a.date DESC, a.name ASC");
	}
	
	public function getAdminsAll()
	{
		$query = $this->db->prepare("DELETE FROM ss_admins WHERE date > 0 AND date + 1209600 < UNIX_TIMESTAMP()");
		$query->execute();
		
		return $this->db->query("SELECT a.id, a.name, b.name AS server, a.contact, a.type FROM ss_admins a LEFT JOIN ss_servers b ON a.server = b.id WHERE a.date > UNIX_TIMESTAMP() OR (a.type >= 1 AND a.date = 0) ORDER BY a.server ASC, a.type DESC, a.date DESC, a.name ASC");
	}
	
	public function getAdmin($id)
	{
		$query = $this->db->prepare("SELECT name, steamid, server, service FROM ss_admins WHERE id = :id");
		$query->bindParam(":id", $id);
		$query->execute();

		$result = $query->fetch(\PDO::FETCH_ASSOC);

		$query = $this->db->prepare("SELECT a.us_id, a.password, b.expire, a.type FROM ss_user_service_extra_flags a JOIN ss_user_service b ON a.us_id = b.id WHERE (a.service = 'admin' OR a.service = 'goadmin') AND (a.auth_data = :steamid OR a.auth_data = :name) AND server = :server");
		$query->bindParam(":steamid", $result['steamid']);
		$query->bindParam(":name", $result['name']);
		$query->bindParam(":server", $result['server']);
		$query->execute();

		$result = $query->fetch(\PDO::FETCH_ASSOC);

		if($result['us_id'] > 0)
		{
			$query = $this->db->prepare("UPDATE ss_admins SET service = :service, password = :password, date = :date, service_type = :type WHERE id = :id AND (service != :service OR password != :password OR date != :date OR service_type != :type)");
			$query->bindParam(":service", $result['us_id']);
			$query->bindParam(":password", $result['password']);
			$query->bindParam(":date", $result['expire']);
			$query->bindParam(":type", $result['type']);
			$query->bindParam(":id", $id);
			$query->execute();
		}
		
		$query = $this->db->prepare("SELECT id, name, steamid, password, contact, server, service, service_type, date, type, (CASE WHEN date > UNIX_TIMESTAMP() OR (type >= 1 AND date = 0) THEN '1' ELSE '0' END) AS active FROM ss_admins WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
		
        return $query->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function extendAdmin($id, $service, $service_type, $server, $name, $steamid, $password)
	{
		$query = $this->db->prepare("SELECT b.type FROM ss_admins a JOIN ss_servers b ON a.server = b.id WHERE a.server = :server");
		$query->bindParam(":server", $server);
		$query->execute();

		$server_type = $query->fetch(\PDO::FETCH_ASSOC);

		if($server_type['type'] == "amxx")
		{
			if($service_type > 1)
			{
				$auth_data = $steamid;
			}
			else
			{
				$auth_data = $name;
			}
			$service_name = "admin";
			$service_type = "1";
		}
		else
		{
			$auth_data = $steamid;
			$service_name = "goadmin";
			$service_type = "4";
		}
		
		$query = $this->db->prepare("SELECT COUNT(b.id) as total FROM ss_admins a JOIN ss_players_flags b ON a.server = b.server WHERE b.auth_data = :auth_data");
		$query->bindParam(":auth_data", $auth_data);
		$query->execute();

		$exists = $query->fetch(\PDO::FETCH_ASSOC);

		if($exists['total'] > 0)
		{
			if($server_type['type'] == "amxx")
			{
				$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; UPDATE ss_players_flags SET password = :password, b = @t, c = @t, d = @t, e = @t, i = @t, j = @t, m = @t, n = @t, s = @t, u = @t, t = @t WHERE server = :server AND auth_data = :auth_data");
			}
			else
			{
				$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; UPDATE ss_players_flags SET password = :password, a = @t, b = @t, c = @t, d = @t, f = @t, g = @t, j = @t, k = @t, e = @t, t = @t WHERE server = :server AND auth_data = :auth_data");
			}
			$query->bindParam(":password", $password);
			$query->bindParam(":server", $server);
			$query->bindParam(":auth_data", $auth_data);
			$query->execute();
			
			$query = $this->db->prepare("SELECT COUNT(id) as total FROM ss_user_service WHERE id = :service");
			$query->bindParam(":service", $service);
			$query->execute();

			$services = $query->fetch(\PDO::FETCH_ASSOC);
			
			if($services['total'] > 0)
			{
				$query = $this->db->prepare("UPDATE ss_user_service SET expire = expire + 2592000 WHERE id = :service");
				$query->bindParam(":service", $service);
				$query->execute();
				
				$query = $this->db->prepare("UPDATE ss_admins SET date = date + 2592000 WHERE id = :id");
				$query->bindParam(":id", $id);
				$query->execute();
			}
			else
			{
				$query = $this->db->prepare("INSERT INTO ss_user_service (service, uid, expire) VALUES (:service, '0', UNIX_TIMESTAMP() + 2592000)");
				$query->bindParam(":service", $service_name);
				$query->execute();
				$service_id = $this->db->lastInsertId();

				$query = $this->db->prepare("UPDATE ss_admins SET service = :service_id WHERE id = :id");
				$query->bindParam(":service_id", $service_id);
				$query->bindParam(":id", $id);
				$query->execute();
				
				$query = $this->db->prepare("UPDATE ss_admins SET date = UNIX_TIMESTAMP() + 2592000 WHERE id = :id");
				$query->bindParam(":id", $id);
				$query->execute();

				$query = $this->db->prepare("INSERT INTO ss_user_service_extra_flags (us_id, service, server, type, auth_data, password) VALUES (:id, :service, :server, :type, :auth_data, :password)");
				$query->bindParam(":id", $service_id);
				$query->bindParam(":service", $service_name);
				$query->bindParam(":server", $server);
				$query->bindParam(":type", $service_type);
				$query->bindParam(":auth_data", $auth_data);
				$query->bindParam(":password", $password);
				$query->execute();
			}
		}
		else
		{
			if($server_type['type'] == "amxx")
			{
				$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; INSERT INTO ss_players_flags (server, type, auth_data, password, b, c, d, e, i, j, m, n, s, u, t) VALUES (:server, :type, :auth_data, :password, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t)");
			}
			else
			{
				$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; INSERT INTO ss_players_flags (server, type, auth_data, password, a, b, c, d, f, g, j, k, e, t) VALUES (:server, :type, :auth_data, :password, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t)");
			}
			$query->bindParam(":server", $server);
			$query->bindParam(":type", $service_type);
			$query->bindParam(":auth_data", $auth_data);
			$query->bindParam(":password", $password);
			$query->execute();
			
			$query = $this->db->prepare("INSERT INTO ss_user_service (service, uid, expire) VALUES (:service, '0', UNIX_TIMESTAMP() + 2592000)");
			$query->bindParam(":service", $service_name);
			$query->execute();
			$service_id = $this->db->lastInsertId();

			$query = $this->db->prepare("UPDATE ss_admins SET service = :service_id WHERE id = :id");
			$query->bindParam(":service_id", $service_id);
			$query->bindParam(":id", $id);
			$query->execute();
			
			$query = $this->db->prepare("UPDATE ss_admins SET date = UNIX_TIMESTAMP() + 2592000 WHERE id = :id");
			$query->bindParam(":id", $id);
			$query->execute();

			$query = $this->db->prepare("INSERT INTO ss_user_service_extra_flags (us_id, service, server, type, auth_data, password) VALUES (:id, :service, :server, :type, :auth_data, :password)");
			$query->bindParam(":id", $service_id);
			$query->bindParam(":service", $service_name);
			$query->bindParam(":server", $server);
			$query->bindParam(":type", $service_type);
			$query->bindParam(":auth_data", $auth_data);
			$query->bindParam(":password", $password);
			$query->execute();
		}
	}
	
	public function deleteAdmin($id, $service, $service_type, $server, $name, $steamid)
	{
		$query = $this->db->prepare("SELECT b.type FROM ss_admins a JOIN ss_servers b ON a.server = b.id WHERE a.server = :server");
		$query->bindParam(":server", $server);
		$query->execute();

		$server_type = $query->fetch(\PDO::FETCH_ASSOC);
		
		if($server_type['type'] == "amxx")
		{
			if($service_type > 1)
			{
				$auth_data = $steamid;
			}
			else
			{
				$auth_data = $name;
			}
		}
		else
		{
			$auth_data = $steamid;
		}
		
		$query = $this->db->prepare("DELETE FROM ss_players_flags WHERE server = :server AND auth_data = :auth_data");
		$query->bindParam(":server", $server);
		$query->bindParam(":auth_data", $auth_data);
		$query->execute();
		
		$query = $this->db->prepare("DELETE FROM ss_user_service WHERE id = :id");
		$query->bindParam(":id", $service);
		$query->execute();
		
		$query = $this->db->prepare("DELETE FROM ss_user_service_extra_flags WHERE us_id = :id");
		$query->bindParam(":id", $service);
		$query->execute();
		
		$query = $this->db->prepare("DELETE FROM ss_admins WHERE id = :id");
        $query->bindParam(":id", $id);
        $query->execute();
    }
	
	public function updateAdmin($id, $name, $steamid, $password, $contact, $server, $service, $type, $old_name, $old_steamid, $old_server)
	{
		$query = $this->db->prepare("UPDATE ss_admins SET name = :name, steamid = :steamid, password = :password, contact = :contact, server = :server, type = :type WHERE id = :id");
        $query->bindParam(":id", $id);
		$query->bindParam(":name", $name);
		$query->bindParam(":steamid", $steamid);
		$query->bindParam(":password", $password);
		$query->bindParam(":contact", $contact);
		$query->bindParam(":server", $server);
		$query->bindParam(":type", $type);
        $query->execute();
		
		if($service > 0 && ($name != $old_name || $steam_id != $old_steamid || $server != $old_server))
		{
			$query = $this->db->prepare("SELECT b.type FROM ss_admins a JOIN ss_servers b ON a.server = b.id WHERE a.server = :server");
			$query->bindParam(":server", $server);
			$query->execute();

			$server_type = $query->fetch(\PDO::FETCH_ASSOC);

			if($server_type['type'] == "amxx")
			{
				$auth_data = $name;
				$old_auth_data = $old_name;
				$service_name = "admin";
				$service_type = "1";
			}
			else
			{
				$auth_data = $steamid;
				$old_auth_data = $old_steamid;
				$service_name = "goadmin";
				$service_type = "4";
			}
	
			if($server_type['type'] == "amxx")
			{
				$query = $this->db->prepare("UPDATE ss_players_flags SET server = :server, type = :type, auth_data = :auth_data, password = :password WHERE server = :old_server AND auth_data = :old_auth_data");
				$query->bindParam(":password", $password);
			}
			else
			{
				$query = $this->db->prepare("UPDATE ss_players_flags SET server = :server, type = :type, auth_data = :auth_data WHERE server = :old_server AND auth_data = :old_auth_data");
				$query->bindParam(":server", $server);
			}
			$query->bindParam(":server", $server);
			$query->bindParam(":type", $service_type);
			$query->bindParam(":auth_data", $auth_data);
			$query->bindParam(":old_server", $old_server);
			$query->bindParam(":old_auth_data", $old_auth_data);
			$query->execute();

			$query = $this->db->prepare("UPDATE ss_user_service SET service = :service WHERE id = :service_id");
			$query->bindParam(":service", $service_name);
			$query->bindParam(":service_id", $service);
			$query->execute();

			$query = $this->db->prepare("UPDATE ss_user_service_extra_flags SET service = :service, server = :server, type = :type, auth_data = :auth_data, password = :password WHERE us_id = :service_id");
			$query->bindParam(":service", $service_name);
			$query->bindParam(":server", $server);
			$query->bindParam(":type", $service_type);
			$query->bindParam(":auth_data", $auth_data);
			$query->bindParam(":password", $password);
			$query->bindParam(":service_id", $service);
			$query->execute();
		}
	}
	
	public function addAdmin($name, $steamid, $password, $contact, $server, $type, $service)
	{
        $query = $this->db->prepare("INSERT INTO ss_admins (name, steamid, password, contact, server, type) VALUES(:name, :steamid, :password, :contact, :server, :type)");
        $query->bindParam(":name", $name);
		$query->bindParam(":steamid", $steamid);
		$query->bindParam(":password", $password);
		$query->bindParam(":contact", $contact);
		$query->bindParam(":server", $server);
		$query->bindParam(":type", $type);
        $query->execute();
		$id = $this->db->lastInsertId();
		
		if($service > 0)
		{
			$query = $this->db->prepare("UPDATE ss_admins SET date = UNIX_TIMESTAMP() + 2592000 WHERE id = :id");
			$query->bindParam(":id", $id);
			$query->execute();
			
			$query = $this->db->prepare("SELECT b.type FROM ss_admins a JOIN ss_servers b ON a.server = b.id WHERE a.server = :server");
			$query->bindParam(":server", $server);
			$query->execute();
		
			$server_type = $query->fetch(\PDO::FETCH_ASSOC);
			
			if($server_type['type'] == "amxx")
			{
				if($service > 1)
				{
					$auth_data = $steamid;
				}
				else
				{
					$auth_data = $name;
				}
				$service_name = "admin";
				$service_type = "1";
			}
			else
			{
				$auth_data = $steamid;
				$service_name = "goadmin";
				$service_type = "4";
			}

			$query = $this->db->prepare("SELECT COUNT(b.id) as total FROM ss_admins a JOIN ss_players_flags b ON a.server = b.server WHERE b.auth_data = :auth_data");
			$query->bindParam(":auth_data", $auth_data);
			$query->execute();

			$exists = $query->fetch(\PDO::FETCH_ASSOC);

			if($exists['total'] > 0)
			{
				if($server_type['type'] == "amxx")
				{
					$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; UPDATE ss_players_flags SET password = :password, b = @t, c = @t, d = @t, e = @t, i = @t, j = @t, m = @t, n = @t, s = @t, u = @t, t = @t WHERE server = :server AND auth_data = :auth_data");
				}
				else
				{
					$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; UPDATE ss_players_flags SET password = :password, a = @t, b = @t, c = @t, d = @t, f = @t, g = @t, j = @t, k = @t, e = @t, t = @t WHERE server = :server AND auth_data = :auth_data");
				}
				$query->bindParam(":password", $password);
				$query->bindParam(":server", $server);
				$query->bindParam(":auth_data", $auth_data);
				$query->execute();
			}
			else
			{
				if($server_type['type'] == "amxx")
				{
					$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; INSERT INTO ss_players_flags (server, type, auth_data, password, b, c, d, e, i, j, m, n, s, u, t) VALUES (:server, :type, :auth_data, :password, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t)");
				}
				else
				{
					$query = $this->db->prepare("SET @t = UNIX_TIMESTAMP() + 2592000; INSERT INTO ss_players_flags (server, type, auth_data, password, a, b, c, d, f, g, j, k, e, t) VALUES (:server, :type, :auth_data, :password, @t, @t, @t, @t, @t, @t, @t, @t, @t, @t)");
				}
				$query->bindParam(":server", $server);
				$query->bindParam(":type", $service_type);
				$query->bindParam(":auth_data", $auth_data);
				$query->bindParam(":password", $password);
				$query->execute();
			}
	
			$query = $this->db->prepare("INSERT INTO ss_user_service (service, uid, expire) VALUES (:service, '0', UNIX_TIMESTAMP() + 2592000)");
			$query->bindParam(":service", $service_name);
			$query->execute();
			$service_id = $this->db->lastInsertId();
			
			$query = $this->db->prepare("UPDATE ss_admins SET service = :service_id, service_type = :service_type WHERE id = :id");
			$query->bindParam(":service_id", $service_id);
			$query->bindParam(":service_type", $service_type);
			$query->bindParam(":id", $id);
			$query->execute();

			$query = $this->db->prepare("INSERT INTO ss_user_service_extra_flags (us_id, service, server, type, auth_data, password) VALUES (:id, :service, :server, :type, :auth_data, :password)");
			$query->bindParam(":id", $service_id);
			$query->bindParam(":service", $service_name);
			$query->bindParam(":server", $server);
			$query->bindParam(":type", $service_type);
			$query->bindParam(":auth_data", $auth_data);
			$query->bindParam(":password", $password);
			$query->execute();
		}
    }
}


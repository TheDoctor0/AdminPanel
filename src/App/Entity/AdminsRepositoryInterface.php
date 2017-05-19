<?php

namespace App\Entity;

interface AdminsRepositoryInterface
{
    public function getAdmins();
	
	public function getAdminsAll();
	
	public function getAdmin($id);
	
	public function deleteAdmin($id, $service, $service_type, $server, $name, $steamid);
	
	public function extendAdmin($id, $service, $service_type, $server, $name, $steamid, $password);
	
	public function updateAdmin($id, $name, $steamid, $password, $contact, $server, $service, $type, $old_name, $old_steamid, $old_server);
	
	public function addAdmin($name, $steamid, $password, $contact, $server, $type, $service);
}


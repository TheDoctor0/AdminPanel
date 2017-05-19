<?php

namespace App\Entity;

interface MotdRepositoryInterface
{
	public function getMOTD();

	public function saveMOTD($motd);
}

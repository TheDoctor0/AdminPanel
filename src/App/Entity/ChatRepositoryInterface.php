<?php

namespace App\Entity;

interface ChatRepositoryInterface
{
	public function getMessages($id);
}

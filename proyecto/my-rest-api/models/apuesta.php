<?php 
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Validator\Uniqueness;
use Phalcon\Mvc\Model\Validator\InclusionIn;



class apuesta extends Model
{
	/**
	*Establese unas reglas de validacion para la insercion de datos
	*/
	public function validation()
	{
		//
			
				return true;
			
	}
}
?>
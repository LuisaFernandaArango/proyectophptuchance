<?php
error_reporting(E_ALL);
ini_set("display_errors", "On");
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

//Esta clase nos permitirá cargar el modelo
$loader = new Loader();
//	echo __DIR__.'/models/';
//registramos el directorio donde tenemos los modelos
$loader->registerDirs(

	array(
		__DIR__.'/models/'
		)
	)->register();

//carga todos los servicios que provee falcon
$di = new FactoryDefault();

//Inicializamos la configuracion de la DB
$di->set('db', function(){
	return new PdoMysql(
		array(
			"host"     => getenv('IP'),
			"username" => "root",
			"password" => "",
			"dbname"   => "TuChance"
			)
		);
});

//creamos una instancia de Micro
//Micro, corresponde a una clase usada ara crear micro Aplicaciones
$app = new Micro($di);


//INICIO se definen las rutas de la API

//obtiene una lista con todos los usuarios de la BD
$app->get('/api/users', function() use ($app){

	//creamos una consulta phql
	$phql = "SELECT * FROM users WHERE tipo = 1 ORDER BY nombre ";
	//El modelsManeger ejecuta consultas directamente
	$users = $app->modelsManager->executeQuery($phql);
	//creamos un array que contenga todos los datos
	$data = array();
	foreach ($users as $user) {
		# code...
		$data[] = array(
			'id' => $user->id,
			'nombre' => $user->nombre
			);
	}
	//Enviamos los datos en formato JSON
	echo json_encode($data);
});

//busca un usuario por su username y contraseña (Logeo) Administrados
$app->get('/api/users/searchAdmin/{username}/{pass}', function($username,$pass) use ($app) {
	
   	$contra= md5($pass);
    $fecha = date("Y-m-d",time());
   	$phql = "SELECT * FROM users WHERE tipo = 2 AND username LIKE :username: AND pass LIKE '$contra' ";
   	
   	$phpl2 = "UPDATE users SET onLine = 1, last_connection= '$fecha' WHERE username LIKE :username:";
   	$users = $app->modelsManager->executeQuery(
		$phpl2,
		array(
			'username' => '%' . $username . '%'
			
			)
		);
	$users = $app->modelsManager->executeQuery(
		$phql,
		array( 'username' => '%' . $username . '%'))->getFirst();
	//Crea un objeto detipo respuesta
	$response = new Response();
	//si mp hay ningun usuario 
	if($users == false) {
		$response->setJsonContent(
			array(
				'status' => 'NOT-FOUND'
				)
			);
	}
	else{
		//Si hay un usuario respondemos en formato JSON
		$response->setJsonContent(
			array(
				'status' => 'OK',
				'data' => 	array(
					'username' => '%' . $username . '%'
					)
				)
			);
	}
	return $response;
});


//busca un usuario por su username y contraseña (Logeo) Empleado
$app->get('/api/users/search/{username}/{pass}', function($username,$pass) use ($app) {
	
   	$contra= md5($pass);
    $fecha = date("Y-m-d",time());
   	$phql = "SELECT * FROM users WHERE username like :username: AND pass like '$contra' ";
   	
   	$phpl2 = "UPDATE users SET onLine = 1, last_connection= '$fecha' WHERE username LIKE :username:";
   	$users = $app->modelsManager->executeQuery(
		$phpl2,
		array(
			'username' => '%' . $username . '%'
			
			)
		);
	$users = $app->modelsManager->executeQuery(
		$phql,
		array( 'username' => '%' . $username . '%'))->getFirst();
	//Crea un objeto detipo respuesta
	$response = new Response();
	//si mp hay ningun usuario 
	//print_r($users);
	if($users!=null && $users->username != null) {
	
		if($users->tipo == 2){
			
			$response->setJsonContent(
			array(
				'status' => 'ADMIN',
				'data' => 	array(
					'username' =>   $username
					
					)
				)
			);
		}else
		
		if($users->tipo == 1){
			$response->setJsonContent(
			array(
				'status' => 'OK',
				'data' => 	array(
					'username' =>   $username,
					'tipo' => $users->tipo
					
					)
				)
			);
		}else{
			echo "no funciono";
		}
		
			
	}
	else{
		//Si hay un usuario respondemos en formato JSON
		$response->setJsonContent(
			array(
				'status' => 'NOT-FOUND'
				)
			);
	}
	return $response;

});
//Busca un usuario por el id dado
$app->get('/api/users/{id:[0-9]+}', function($id) use ($app) {
	//selecciona el usuario que coinciada con el id seleccionado
	$phql = "SELECT * FROM users WHERE id = :id: AND tipo = 1";
	$user = $app->modelsManager->executeQuery($phql, array(
			'id' => $id
		))->getFirst();
	//Crea un objeto detipo respuesta
	$response = new Response();
	//si mp hay ningun usuario 
	if($user == false) {
		$response->setJsonContent(
			array(
				'status' => 'NOT-FOUND'
				)
			);
	}
	else{
		//Si hay un usuario respondemos en formato JSON
		$response->setJsonContent(
			array(
				'status' => 'FOUND',
				'data' => array(
					'id'     => $user->id,
					'nombre' => $user->nombre
					)
				)
			);
	}
	return $response;
});

//Agrega un usuario Empleado via POST con JSON
$app->post('/api/users', function() use ($app) {
	$user = $app->request->getJsonRawBody();

	$phql = "INSERT INTO users(nombre,username,pass,onLine,tipo,last_connection) values(:nombre:,:username:,:pass:,:onLine:,:tipo:,:last_connection:)";
		$fecha = date("Y-m-d",time());
		$online1= 0;
		$tipoE= 1; 
		$contra= md5($user->pass);
	$status = $app->modelsManager->executeQuery($phql, array(
		'nombre' 			=> $user->nombre,
		'username' 			=> $user->username,
		'pass' 				=> $contra,
		'onLine' 			=> $online1,
		'tipo' 		 		=> $tipoE,
		'last_connection'   => $fecha
	
		)
	);

	$response = new Response();

	if($status->success() == true){
		$response->setJsonContent(array('status' => 'OK'));
	}else{
		$response->setStatusCode(409,"Conflict");
		$errors = array();
		foreach ($status->getMessages() as $message) {
			$errores[] = $message->getMessage();
		}

		$response->setJsonContent(array(
				'status' => 'ERROR',
				'messages' => $errors
				)
			);
	}
	return $response;


});



//Elimina un usuario basado en su id
$app->delete('/api/users/{id:[0-9]+}', function ($id) use ($app) {
	$phql = "DELETE FROM users WHERE id = :id:";
	$status = $app->modelsManager->executeQuery($phql, array(
		'id' => $id
		));
	//Creamo una respuesta
	$response = new Response();

	if($status->success() == true){
		$response->setJsonContent(
			array(
				'status' => 'OK'
				)
			);
	}
	else{
		//Cambia el estado de la peticion HTP
		$response->setStatusCode(409, "Conflict");
		$errors = array();
		foreach ($status->getMessage as $message) {
			# code...
			$errors[] = $message->getMessage();
		}
		$response->setJsonContent(
			array(
				'status'   => 'ERROR',
				'messages' => $errors
				)
			);
	}

});
$app->notFound(function() use ($app){
	$app->response->setStatusCode(409, "Conflict")->sendHeaders();
	echo "error";
});


//////////////////////////////////////Chance/////////////////////////////////77


//obtiene el valor de una apuesta 
$app->get('/api/chances/{idapuesta}', function($idapuesta) use ($app){

	//creamos una consulta phql
	$phql = "SELECT * FROM chance WHERE apuesta_idapuesta = '$idapuesta'  ";
	//El modelsManeger ejecuta consultas directamente
	$chances = $app->modelsManager->executeQuery($phql);
	//creamos un array que contenga todos los datos
	$data = array();
	foreach ($chances as $chance) {
		# code...
		$data[] = array(
			'numero' => $chance->numero,
			'valor' => $chance->valor
			);
	}
	$total = 0;
    //	print_r($data);
	for ($i = 0; $i < count($data); $i++)
	{
	//	print_r();
  	  $total = $data[$i]['valor'] + $total;
    }
	//Enviamos los datos en formato JSON
	echo json_encode($total);
});


//obtiene una lista con todos los chance de la BD
$app->get('/api/chances', function() use ($app){

	//creamos una consulta phql
	$phql = "SELECT * FROM chance  ";
	//El modelsManeger ejecuta consultas directamente
	$chances = $app->modelsManager->executeQuery($phql);
	//creamos un array que contenga todos los datos
	$data = array();
	foreach ($chances as $chance) {
		# code...
		$data[] = array(
			'numero' => $chance->numero,
			'valor' => $chance->valor
			);
	}
	//Enviamos los datos en formato JSON
	echo json_encode($data);
});

//Agrega un chance  via POST con JSON
$app->post('/api/chances', function() use ($app) {
	$chance = $app->request->getJsonRawBody();

	$phql = "INSERT INTO chance(apuesta_idapuesta,numero,valor,loteria,premio) values(:apuesta_idapuesta:,:numero:,:valor:,:loteria:,:premio:)";
	$idApuesta= 1; 
	$premio = ($chance->valor)*4500;
	$status = $app->modelsManager->executeQuery($phql, array(
		'apuesta_idapuesta' => $idApuesta,
		'numero' 			=> $chance->numero,
		'valor' 			=> $chance->valor,
		'loteria' 			=> $chance->loteria,
		'premio' 			=> $premio
		
	
		)
);
	$response = new Response();

	if($status->success() == true){
		$response->setJsonContent(array('status' => 'OK'));
	}else{
		$response->setStatusCode(409,"Conflict");
		$errors = array();
		foreach ($status->getMessages() as $message) {
			$errores[] = $message->getMessage();
		}

		$response->setJsonContent(array(
				'status' => 'ERROR',
				'messages' => $errors
				)
			);
	}
	return $response;


});


//actualiza la apuesta del chance por id
$app->put('/api/chances/{idchance:[0-9]+}/{apuesta_idapuesta:[0-9]+}', function($idchance,$apuesta_idapuesta) use ($app) {
	//obtiene los datos enviados en formato JSON
	$chance = $app->request->getJsonRawBody();
	//crea la ocnsulta phql
	$phql = "UPDATE chance SET apuesta_idapuesta = :apuesta_idapuesta: WHERE idchance = :idchance:";
	$status = $app->modelsManager->executeQuery($phql, array(
		'idchance'            => $idchance,
		'apuesta_idapuesta'   => $apuesta_idapuesta
	
	));
	$response =  new Response();

	//Mira si la inserción fun exitosa
	if($status->success() == true) {
		$response->setJsonContent (array( 'status' => 'OK' ) );
	}
	else{
		$response->setStatusCode(409, "Conflict");
		$errors = array();
		foreach ($status->getMessages() as $message) {
			# code...
			$errors[] = $message->getMessage();
		}
		$response->setJsonContent(
			array(
				'status'   => 'ERROR',
				'messages' => $errors
				)
			);
	}
	return $response;
});


//obtiene el ultimo chance agregado
$app->get('/api/chance', function() use ($app){

	
	//creamos una consulta phql
	$phql = "SELECT * FROM chance ORDER BY idchance DESC LIMIT 1";
	//El modelsManeger ejecuta consultas directamente
	$chances = $app->modelsManager->executeQuery($phql);
	//creamos un array que contenga todos los datos
	$data = array();
	foreach ($chances as $chance) {
		# code...
		$data[] = array(
			'idchance' => $chance->idchance
			);
	}
	//Enviamos los datos en formato JSON
	echo json_encode($data);
});

/////////////////////////////////Apuestas////////////////////////////////////////////

//Total de la apuesta
$app->put('/api/apuesta/{id:[0-9]+}/{total:[0-9]+}', function($id,$total) use ($app) {
	//obtiene los datos enviados en formato JSON
	$apuesta = $app->request->getJsonRawBody();
	//crea la ocnsulta phql
	$phql = "UPDATE apuesta SET valor = :total: WHERE idapuesta = :id:";
	$status = $app->modelsManager->executeQuery($phql, array(
		'id'=> $id,
		'total'    => $total
	));
	$response =  new Response();

	//Mira si la inserción fun exitosa
	if($status->success() == true) {
		$response->setJsonContent (array( 'status' => 'OK' ) );
	}
	else{
		$response->setStatusCode(409, "Conflict");
		$errors = array();
		foreach ($status->getMessages() as $message) {
			# code...
			$errors[] = $message->getMessage();
		}
		$response->setJsonContent(
			array(
				'status'   => 'ERROR',
				'messages' => $errors
				)
			);
	}
	return $response;
});


//obtiene la ultima apuesta agregada
$app->get('/api/apuesta', function() use ($app){

	//creamos una consulta phql
	$phql = "SELECT * FROM apuesta ORDER BY idapuesta DESC LIMIT 1";
	//El modelsManeger ejecuta consultas directamente
	$apuestas = $app->modelsManager->executeQuery($phql);
	//creamos un array que contenga todos los datos
	$data = array();
	foreach ($apuestas as $apuesta) {
		# code...
		$data[] = array(
			'id'     =>  $apuesta->idapuesta
			);
	}
	//Enviamos los datos en formato JSON
	echo json_encode($data);
});

//Agrega un apuesta  via POST con JSON
$app->post('/api/apuestas', function() use ($app) {
	$apuesta = $app->request->getJsonRawBody();

	$phql = "INSERT INTO apuesta(fecha,valor) values(:fecha:,:valor:)";
	$fecha1= date("Y-m-d",time());
	$valor=0;
	$status = $app->modelsManager->executeQuery($phql, array(
		'fecha'  => $fecha1,
		'valor'  => $valor
		)
	);
	$response = new Response();

	if($status->success() == true){
		$response->setJsonContent(array('status' => 'OK'));
	}else{
		$response->setStatusCode(409,"Conflict");
		$errors = array();
		foreach ($status->getMessages() as $message) {
			$errores[] = $message->getMessage();
		}

		$response->setJsonContent(array(
				'status' => 'ERROR',
				'messages' => $errors
				)
			);
	}
	return $response;


});


//FIN de las rutas
$app->handle();

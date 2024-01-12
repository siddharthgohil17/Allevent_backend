<?php

declare(strict_types=1);
require __DIR__ .'/../config/db.php';

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {


    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('<h1> Hello date is ' . date('Y-m-d') . '</h1>');
        return $response;
    });
    $app->get('/event/location', function (Request $request, Response $response , array $args) {
        $queryParams = $request->getQueryParams();
       
        $country = isset($queryParams['country']) ? $queryParams['country'] : null;
        $state = isset($queryParams['state']) ? $queryParams['state'] : null;
        $city = isset($queryParams['city']) ? $queryParams['city'] : null;
          
     

        if ($country === null && $state === null && $city === null) {
            $responseMessage = array(
                "message" => "All parameters are null. Please provide at least one parameter."
            );
        
            $response->getBody()->write(json_encode($responseMessage));
        
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(400);  // You can choose an appropriate HTTP status code
        }
         
        // You may want to add additional validation for the parameters if needed.
        $sql = "SELECT * FROM events WHERE 1";
        if ($country !== null) {
            $sql .= " AND country = '$country'";
        }
    
        if ($state !== null) {
            $sql .= " AND state = '$state'";
        }
    
        if ($city !== null) {
            $sql .= " AND city = '$city'";
        }
    
        try {
            $db=new DB();
            $conn=$db->connect();

            $stmt=$conn->query($sql);
            $event=$stmt->fetchAll(PDO::FETCH_OBJ);
            $db=null;
            $response->getBody()->write(json_encode($event));
            
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => "Internal Server error"
            );
    
            $response->getBody()->write(json_encode($error));
    
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(500);
        }
    });
    $app->get('/events', function (Request $request, Response $response) {
        if (!extension_loaded('pdo_mysql')) {
            die('pdo_mysql extension not installed or enabled.');
        }        
        $sql="SELECT * FROM events;";

        try{
            $db=new DB();
            $conn=$db->connect();

            $stmt=$conn->query($sql);
            $event=$stmt->fetchAll(PDO::FETCH_OBJ);
            $db=null;
            $response->getBody()->write(json_encode($event));
            
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(200);
        }catch(PDOException $e){
            $error = array(
                "message" => "Internal Server error"
            );
            $response->getBody()->write(json_encode($error));
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(500);
        }

    
    });

    $app->get('/event/{event_id}', function (Request $request, Response $response, array $args) {
        if (!extension_loaded('pdo_mysql')) {
            die('pdo_mysql extension not installed or enabled.');
        }        
        $event_id = $args['event_id'];
        $sql="SELECT * FROM events where event_id=$event_id;";

        try{
            $db=new DB();
            $conn=$db->connect();

            $stmt=$conn->query($sql);
            $event=$stmt->fetchAll(PDO::FETCH_OBJ);
            $db=null;
            $response->getBody()->write(json_encode($event));
            
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(200);
        }catch(PDOException $e){
            $error = array(
                "message" => "Internal Server error"
            );
            $response->getBody()->write(json_encode($error));
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(500);
        }

    
    });

    $app->get('/event/category/{category}', function (Request $request, Response $response, array $args) {
     
        $category = $args['category'];

        $sql="SELECT * FROM events where category='$category';";

        try{
            $db=new DB();
            $conn=$db->connect();

            $stmt=$conn->query($sql);
            $event=$stmt->fetchAll(PDO::FETCH_OBJ);
            $db=null;
            $response->getBody()->write(json_encode($event));
        
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(200);
        }catch(PDOException $e){
            $error = array(
                "message" => "Internal Server error"
            );
            $response->getBody()->write(json_encode($error));
            return $response 
            ->withHeader("content-type","application/json")
            ->withStatus(500);
        }

    
    });

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');

        return $response;
    });
   
    
    


};



// require __DIR__ .'/../src/Application/Actions/Event/event.php';

// $app->get('/event/{event_id}', function (Request $request, Response $response) {

// })

// $app->group('/users', function (Group $group) {
//     $group->get('', ListUsersAction::class);
//     $group->get('/{id}', ViewUserAction::class);
// });
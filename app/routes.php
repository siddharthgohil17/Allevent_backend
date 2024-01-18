<?php

declare(strict_types=1);
require __DIR__ .'/../config/db.php';

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Middleware\CorsMiddleware; 

return function (App $app) {

    $app->add(new CorsMiddleware());

    //preflight cors request
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
       
        // $origin = $request->getHeaderLine('Origin');
        // error_log("Request Origin: $origin");
       

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
    
        return $response;
    });
    
    //this api for checking purpose
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('<h1> Hello date is ' . date('Y-m-d') . '</h1>');
        return $response;
    });
     
    $app->get('/get_all_events', function (Request $request, Response $response, array $args) {
        $queryParams = $request->getQueryParams();
        $event_name = isset($queryParams['event_name']) ? $queryParams['event_name'] : null;
        $time = isset($queryParams['time']) ? $queryParams['time'] : null;
        $country = isset($queryParams['country']) ? $queryParams['country'] : null;
        $state = isset($queryParams['state']) ? $queryParams['state'] : null;
        $city = isset($queryParams['city']) ? $queryParams['city'] : null;
        $category = isset($queryParams['category']) ? $queryParams['category'] : null;
        $pageNo = isset($queryParams['pageNo']) ? (int) $queryParams['pageNo'] : 1;
        $pageSize = 12; 
    
      
        $x = null;
        if ($time === 'today') {
            $x = date('Y-m-d');
        } elseif ($time === 'tomorrow') {
            $x = date('Y-m-d', strtotime('tomorrow'));
        } elseif ($time !== null) {
            $x = date('Y-m-d', strtotime($time));
        }
    
        // Build SQL query based on parameters
        $sql = "SELECT * FROM events WHERE 1";
    
        if ($x !== null) {
            $sql .= " AND (DATE(start_time) = '$x' OR DATE(end_time) = '$x')";
        }
    
        if ($country !== null) {
            $sql .= " AND country = '$country'";
        }
    
        if ($state !== null) {
            $sql .= " AND state = '$state'";
        }
    
        if ($city !== null) {
            $sql .= " AND city = '$city'";
        }
    
        if ($category !== null) {
            $sql .= " AND category = '$category'";
        }
    
        if ($event_name !== null) {
            $sql .= " AND event_name = '$event_name'";
        }   
        $offset = ($pageNo - 1) * $pageSize;
        $sql .= " LIMIT $pageSize OFFSET $offset";
    

        try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->query($sql);
            $event = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
            if (empty($event)) {
                $responseMessage = array(
                    "message" => "No events found for the specified parameters."
                );
                $response->getBody()->write(json_encode($responseMessage));
                return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(404);
            }
    
            $response->getBody()->write(json_encode($event));
    
            return $response
                ->withHeader("Content-Type", "application/json")
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
    
    //this api for filter event according user preference
    $app->get('/location', function (Request $request, Response $response , array $args) {
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
                ->withStatus(400);  
        }
         

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
    $app->get("/event_by_time", function (Request $request, Response $response, array $args) {    
        $queryParams = $request->getQueryParams();
        $time = isset($queryParams['time']) ? $queryParams['time'] : null;
        $x = null;
           
        
  
        if ($time === 'today') {
            $x = date('Y-m-d');
        } elseif ($time === 'tomorrow') {
            $x = date('Y-m-d', strtotime('tomorrow'));
        } else {
            $x = date('Y-m-d', strtotime($time));
        }

    
        if ($x === null) {
            // Handle the case where $x is still null (unexpected scenario)
            $responseMessage = array(
                "message" => "Unexpected error. Please try again."
            );
        
            $response->getBody()->write(json_encode($responseMessage));
        
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(500);
        }
    
        $sql = "SELECT * FROM events WHERE start_time='$x' OR end_time='$x'";
    
        try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->query($sql);
            $event = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
            if (empty($event)) {
                // Handle the case when there are no events
                $responseMessage = array(
                    "message" => "No events found for the specified time."
                );
                $response->getBody()->write(json_encode($responseMessage));
                return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(404); // Return 404 Not Found
            }
    
            $response->getBody()->write(json_encode($event));
    
            return $response 
                ->withHeader("Content-Type", "application/json")
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
    
    $app->get('/category/{category}', function (Request $request, Response $response, array $args) {
     
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

    //this api for add new event
    $app->post('/createEvent', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
    
        $requiredFields = ['event_name', 'category', 'city', 'state', 'country', 'start_time', 'end_time', 'description', 'organizer_id', 'thumb_picture'];
    
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $responseMessage = array(
                    "message" => "Required field '{$field}' is missing or empty."
                );
    
                $response->getBody()->write(json_encode($responseMessage));
    
                return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(400);
            }
        }
    
        // Extract data
        $event_name = $data['event_name'];
        $category = $data['category'];
        $city = $data['city'];
        $state = $data['state'];
        $country = $data['country'];
        $start_time = DateTime::createFromFormat('d/m/Y', $data['start_time'])->format('Y-m-d');
        $end_time = DateTime::createFromFormat('d/m/Y', $data['end_time'])->format('Y-m-d');
        $description = $data['description'];
        $organizer_id = $data['organizer_id'];
        $thumb_picture = $data['thumb_picture'];
    
      
        $sql = "INSERT INTO events (event_name, category, city, state, country, start_time, end_time, description, organizer_id, thumb_picture) 
                VALUES (:event_name, :category, :city, :state, :country, :start_time, :end_time, :description, :organizer_id, :thumb_picture)";
    
        try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->prepare($sql);
    
            $stmt->bindParam(':event_name', $event_name);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':state', $state);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->bindParam(':end_time', $end_time);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':organizer_id', $organizer_id);
            $stmt->bindParam(':thumb_picture', $thumb_picture);
    
            $stmt->execute();
    
            $responseMessage = array(
                "message" => "Event created successfully."
            );
    
            $response->getBody()->write(json_encode($responseMessage));
    
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(201); // 201 Created
        } catch (PDOException $e) {
            error_log('Error in /createEvent: ' . $e->getMessage());
            error_log('SQL Query: ' . $sql);
            $error = array(
                "message" => "Internal Server error"
            );
    
            $response->getBody()->write(json_encode($error));
    
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(500);
        }
    });
    
     
    //helping api
    $app->get("/collect_all_category", function (Request $request,Response $response){

        $sql="SELECT category from events group by category";

         try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->query($sql);
            $event = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
            if (empty($event)) {
                $responseMessage = array(
                    "message" => "No city found"
                );
                $response->getBody()->write(json_encode($responseMessage));
                return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(404);
            }
    
            $response->getBody()->write(json_encode($event));
    
            return $response
                ->withHeader("Content-Type", "application/json")
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
    $app->get("/collect_all_city", function (Request $request,Response $response){

        $sql="SELECT city from events group by city";

        try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->query($sql);
            $event = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
            if (empty($event)) {
                $responseMessage = array(
                    "message" => "No city found"
                );
                $response->getBody()->write(json_encode($responseMessage));
                return $response
                    ->withHeader("Content-Type", "application/json")
                    ->withStatus(404);
            }
    
            $response->getBody()->write(json_encode($event));
    
            return $response
                ->withHeader("Content-Type", "application/json")
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
   

    
    


};



// require __DIR__ .'/../src/Application/Actions/Event/event.php';

// $app->get('/event/{event_id}', function (Request $request, Response $response) {

// })

// $app->group('/users', function (Group $group) {
//     $group->get('', ListUsersAction::class);
//     $group->get('/{id}', ViewUserAction::class);
// });
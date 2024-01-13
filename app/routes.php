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

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
    
        // Get the "Origin" header from the request
        $origin = $request->getHeaderLine('Origin');
    
        error_log("Request Origin: $origin");
    
        // Set CORS headers
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    
        return $response;
    });
    

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
        $pageSize = 10; 
    
      
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
    
        // Calculate OFFSET based on page number and page size
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

    $app->post('/createEvent', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
    
        // Validate required parameters
        $requiredFields = ['event_name', 'category', 'city', 'state', 'country', 'start_time', 'end_time', 'description', 'organizer_id', 'event_banner', 'thumb_picture', 'weight'];
    
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
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        $description = $data['description'];
        $organizer_id = $data['organizer_id'];
        $event_banner = $data['event_banner'];
        $thumb_picture = $data['thumb_picture'];
        $weight = $data['weight'];
    
        // Insert into database
        $sql = "INSERT INTO events (event_name, category, city, state, country, start_time, end_time, description, organizer_id, event_banner, thumb_picture, weight) 
                VALUES (:event_name, :category, :city, :state, :country, :start_time, :end_time, :description, :organizer_id, :event_banner, :thumb_picture, :weight)";
    
        try {
            $db = new DB();
            $conn = $db->connect();
    
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':event_name', $event_name, PDO::PARAM_STR);
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->bindParam(':city', $city, PDO::PARAM_STR);
            $stmt->bindParam(':state', $state, PDO::PARAM_STR);
            $stmt->bindParam(':country', $country, PDO::PARAM_STR);
            $stmt->bindParam(':start_time', $start_time, PDO::PARAM_STR);
            $stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':organizer_id', $organizer_id, PDO::PARAM_STR);
            $stmt->bindParam(':event_banner', $event_banner, PDO::PARAM_STR);
            $stmt->bindParam(':thumb_picture', $thumb_picture, PDO::PARAM_STR);
            $stmt->bindParam(':weight', $weight, PDO::PARAM_INT);
    
            $stmt->execute();
    
            $responseMessage = array(
                "message" => "Event created successfully."
            );
    
            $response->getBody()->write(json_encode($responseMessage));
    
            return $response
                ->withHeader("Content-Type", "application/json")
                ->withStatus(201); // 201 Created
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
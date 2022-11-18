<?php

namespace Middlewares;

use Helpers\HttpRequest;

class AuthMiddleware {

public function __construct(HttpRequest $request)
{
    
    $restrictedRoutes = (array)$_ENV['config']->restricted;
    
    $params = $request->stringRequest;

    $this->id = isset($request->route[1]) ? $request->route[1] : null;
    
    $params = str_replace($this->id, ":id", $params);
    // str_replace (l'item à changer, la valeur qu'on veut injecter, l'output: avec la nouvelle valeur)
 

    if(isset($restrictedRoutes[$params])){
        $this->condition = $restrictedRoutes[$params];
    }
    
    $bp = true;

    foreach ($restrictedRoutes as $k => $v){
        // Pour chaque routes restricted .. 
        $restricted = str_replace(":id", $this->id, $k);
        
        if($restricted == $params){
            $this->condition = $v;
            break;
        }

        $bp = true;
    }

}

// public function verify(){
//     if(isset($this->condition)){
//         $headers = apache_request_headers();

//         if(isset($headers["Authorization"])){
//             $token = $headers["Authorization"];
//         }

//         if(isset($_COOKIE['blog'])){
//             $token = $_COOKIE['blog'];
//         }

//         $secretKey = $_ENV['config']->secret;
        
//         if(isset($token) && !empty($token)){
//             try{
//                 $payload = JWT::decode($token, new Key($secretKey, 'HS512'));
//             }catch(Exception $e){
//                 $payload = null;
//             }
//             if (isset($payload) &&
//                 $payload->iss === "blog.api" &&
//                 $payload->nbf < time() &&
//                 $payload->exp > time())
//             {
//                 $userRole = $payload->userRole;
//                 $userId = $payload->userId;
//                 $id = $this->id;
//                 $test = false;
//                 eval("\$test=".$this->condition);
//                 if($test){
//                     return true;
//                 }
//             }
//         }

//         header('HTTP/1.0 401 Unauthorized');
//         die;
//     }

//     return true;
// }

}
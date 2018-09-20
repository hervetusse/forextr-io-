<?php


use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App([
    'settings'=>[
        'displayErrorDetails'=>true
    ]
]);

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "secure"=>false,
    "users" => [
        "shield" => "123456",
    ]
]));


/* 
    endpoint: fetchCurrencyRate
    parameters: currency, symbol
    method: POST
*/
$app->post('/fetchCurrencyRate', function(Request $request, Response $response){

    $request_data = $request->getParsedBody(); 
    $json = json_decode($request_data);
    $text = $json->result->resolvedQuery;


    $responseText = prepareResponse($text);
    
    $response = new \stdClass();
    $response->speech = $responseText;
    $response->displayText = $responseText;
    $response->source = "webhook";
    return json_encode($text);

    if(!haveEmptyParameters(array('currency', 'symbol'), $request, $response)){

        $symbol = $request_data['symbol'];
        $currency = $request_data['currency'];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://ratesapi.io/api/latest?base=$currency&symbols=$symbol");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
            curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 2);
            $data = curl_exec($ch);
            if(curl_errno($ch)){
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);

            $response->write($data);

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(201);
          } catch(Exception $e) {
            // do something on exception
          }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);    
});



function haveEmptyParameters($required_params, $request, $response){
    $error = false; 
    $error_params = '';
    $request_params = $request->getParsedBody(); 

    foreach($required_params as $param){
        if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
            $error = true; 
            $error_params .= $param . ', ';
        }
    }

    if($error){
        $error_detail = array();
        $error_detail['error'] = true; 
        $error_detail['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
        $response->write(json_encode($error_detail));
    }
    return $error; 
}

function prepareResponse($text){
    return "You said: " . $text ;
}

$app->run();





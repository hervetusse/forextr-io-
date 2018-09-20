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

    $request_data = file_get_contents('php://input');
    $json = json_decode($request_data);
    $text = $json->result->resolvedQuery;

    $amt  = (!empty($json->result->parameters->amount)) ? $json->result->parameters->amount : '';
    $currency = (!empty($json->result->parameters->currencyfrom)) ? $json->result->parameters->currencyfrom : '';

    $responseText = prepareResponse($text, $currency, $amt);
    
    $response = new \stdClass();
    $response->speech = $responseText;
    $response->displayText = $responseText;
    $response->source = "webhook";
    return json_encode($response);
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

function prepareResponse($text, $currency, $amt){

    $symbol = $currency;
    $amt = $amt;
    $data = '';

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ratesapi.io/api/latest?base=$currency&symbols=ZAR");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 2);
        $data = curl_exec($ch);
        if(curl_errno($ch)){
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        } catch(Exception $e) {
        // do something on exception
        }

        $rate = json_decode($data);
        $rate = $rate->rates->ZAR;
        $rate_amt = (!empty($amt)) ? $amt * $rate : $rate;

    return "You said: " . $text . ' Amount: ' . $amt . ' Currency: ' . $symbol . ' The rate is: ' . number_format((float)$rate_amt, 2, '.', '') . ' rand';
}




$app->run();





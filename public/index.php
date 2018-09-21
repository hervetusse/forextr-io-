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

    return ' The rate is: ' . number_format((float)$rate_amt, 2, '.', '') . ' rand';
}




$app->run();





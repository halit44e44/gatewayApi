<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return "EPINPAY";
});


//Authorization
$router->group(['prefix' => 'epinpay/v1/'], function () use ($router) {
    $router->post('getToken', 'TokenController@getToken');
    $router->post('getAllData', 'GatewayController@getAllData');
    $router->post('paymentCheck', 'PaymentCheckController@paymentCheck');
    $router->post('control', 'ControlController@statusControl');
    $router->post('isbank', 'IsbankController@isbank');


    $router->post('isbank/isbankResult', 'IsbankController@isbankResult');
    $router->post('teqpayResult', 'TeqpayController@teqpayResult');
});



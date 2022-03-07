<?php

namespace App\Helpers;

use App\Http\Controllers\GatewayController;
use App\Models\Customers;
use App\Models\PayTransaction;
use App\Models\Result;
use App\Models\Teqpay;
use Exception;

class TeqpayFunctionsHelper
{
    public static function json($data)
    {
        header('Content-Type: application/json');
        echo $data;
        exit();
    }


    public static function result($request)
    {
        try {
            switch ($request->paymentMethodId) {
                case 43:
                    $paymentMetod = 42;
                    break;
                case 44:
                    $paymentMetod = 1;
                    break;
                case 45:
                    $paymentMetod = 27;
                    break;
                case 46:
                    $paymentMetod = 25;
                    break;
                case 47:
                    $paymentMetod = 39;
                    break;
                case 48:
                    $paymentMetod = 31;
                    break;
                case 49:
                    $paymentMetod = 44;
                    break;
                default:
                    $paymentMetod = "HATA";
            }

            $header = array(
                "ApiKey: " . env('TEQPAY_APIKEY') . "",
                "SecretKey: " . env('TEQPAY_SECRETKEY') . "",
                "Content-Type: application/json"
            );

            $body = '{
            "customerName": "' . $request->customerName . '",    
            "customerCitizenNo": "' . $request->customerCitizenNo . '",
            "customerEmail": "' . $request->customerEmail . '",
            "customerPhone": "' . $request->customerPhone . '",
            "customerIpAddress": "' . $request->customerIpAddress . '",
            "price": ' . number_format($request->price, 2) . ',
            "conversationId": "' . $request->conversationId . '",
            "callbackUrl": "' . env('TEQPAY_CALLBACKURL') . '",
            "language": "' . $request->language . '",
            "paymentMetodId": "' . $paymentMetod . '",
            "installment": ' . json_encode($request->installment, true) . ',
            "products": ' . json_encode($request->products, true) . ',
            "billing": {
                "billingName": "Epin Ödeme ve İletişim Teknolojileri A.Ş",
                "billingCity": "İstanbul",
                "billingCountry": "Türkiye",
                "billingAddress": "Yakuplu Mah. Hürriyet Blv. No:1 Kapı:224 Beylikdüzü/İstanbul"
            },
            "shipping": {
                "shippingContactName": "Epin Ödeme ve İletişim Teknolojileri A.Ş",
                "shippingCity": "İstanbul",
                "shippingCountry": "Türkiye",
                "shippingAddress": "Yakuplu Mah. Hürriyet Blv. No:1 Kapı:224 Beylikdüzü/İstanbul"
            }
            }';
            $response = DefaultFuctionsHelper::requestCURL(env('TEQPAY_URL'), $header, $body);
            $result = json_decode($response, true);

            if (!empty($result['ResultCode']) && $result['ResultCode'] == 10000) {
                GatewayController::saveDb($request);
                $data = ['result' => true, 'resultCode' => 200, 'message' => 'İşlem Başarılı', 'URL' => $result['PaymentData']['PaymentUrl']];
                DefaultFuctionsHelper::json(json_encode($data));
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Banka ile bağlantı kurulamadı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function check($request)
    {
        try {
            $result = Result::where('conversationId', $request->conversationId)->first();
            if ($result) {
                $orderId = $result->orderId;
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'İşlem Başarısız'];
                DefaultFuctionsHelper::json(json_encode($data));
            }

            $header = array(
                "ApiKey: " . env('TEQPAY_APIKEY') . "",
                "SecretKey: " . env('TEQPAY_SECRETKEY') . "",
                "Content-Type: application/json"
            );

            $body = '{
            "OrderId": ' . $orderId . ',    
            "Language": "' . $request->language . '",
            }';

            $response = DefaultFuctionsHelper::requestCURL(env('TEQPAY_IPN_URL'), $header, $body);
            $result = json_decode($response, true);

            $customer = Customers::where('eMail', $result['TransactionList']['CustomerEmail'])->first();
            if (!empty($result['ResultCode']) && $result['ResultCode'] == 10000) {
                TeqpayFunctionsHelper::saveTeqpay($result);
                $data = [
                    'result' => true,
                    'resultCode' => 200,
                    'message' => 'İşlem Başarılı',
                    'transactionList' =>
                    [
                        'conversationId' => $result['TransactionList']['ConversationId'],
                        'totalAmount' => $result['TransactionList']['TotalAmount'],
                        'customerId' => $customer->customerId,
                        'customerName' => $result['TransactionList']['CustomerName'],
                        'customerEmail' => $result['TransactionList']['CustomerEmail'],
                        'customerPhone' => $result['TransactionList']['CustomerPhone'],
                        'transactionDateTime' => $result['TransactionList']['TransactionDate'] . ' ' . $result['TransactionList']['TransactionTime']
                    ]
                ];
                DefaultFuctionsHelper::json(json_encode($data));
            } else {
                TeqpayFunctionsHelper::saveTeqpay($result);
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Banka ile bağlantı kurulamadı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    private static function saveTeqpay($results)
    {
        try {
            $payTransaction = PayTransaction::where('conversationId', $results['TransactionList']['ConversationId'])->first();
            $result = Result::where('orderId', $results['TransactionList']['OrderId'])->first();

            $pay = PayTransaction::find($payTransaction->id);
            if ($payTransaction->status == "1") {
                $pay->description = "Onaylandı ve Teyit edildi.";
                $pay->status = "11";
                $pay->result_id = $result->id;
                $pay->save();
            } elseif ($payTransaction->status == "3") {
                $pay->description = "Operasyon Onayı ve Teyit";
                $pay->result_id = $result->id;
                $pay->save();
            }

            $teqpay = Teqpay::where('conversationId', $results['TransactionList']['ConversationId'])->first();

            if ($teqpay == null) {
                $customer = Customers::where('eMail', $results['TransactionList']['CustomerEmail'])->first();
                $teqpay = new Teqpay();
                $teqpay->customer_id = $customer->id;
                $teqpay->conversationId = $results['TransactionList']['ConversationId'];
                $teqpay->resultCode = $results['ResultCode'];
                $teqpay->resultMessage = $results['ResultMessage'];
                $teqpay->merchant = $results['TransactionList']['Merchant'];
                $teqpay->orderId = $results['TransactionList']['OrderId'];
                $teqpay->totalAmount = $results['TransactionList']['TotalAmount'];
                $teqpay->transactionDateTime = $results['TransactionList']['TransactionDate'] . ' ' . $results['TransactionList']['TransactionTime'];
                $teqpay->paymentDetail = json_encode($results['TransactionList']['PaymentDetail']);
                $teqpay->productDetail = json_encode($results['TransactionList']['ProductDetail']);
                $teqpay->save();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

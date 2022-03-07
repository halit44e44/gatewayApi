<?php

namespace App\Helpers;

use App\Http\Controllers\GatewayController;
use App\Models\Customers;
use App\Models\PayTransaction;
use App\Models\Result;
use Exception;

class IsbankFuctionsHelper
{

    public static function result($request)
    {
        try {
            GatewayController::saveDb($request);
            $clientId = env('ISBANK_CLIENTID');
            $amount = $request->price;
            $oid = $request->conversationId;
            $okUrl = env('ISBANK_RESULT');
            $failUrl = env('ISBANK_RESULT');
            $rnd = microtime();;
            $taksit = "0";
            $islemtipi = "Auth";
            $storekey = env('ISBANK_STOREKEY');
            $hashstr = $clientId . $oid . $amount . $okUrl . $failUrl . $islemtipi . $taksit . $rnd . $storekey;
            $hash = base64_encode(pack('H*', sha1($hashstr)));


            echo '<form method="post" id="forClick" action=' . env('ISBANK_URL') . '>
                <input type="hidden" name="clientid" value="' . $clientId . '">
                <input type="hidden" name="amount" value="' . $amount . '">

                <input type="hidden" name="oid" value="' . $oid . '">	
                <input type="hidden" name="okUrl" value="' . $okUrl . '">
                <input type="hidden" name="failUrl" value="' . $failUrl . '">
                <input type="hidden" name="islemtipi" value="' . $islemtipi . '">
                <input type="hidden" name="taksit" value="' . $taksit . '">
                <input type="hidden" name="rnd" value="' . $rnd . '">
                <input type="hidden" name="hash" value="' . $hash . '">
				<input type="hidden" name="currency" value="949">
                <input type="hidden" name="storetype" value="3d_pay_hosting" >
	
                <input type="hidden" name="refreshtime" value="0" >
		
                <input type="hidden" name="lang" value="tr">
				<input type="hidden" name="firmaadi" value="Epin Ödeme Sistemleri A.Ş">
				<input type="hidden" name="Fismi" value="Epin Ödeme Sistemleri A.Ş">
                <input type="hidden" name="faturaFirma" value="Epin Ödeme Sistemleri A.Ş">
                <input type="hidden" name="Fadres" value="İstanbul Beylikdüzü">
                <input type="hidden" name="Fadres2" value="İstanbul Beylikdüzü">
                <input type="hidden" name="Fil" value="İstanbul Beylikdüzü">
                
                <script>
                    document.getElementById("forClick").submit();
                </script></form>
                ';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function check($request)
    {
        try {
            if (!empty($request)) {
                $header = array(
                    'Content-Type: text/xml',
                    'Cookie: Apache=170.84.253.26.1632295557592472'
                );

                $body = '<?xml version="1.0" encoding="ISO-8859-9"?>
                        <CC5Request>
                            <Name>' . env('ISBANK_NAME') . '</Name>
                            <Password>' . env('ISBANK_PASSWORD') . '</Password>
                            <ClientId>' . env('ISBANK_CLIENTID') . '</ClientId>
                            <OrderId>' . $request->conversationId . '</OrderId>
                            <Extra>
                                <ORDERSTATUS>QUERY</ORDERSTATUS>
                            </Extra>
                        </CC5Request>';

                $response = DefaultFuctionsHelper::requestCURL(env('ISBANK_IPN_URL'), $header, $body);

                $xml = simplexml_load_string($response);
                $response = $xml->Response;
                $procReturnCode = $xml->ProcReturnCode;

                if ($response == "Approved" && $procReturnCode == "00") {

                    $payTransaction = PayTransaction::where('conversationId', $request->conversationId)->first();
                    $result = Result::where('conversationId',  $request->conversationId)->first();
                    if (!empty($result)) {
                        $customer = Customers::where('id', $payTransaction->customer_id)->first();
                        if ($payTransaction->status == "1") {
                            $payTransaction->description = "Onaylandı ve Teyit edildi.";
                            $payTransaction->status = "11";
                            $payTransaction->result_id = $result->id;
                        } elseif ($payTransaction->status == "3") {
                            $payTransaction->description = "Operasyon Onayı ve Teyit";
                            $payTransaction->result_id = $result->id;
                        }
                        $payTransaction->save();

                        $data = [
                            'result' => true,
                            'resultCode' => 200,
                            'message' => 'İşlem Başarılı',
                            'transactionList' =>
                            [
                                'conversationId' => $result->conversationId,
                                'totalAmount' => $payTransaction->price,
                                'customerId' => $customer->customerId,
                                'customerName' => $customer->fullName,
                                'customerEmail' => $customer->eMail,
                                'customerPhone' => $customer->phone,
                                'transactionDateTime' => $payTransaction->created_at
                            ]
                        ];
                        DefaultFuctionsHelper::json(json_encode($data));
                    } else {
                        $data = ['result' => false, 'resultCode' => 404, 'message' => 'Banka ile bağlantı kurulamadı'];
                        DefaultFuctionsHelper::json(json_encode($data));
                    }
                } else {
                    $data = ['result' => false, 'resultCode' => 404, 'message' => 'Bankadan ücret onayı gelmedi.'];
                    DefaultFuctionsHelper::json(json_encode($data));
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

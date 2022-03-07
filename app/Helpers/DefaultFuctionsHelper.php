<?php

namespace App\Helpers;

use App\Models\Companies;
use App\Models\Flag;
use App\Models\PayTransaction;
use Exception;

class DefaultFuctionsHelper
{
    public static function json($data)
    {
        header('Content-Type: application/json');
        echo $data;
        exit();
    }

    public static function requestCURL($url = "", $header = array(), $body = "")
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function location($resultId, $conversationId, $message, $status, $urlMessage)
    {
        try {
            $payTransaction = PayTransaction::where('conversationId', $conversationId)->first();
            $company = Companies::where('id', $payTransaction->company_id)->first();
            $payTransaction->description = $message;
            if ($resultId != null) {
                $payTransaction->result_id = $resultId;
            }
            $payTransaction->status = $status;
            $payTransaction->save();

            $body = array(
                'message' => $urlMessage,
                'conversationId' => $conversationId
            );

            $response = DefaultFuctionsHelper::requestCURL($company->ipn, array(), $body);
            $flag = Flag::find(1);
            $flag->value = "1";
            $flag->save();
            header('Location:' . $response);
            exit();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

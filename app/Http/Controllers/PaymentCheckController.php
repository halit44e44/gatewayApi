<?php

namespace App\Http\Controllers;

use App\Helpers\TeqpayFunctionsHelper;
use App\Helpers\DefaultFuctionsHelper;
use App\Helpers\IsbankFuctionsHelper;
use App\Models\Banks;
use App\Models\Companies;
use App\Models\PayTransaction;
use Illuminate\Http\Request;

class PaymentCheckController extends Controller
{

    private function validates($request)
    {
        $this->validate($request, [
            'institutionCode' => 'required',
            'conversationId' => 'required',
            'language' => 'required'
        ]);
        $company = Companies::where('institutionCode', $request->institutionCode)->where('status', "1")->first();
        if ($company) {
            if ($request->header('apikey') != $company->apiKey) {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Api Key Hatalıdır'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
            if ($request->header('secretkey') != $company->secretKey) {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Secret Key Hatalıdır'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'Kurum bulunamadı'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
    }

    public function paymentCheck(Request $request)
    {
        if (!is_null($request)) {
            $payTransaction = PayTransaction::where('conversationId', $request->conversationId)->first();
            $this->validates($request);
            if (!empty($payTransaction)) {
                if ($payTransaction->status != "2") {

                    $conversation = PayTransaction::where('conversationId', $request->conversationId)->first();
                    if ($conversation) {
                        $bank = Banks::where('id', $conversation->bank_id)->first();
                    }
                    switch ($bank->paymentMethodId) {
                        case 43:
                            $paymentMetod = "Teqpay";
                            break;
                        case 44:
                            $paymentMetod = "Teqpay";
                            break;
                        case 45:
                            $paymentMetod = "Teqpay";
                            break;
                        case 46:
                            $paymentMetod = "Teqpay";
                            break;
                        case 47:
                            $paymentMetod = "Teqpay";
                            break;
                        case 48:
                            $paymentMetod = "Teqpay";
                            break;
                        case 49:
                            $paymentMetod = "Teqpay";
                            break;
                        case 51:
                            $paymentMetod = "Isbank";
                            break;
                        default:
                            $paymentMetod = "HATA";
                    }
                    
                    if ($paymentMetod == "Teqpay") {
                        return TeqpayFunctionsHelper::check($request);
                    } else if ($paymentMetod == "Isbank") {
                        return IsbankFuctionsHelper::check($request);
                    } else {
                        $data = ['result' => false, 'resultCode' => 404, 'message' => 'conversationId bilgisi hatalı.'];
                        DefaultFuctionsHelper::json(json_encode($data));
                    }
                } else {
                    $data = ['result' => false, 'resultCode' => 301, 'message' => 'İşlem Onay Bekliyor.'];
                    DefaultFuctionsHelper::json(json_encode($data));
                }
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'conversationId hatalı.'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'JSON hatası'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
    }
}

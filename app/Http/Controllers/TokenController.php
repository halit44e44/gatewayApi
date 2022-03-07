<?php

namespace App\Http\Controllers;

use App\Helpers\TeqpayFunctionsHelper;
use App\Helpers\DefaultFuctionsHelper;
use App\Models\Banks;
use App\Models\Companies;
use App\Models\Token;
use Exception;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;

class TokenController extends Controller
{
    private function validates($request)
    {
        $this->validate($request, [
            'paymentMethodId' => 'required:numeric',
            'institutionCode' => 'required'
        ]);


        try {
            $institutionCode = Companies::where('institutionCode', $request->institutionCode)->first();
            if (empty($institutionCode)) {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Böyle bir şirket bulunamadı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
            $paymentMethod = Banks::where('paymentMethodId', $request->paymentMethodId)->first();
            if (empty($paymentMethod)) {
                $data = ['result' => false, 'ResultCode' => 404, 'message' => 'Böyle bir ödeme yöntemi bulunamadı.'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
            $company = Companies::where('institutionCode', $request->institutionCode)->where('status', "1")->first();
            if (!empty($company) && $request->header('apikey') != $company->apiKey) {
                $data = ['result' => false, 'ResultCode' => 404, 'message' => 'Api Key Hatalıdır'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
            if (!empty($company) && $request->header('secretkey') != $company->secretKey) {
                $data = ['result' => false, 'ResultCode' => 404, 'message' => 'Secret Key Hatalıdır'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getToken(Request $request)
    {
        if (!is_null($request)) {
            $this->validates($request);

            $company = Companies::where('institutionCode', $request->institutionCode)
                ->where('apiKey', $request->header('apikey'))
                ->where('secretKey', $request->header('secretKey'))->first();
            if (!empty($company)) {
                $payload = [
                    'insCode'   => $request->institutionCode,
                    'payMetId'    => $request->paymentMethodId,
                    'iat'       => time(),
                    'exp'       => time() + 60 * 60
                ];
                $jwt =  JWT::encode($payload, env('JWT_LOCAL_SECRET'), env('JWT_HASH'));

                $conversationId = $company->name . time() . $request->institutionCode . rand(1, 333);
                $token = Token::where('conversationId', $conversationId)
                    ->where('apiKey', $request->header('apikey'))->first();


                if (empty($token)) {
                    $data = ['token' => $jwt, 'conversationId' => $conversationId, 'result' => true, 'resultCode' => 200, 'message' => 'Token oluşturuldu.'];
                    TokenController::saveDb($request, $jwt, $conversationId);
                    TeqpayFunctionsHelper::json(json_encode($data));
                } else {
                    $data = ['result' => false, 'resultCode' => 303, 'message' => '"conversationId" ait bir token zaten mevcut'];
                    DefaultFuctionsHelper::json(json_encode($data));
                }
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Şirket Bulunamadı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        }
    }

    private static function saveDb($request, $jwt, $conversationId)
    {
        $token = new Token();
        $token->token = $jwt;
        $token->conversationId = $conversationId;
        $token->apiKey = $request->header('apikey');
        $token->secretKey = $request->header('secretkey');
        $token->save();
    }
}

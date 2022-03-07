<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use App\Models\PayTransaction;
use Illuminate\Http\Request;
use App\Helpers\DefaultFuctionsHelper;
use App\Models\CompanyPaymentMethod;
use App\Models\Result;

class TeqpayController extends Controller
{
    public function teqpayResult(Request $request)
    {
        if (!empty($request)) {
            $payTransaction = PayTransaction::where('conversationId', $request->ConversationId)->first();
            $company = Companies::where('id', $payTransaction->company_id)->first();
            $companyPaymentMethod = CompanyPaymentMethod::where('company_id', $company->id)->where('bank_id', $payTransaction->bank_id)->first();
            $results = Result::where('conversationId', $request->ConversationId)->first();
            if ($results == null) {
                $results = new Result();
                $results->conversationId = $request->ConversationId;
                $results->orderId = $request->OrderId;
                $results->result = $request->Result;
                $results->resultCode = $request->ResultCode;
                $results->ResultMessage = $request->ResultMessage;
                $results->token = $request->Token;
                $results->save();
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Bu İşlem önceden başarılı bir şekilde kayıt edilmiştir.'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
            $resultQuery = Result::where('conversationId', $request->ConversationId)->first();

            if ($companyPaymentMethod->controlValue > $payTransaction->price) {
                if (!empty($request)) {

                    if (!empty($request['ResultCode']) && $request['ResultCode'] == 10000) {
                        DefaultFuctionsHelper::location($resultQuery->id, $request->ConversationId, "Onaylandı", "1", "success");
                    } else {
                        DefaultFuctionsHelper::location(0, $request->ConversationId, "Başarısız' . $request->ErrMsg . '", "5", "failture");
                    }
                }
            } else {
                if (!empty($request['ResultCode']) && $request['ResultCode'] == 10000) {
                    DefaultFuctionsHelper::location($resultQuery->id, $request->ConversationId, "Onay Bekleniyor", "2", "wait");
                } else {
                    DefaultFuctionsHelper::location(0, $request->ConversationId, "Başarısız' . $request->ErrMsg . '", "5", "failture");
                }
            }
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'Banka ile iletişim kurulamadı.'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
    }
}

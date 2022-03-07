<?php

namespace App\Http\Controllers;

use App\Helpers\DefaultFuctionsHelper;
use App\Models\Companies;
use App\Models\CompanyPaymentMethod;
use App\Models\Isbank;
use App\Models\PayTransaction;
use App\Models\Result;
use Illuminate\Http\Request;

class IsbankController extends Controller
{
    public function isbankResult(Request $request)
    {
        if (!empty($request)) {
            $payTransaction = PayTransaction::where('conversationId', $request->ReturnOid)->first();
            $company = Companies::where('id', $payTransaction->company_id)->first();
            $companyPaymentMethod = CompanyPaymentMethod::where('company_id', $company->id)->where('bank_id', $payTransaction->bank_id)->first();
            $results = Result::where('conversationId', $request->ReturnOid)->first();
            $isbank = Isbank::where('conversationId', $request->ReturnOid)->first();
            $paymentDetail = ['PaymentType' => $request->EXTRA_CARDBRAND, 'PaymentMethod' => "İş Bankası", 'AccountNo' => $request->maskedCreditCard, 'ValorDate' => $request->EXTRA_TRXDATE, 'PaymentStatus' => $request->Response];

            if (!empty($request->ProcReturnCode) && $request->ProcReturnCode == "00" && $request->Response == "Approved") {
                if ($results == null) {
                    $results = new Result();
                    $results->conversationId = $request->ReturnOid;
                    $results->orderId = $request->TransId;
                    $results->result = $request->callbackCall;
                    $results->resultCode = $request->ProcReturnCode;
                    $results->ResultMessage = $request->Response;
                    $results->token = $request->querycampainghash;
                    $results->save();
                } else {
                    $data = ['result' => false, 'resultCode' => 404, 'message' => 'Bu İşlem önceden başarılı bir şekilde kayıt edilmiştir.'];
                    DefaultFuctionsHelper::json(json_encode($data));
                }
                $paymentDetail = ['PaymentType' => $request->EXTRA_CARDBRAND, 'PaymentMethod' => "Is Bankasi", 'AccountNo' => $request->maskedCreditCard, 'ValorDate' => $request->EXTRA_TRXDATE, 'PaymentStatus' => $request->Response];
                if ($isbank == null) {
                    $isbank = new Isbank();
                    $isbank->customer_id = $payTransaction->customer_id;
                    $isbank->conversationId = $request->ReturnOid;
                    $isbank->resultCode = $request->ProcReturnCode;
                    $isbank->resultMessage = $request->Response;
                    $isbank->merchant = $request->Fismi;
                    $isbank->orderId = $request->TransId;
                    $isbank->totalAmount = $request->amount;
                    $isbank->transactionDateTime = $request->EXTRA_TRXDATE;
                    $isbank->paymentDetail = json_encode($paymentDetail, true);
                    $isbank->productDetail = "";
                    $isbank->save();
                }

                $resultQuery = Result::where('conversationId', $request->ReturnOid)->first();
                if ($companyPaymentMethod->controlValue > $payTransaction->price) {
                    if (!empty($request->ProcReturnCode) && $request->ProcReturnCode == "00" && $request->Response == "Approved") {
                        DefaultFuctionsHelper::location($resultQuery->id, $request->ReturnOid, "Onaylandı", "1", "success");
                    } else {
                        DefaultFuctionsHelper::location(NULL, $request->ReturnOid, "Başarısız" . $request->ErrMsg . "", "5", "failture");
                    }
                } else {
                    if (!empty($request->ProcReturnCode) && $request->ProcReturnCode == "00" && $request->Response == "Approved") {
                        DefaultFuctionsHelper::location($resultQuery->id, $request->ReturnOid, "Onay Bekleniyor", "2", "wait");
                    } else {
                        DefaultFuctionsHelper::location(NULL, $request->ReturnOid, "Başarısız", "5", "failture");
                    }
                }
            } else {
                DefaultFuctionsHelper::location("", $request->ReturnOid, "Başarısız " . $request->ErrMsg . "", "5", "failture");
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Ödeme Başarısız'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'Banka ile iletişim kurulamadı.'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
    }
}

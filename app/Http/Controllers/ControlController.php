<?php

namespace App\Http\Controllers;

use App\Helpers\DefaultFuctionsHelper;
use App\Models\Companies;
use App\Models\PayTransaction;
use App\Models\Result;
use Illuminate\Http\Request;

class ControlController extends Controller
{

    private function validates($request)
    {
        $this->validate($request, [
            'conversationId' => 'required',
            'value' => 'required|numeric|integer'
        ]);
    }

    
     public function statusControl(Request $request)
    {
        if (!is_null($request)) {
            $this->validates($request);
            $company = Companies::where('id', $request->companyId)->first();

            $payTransaction = PayTransaction::where('conversationId', $request->conversationId)->where('status', '2')->first();
            $resultQuery = Result::where('conversationId', $request->conversationId)->first();

            if (!empty($payTransaction)) {
                if ($request->value == "1") {
                    DefaultFuctionsHelper::location($resultQuery->id, $request->conversationId, "Operasyon Onayı", "3", "success");
                } else {
                    DefaultFuctionsHelper::location($resultQuery->id, $request->conversationId, "Operasyon Red", "4", "failture");
                }
                $data = ['result' => true, 'resultCode' => 200, 'message' => 'İşlem Başarılı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'JSON hatalı'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
    }

/*
    public function statusControl(Request $request)
    {

        if (!is_null($request)) {
            $this->validates($request);
            $company = Companies::where('id', $request->companyId)->first();

            $payTransaction = PayTransaction::where('conversationId', $request->conversationId)->where('status', '2')->first();
            if (!empty($payTransaction)) {
                if ($request->value == "1") {
                    $payTransaction->status = "3";
                    $payTransaction->description = "Operasyon Onayı";
                    $payTransaction->save();
                    header('Content-Type: application/json');
                    header("Location:" . $company->ipn . "?message=success&conversationId=" . $request->conversationId . "");
                    $flag = Flag::find(1);
                    $flag->value = "1";
                    $flag->save();
                    exit();
                } else {
                    $payTransaction->status = "4";
                    $payTransaction->description = "Operasyon Red";
                    $payTransaction->save();
                    header('Content-Type: application/json');
                    header("Location:" . $company->ipn . "?message=failture&conversationId=" . $request->conversationId . "");
                    $flag = Flag::find(1);
                    $flag->value = "1";
                    $flag->save();
                    exit();
                }
                $data = ['result' => true, 'resultCode' => 200, 'message' => 'İşlem Başarılı'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } else {
            return "Post gelmedi";
        }
    }
    */
    
}

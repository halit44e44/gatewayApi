<?php

namespace App\Http\Controllers;

use App\Helpers\TeqpayFunctionsHelper;
use App\Helpers\DefaultFuctionsHelper;
use App\Helpers\IsbankFuctionsHelper;
use App\Models\Banks;
use App\Models\Companies;
use App\Models\Currencies;
use App\Models\Customers;
use App\Models\Flag;
use App\Models\PayTransaction;
use App\Models\Product;
use App\Models\Token;
use Exception;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    private function validates($request)
    {
        $this->validate($request, [
            'customerId' => 'required|numeric|integer',
            'customerName' => 'required',
            'customerCitizenNo' => 'required|max:11|min:11',
            'customerEmail' => 'required|email',
            'customerPhone' => 'required',
            'customerIpAddress' => 'required',
            'price' => 'required|numeric',
            'institutionCode' => 'required',
            'conversationId' => 'required',
            'paymentMethodId' => 'required|numeric',
            'callBackUrl' => 'required',
            'products' => 'required|array',
            'products.*.merchantItemId' => 'required',
            'products.*.itemType' => 'required',
            'products.*.itemCategory' => 'required',
            'products.*.itemName' => 'required',
            'products.*.itemQuantity' => 'required|numeric|integer',
            'products.*.itemPrice' => 'required|numeric',
        ]);
        $company = Companies::where('institutionCode', $request->institutionCode)->where('status', "1")->first();
        if (!empty($company) && $request->header('apikey') != $company->apiKey) {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'Api Key Hatalıdır'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
        try {
            $token = Token::where('token', $request->bearerToken())->where('conversationId', $request->conversationId)->first();
            if ($token) {
                if ($company) {
                    $conversationId = PayTransaction::where('conversationId', $request->conversationId)->first();
                    if (!empty($conversationId) && $conversationId->conversationId == $request->conversationId) {
                        $data = ['result' => false, 'resultCode' => 333, 'message' => 'Konuşma kimliği hatası.'];
                        DefaultFuctionsHelper::json(json_encode($data));
                    }
                    if ($request->header('secretkey') != $company->secretKey) {
                        $data = ['result' => false, 'resultCode' => 404, 'message' => 'Secret Key Hatalıdır'];
                        DefaultFuctionsHelper::json(json_encode($data));
                    }
                } else {
                    $data = ['result' => false, 'resultCode' => 404, 'message' => 'Kurum Kodunuz Hatalıdır.'];
                    DefaultFuctionsHelper::json(json_encode($data));
                }
            } else {
                $data = ['result' => false, 'resultCode' => 404, 'message' => 'Token veya conversationId bulunamadı.'];
                DefaultFuctionsHelper::json(json_encode($data));
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllData(Request $request)
    {
        if (!is_null($request)) {
            $this->validates($request);
            $bank = Banks::where('paymentMethodId', $request->paymentMethodId)->where('status', "1")->first();

            if ($bank->name == "Teqpay") {
                return TeqpayFunctionsHelper::result($request);
            } else if ($bank->name == "Isbank") {
                return IsbankFuctionsHelper::result($request);
            } else {
                echo "Hata => \"paymentMethod\" bilgisi eksik.";
                exit();
            }
        }
    }

    public static function saveDb($request)
    {
        $token = Token::where('token',  $request->bearerToken())->first();
        $bank = Banks::where('paymentMethodId', $request->paymentMethodId)->first();
        $company = Companies::where('institutionCode', $request->institutionCode)->first();
        $currency = Currencies::where('name', "TL")->first(); // Bu Kısım Uluslar arası olduğumuzda açılacak
        $customers = Customers::where('customerId', $request->customerId)->first();

        if ($bank) {
            if (!$customers) {
                $customer = new Customers();
                $customer->customerId = $request->customerId;
                $customer->fullName = $request->customerName;
                $customer->tcNo = $request->customerCitizenNo;
                $customer->eMail = $request->customerEmail;
                $customer->phone = $request->customerPhone;
                $customer->language = $request->language;
                $customer->ip = $request->customerIpAddress;
                $customer->save();
            } else {
                $customer = Customers::find($customers->id);
                $customer->customerId = $request->customerId;
                $customer->fullName = $request->customerName;
                $customer->tcNo = $request->customerCitizenNo;
                $customer->eMail = $request->customerEmail;
                $customer->phone = $request->customerPhone;
                $customer->language = $request->language;
                $customer->ip = $request->customerIpAddress;
                $customer->save();
            }


            for ($i = 0; $i < count($request->products); $i++) {
                $products = new Product();
                $products->conversationId = $request->conversationId;
                $products->merchantItemId = $request->products[$i]['merchantItemId'];
                $products->itemType = $request->products[$i]['itemType'];
                $products->itemCategory = $request->products[$i]['itemCategory'];
                $products->itemName = $request->products[$i]['itemName'];
                $products->itemQuantity = $request->products[$i]['itemQuantity'];
                $products->itemPrice = $request->products[$i]['itemPrice'];
                $products->save();
            }
            $customers = Customers::where('customerId', $request->customerId)->first();
            $product = Product::where('conversationId', $request->conversationId)->get();
            $pay = new PayTransaction();
            $productArr = [];
            for ($i = 0; $i < count($product); $i++) {
                $productArr[$i] = $product[$i]->id;
            }
            $pay->token_id = $token->id;
            $pay->bank_id = $bank->id;
            $pay->company_id = $company->id;
            $pay->currenicy_id = $currency->id;
            $pay->customer_id = $customers->id;
            $pay->product_id = json_encode($productArr, true);
            $pay->conversationId = $request->conversationId;
            $pay->price = $request->price;
            $pay->description = "İşleme Alındı";
            $pay->paymentUrl = $request->callBackUrl;
            $pay->status = "0";
            $pay->save();
        } else {
            $data = ['result' => false, 'resultCode' => 404, 'message' => 'PaymentMetod Hatalı'];
            DefaultFuctionsHelper::json(json_encode($data));
        }
        $flag = Flag::find(1);
        $flag->value = "1";
        $flag->save();
    }
}

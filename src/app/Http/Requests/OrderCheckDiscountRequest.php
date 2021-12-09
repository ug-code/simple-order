<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use \Illuminate\Contracts\Validation\Validator;

class OrderCheckDiscountRequest extends FormRequest
{


    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            "orderId" => 'required',
        ];
    }

    public function response(array $errors)
    {
        /*
        if ($this->expectsJson()) {
            return new JsonResponse($errors, 422);
        }
        */

    }

    protected function failedValidation(Validator $validator)
    {

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors(),
            'status'  => false
        ], 422));

        //  throw new HttpResponseException(response()->json($validator->errors()->all(), 422));
    }
}

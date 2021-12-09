<?php

namespace App\Service;

use App\Models\Customer;
use App\Models\Product;

class OrderService
{


    /**
     *
     * @param Product $product
     * @param array $item
     *
     * @return false|string
     */
    public function isItemManipulation(Product $product, array $item)
    {
        /**
         * Logic
         * Check customer
         * Check productId
         * Check unitPrice
         * Check total
         */

        $itemPrice = (float)$item['unitPrice'] * (int)$item['quantity'];

        //check manipulation
        if ((float)$itemPrice !== (float)$item['total']) {
            //please log me?
            return MessageService::$error['101'];

        }
        $calculateItemPrice = $product->price * $item['quantity'];


        //check manipulation
        if ((float)$calculateItemPrice !== (float)$item['total']) {
            //please log me?
            return MessageService::$error['102'];

        }

        return false;
    }


    public function isTotalManipulation(float $calculateTotal, float $total)
    {
        //check total manipulation
        if ((float)$calculateTotal !== (float)$total) {

            //please log me?
            return MessageService::$error['103'];

        }

        return false;
    }


}

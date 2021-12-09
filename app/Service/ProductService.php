<?php

namespace App\Service;

use App\Models\Product;

class ProductService
{

    public function isStock(Product $product, int $quantity)
    {
        return $product->stock >= $quantity;
    }
}

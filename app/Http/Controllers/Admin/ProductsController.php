<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductsController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.products.index');
    }
}

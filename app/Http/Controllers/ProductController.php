<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Helpers\ProductHelper;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $productHelper = new ProductHelper();

        if ($request->get('page') >= 0 && $request->get('page_size') >= 0) {
            return $productHelper->getPage($request->get('page'), $request->get('page_size'));
        } else {
            return $productHelper->getPage();
        }
    }
}

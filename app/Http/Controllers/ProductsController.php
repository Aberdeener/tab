<?php

namespace App\Http\Controllers;

use Validator;
use App\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{

    public function new(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|unique:products,name',
            'price' => 'required|numeric',
            'category' => 'required'
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator);
        }

        $pst = false;
        if ($request->has('pst')) $pst = true;

        $stock = 0;
        if (!empty($request->stock)) $stock = $request->stock;
        // Box size of -1 means they cannot receive stock via box. Instead must use normal stock
        $box_size = -1;
        if (!empty($request->box_size)) $box_size = $request->box_size;

        $unlimited_stock = false;
        if ($request->has('unlimited_stock')) $unlimited_stock = true;

        $stock_override = false;
        if ($request->has('stock_override')) $stock_override = true;

        $product = new Products();
        $product->name = $request->name;
        $product->price = $request->price;
        $product->category = $request->category;
        $product->stock = $stock;
        $product->box_size = $box_size;
        $product->unlimited_stock = $unlimited_stock;
        $product->stock_override = $stock_override;
        $product->pst = $pst;
        $product->creator_id = $request->id;
        $product->save();
        return redirect('/products')->with('success', 'Successfully created ' . $request->name . '.');
    }

    public function edit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'price' => 'required|numeric',
            'category' => 'required'
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator);
        }

        $pst = false;
        if ($request->has('pst')) $pst = true;

        $unlimited_stock = false;
        if ($request->has('unlimited_stock')) $unlimited_stock = true;

        $stock_override = false;
        if ($request->has('stock_override')) $stock_override = true;

        DB::table('products')
            ->where('id', $request->product_id)
            ->update(['name' => $request->name, 'price' => $request->price, 'category' => $request->category, 'stock' => $request->stock, 'box_size' => $request->box_size, 'unlimited_stock' => $unlimited_stock, 'stock_override' => $stock_override, 'pst' => $pst, 'editor_id' => $request->editor_id]);
        return redirect('/products')->with('success', 'Successfully edited ' . $request->name . '.');
    }

    public function delete($id)
    {
        Products::where('id', $id)->update(['deleted' => true]);
        return redirect('/products')->with('success', 'Successfully deleted ' . Products::find($id)->name . '.');
    }

    public function adjustStock(Request $request)
    {
        // TODO: If errors: redirect back with the ajax form still showing
        $productId = $request->product_id;
        $product = Products::find($productId);
        if ($product == null) return redirect('/products/adjust')->with('error', 'Invalid Product.');

        // We only flash() not put() so if they go to adjust page later, nothing shows up by default
        session()->flash('last_product', $product);

        /*
            Fix this logic.
            -adjust_stock is only required if adjust_box is not available or empty
            -adjust_box is only required if adjust_stock is empty
            -both cannot be 0
        */
        $validator = Validator::make($request->all(), [
            'adjust_stock' => 'required|numeric|not_in:0',
            'adjust_box' => 'sometimes|numeric|not_in:0',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $adjust_stock = $request->adjust_stock;
        $adjust_box = $request->adjust_box;

        Products::addStock($productId, $adjust_stock);

        if ($request->has('adjust_box')) {
            Products::addBox($productId, $adjust_box);
        }

        return redirect('/products/adjust')->with('success', 'Successfully added ' . $adjust_stock . ' stock and ' . $adjust_box . ' boxes to ' . $product->name . '.');
    }

    public function ajaxInit()
    {
        $product = Products::find(\Request::get('id'));

        return view('pages.products.adjust.form', compact('product', $product));
    }
}

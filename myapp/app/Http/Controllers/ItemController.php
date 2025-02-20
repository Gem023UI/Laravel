<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Stock;
use App\Imports\ItemsImport;
use App\Imports\ItemStockImport;
use App\Cart;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;

class ItemController extends Controller
{
    public function index()
    {
        $items = DB::table('items')
            ->join('stocks', 'items.id', '=', 'stocks.item_id')
            ->select('items.*', 'stocks.quantity')
            ->get();
        return view('item.index', compact('items'));
    }

    public function create()
    {
        return view('item.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'description' => 'required|min:4',
            'image' => 'nullable|mimes:jpg,png'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/images');
        }

        $item = Item::create([
            'description' => trim($request->description),
            'cost_price' => $request->cost_price,
            'sell_price' => $request->sell_price,
            'image' => $path
        ]);

        Stock::create([
            'item_id' => $item->id,
            'quantity' => $request->qty
        ]);

        return redirect()->back()->with('success', 'Item added successfully');
    }

    public function edit($id)
    {
        $item = DB::table('items')
            ->join('stocks', 'items.id', '=', 'stocks.item_id')
            ->where('items.id', $id)
            ->select('items.*', 'stocks.quantity')
            ->first();

        if (!$item) {
            return redirect()->back()->with('error', 'Item not found');
        }

        return view('item.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $rules = [
            'description' => 'required|min:4',
            'image' => 'nullable|mimes:jpg,png'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->hasFile('image')) {
            if ($item->image) {
                Storage::delete($item->image);
            }
            $item->image = $request->file('image')->store('public/images');
        }

        $item->update([
            'description' => trim($request->description),
            'cost_price' => $request->cost_price,
            'sell_price' => $request->sell_price,
        ]);

        $stock = Stock::where('item_id', $id)->first();
        if ($stock) {
            $stock->update(['quantity' => $request->qty]);
        }

        return redirect()->back()->with('success', 'Item updated successfully');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);

        if ($item->image) {
            Storage::delete($item->image);
        }

        $item->delete();
        Stock::where('item_id', $id)->delete();

        return redirect()->back()->with('success', 'Item deleted successfully');
    }

    public function import()
    {
        $file = request()->file('item_upload');

        if (!$file) {
            return redirect()->back()->with('error', 'Please upload a file');
        }

        $filePath = $file->storeAs('files', $file->getClientOriginalName());

        Excel::import(new ItemStockImport, $filePath);

        return redirect()->back()->with('success', 'Excel file imported successfully');
    }

    public function getItems()
    {
        $items = DB::table('items')
            ->join('stocks', 'items.id', '=', 'stocks.item_id')
            ->select('items.*', 'stocks.quantity')
            ->get();

        return view('shop.index', compact('items'));
    }

    public function addToCart($id)
    {
        $item = Item::find($id);
        if (!$item) {
            return redirect()->back()->with('error', 'Item not found');
        }

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->add($item, $id);

        Session::put('cart', $cart);

        return redirect('/')->with('success', 'Item added to cart');
    }

    public function getCart()
    {
        if (!Session::has('cart')) {
            return view('shop.shopping-cart');
        }

        $cart = new Cart(Session::get('cart'));

        return view('shop.shopping-cart', ['products' => $cart->items, 'totalPrice' => $cart->totalPrice]);
    }

    public function getReduceByOne($id)
    {
        if (!Session::has('cart')) {
            return redirect()->route('cart.index');
        }

        $cart = new Cart(Session::get('cart'));
        $cart->reduceByOne($id);

        if (count($cart->items) > 0) {
            Session::put('cart', $cart);
        } else {
            Session::forget('cart');
        }

        return redirect()->route('cart.index');
    }

    public function getRemoveItem($id)
    {
        if (!Session::has('cart')) {
            return redirect()->route('cart.index');
        }

        $cart = new Cart(Session::get('cart'));
        $cart->removeItem($id);

        if (count($cart->items) > 0) {
            Session::put('cart', $cart);
        } else {
            Session::forget('cart');
        }

        return redirect()->route('cart.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;

class UserController extends Controller
{
    public function index()
    {
        return view('user.index');
    }

    public function orders()
    {
        $orders = Order::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->paginate(10);
        return view('user.orders', compact('orders'));
    }

    public function order_details($order_id)
    {
        $order = Order::where('user_id', Auth::user()->id)->where('id', $order_id)->first();
        if($order){
            $orderItems = OrderItem::where('order_id', $order->id)->orderBy('id')->paginate(12);
            $transaction =Transaction::where('order_id', $order->id)->first(); 
            return view('user.order-details', compact('order', 'orderItems', 'transaction'));     
        }
        else {
            return redirect()->route('login');
        }        
    }

    public function order_cancel(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = 'Canceled';
        $order->canceled_date = Carbon::now();
        $order->save();
        return back()->with('status', 'Order cancelled successfully!');
    }

    public function addresses()
    {
        $addresses = Address::where('user_id', Auth::user()->id)->get();
        return view('user.addresses', compact('addresses'));
    }

    public function address_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'locality' => 'nullable',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'zip' => 'required',
        ]);

        $address = new Address();
        $address->user_id = Auth::id();
        $address->name = $request->name;
        $address->phone = $request->phone;
        $address->locality = $request->locality;
        $address->address = $request->address;
        $address->city = $request->city;
        $address->country = $request->country;
        $address->zip = $request->zip;
        
        // If this is the first address, make it default
        if (Address::where('user_id', Auth::id())->count() === 0) {
            $address->isdefault = true;
        }
        
        $address->save();
        
        return redirect()->route('user.addresses')->with('status', 'Address added successfully!');
    }

    public function address_update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'locality' => 'nullable',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'zip' => 'required',
        ]);

        $address = Address::where('user_id', Auth::id())->findOrFail($id);
        $address->name = $request->name;
        $address->phone = $request->phone;
        $address->locality = $request->locality;
        $address->address = $request->address;
        $address->city = $request->city;
        $address->country = $request->country;
        $address->zip = $request->zip;
        $address->save();
        
        return redirect()->route('user.addresses')->with('status', 'Address updated successfully!');
    }

    public function address_delete($id)
    {
        $address = Address::where('user_id', Auth::id())->findOrFail($id);
        
        // If deleting default address, make another address default if exists
        if ($address->isdefault) {
            $newDefault = Address::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->first();
            
            if ($newDefault) {
                $newDefault->isdefault = true;
                $newDefault->save();
            }
        }
        
        $address->delete();
        return redirect()->route('user.addresses')->with('status', 'Address deleted successfully!');
    }
}

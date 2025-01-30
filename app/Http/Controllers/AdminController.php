<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('created_at', 'desc')->get()->take(5);

        $dashboardDatas = DB::select("SELECT sum(total) as TotalAmount,
                                    sum(if(status = 'ordered', total, 0)) as TotalOrderedAmount,
                                    sum(if(status = 'delivered', total, 0)) as TotalDeliveredAmount,
                                    sum(if(status = 'canceled', total, 0)) as TotalCanceledAmount,
                                    count(*) as Total,
                                    sum(if(status = 'ordered', 1,0)) as TotalOrdered,
                                    sum(if(status = 'delivered', 1,0)) as TotalDelivered,
                                    sum(if(status = 'canceled', 1,0)) as TotalCanceled
                                    FROM orders");

        $monthlyData = Order::selectRaw('
            MONTH(created_at) as month,
            SUM(total) as total_amount,
            SUM(CASE WHEN status = "ordered" THEN total ELSE 0 END) as ordered_amount,
            SUM(CASE WHEN status = "delivered" THEN total ELSE 0 END) as delivered_amount,
            SUM(CASE WHEN status = "canceled" THEN total ELSE 0 END) as canceled_amount
        ')
        ->whereYear('created_at', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $AmountM = array_fill(0, 12, 0);
        $OrderedAmountM = array_fill(0, 12, 0);
        $DeliveredAmountM = array_fill(0, 12, 0);
        $CanceledAmountM = array_fill(0, 12, 0);

        foreach ($monthlyData as $data) {
            $index = $data->month - 1;
            $AmountM[$index] = (float)$data->total_amount;
            $OrderedAmountM[$index] = (float)$data->ordered_amount;
            $DeliveredAmountM[$index] = (float)$data->delivered_amount;
            $CanceledAmountM[$index] = (float)$data->canceled_amount;
        }

        $TotalAmount = collect($dashboardDatas)->sum('TotalAmount');
        $TotalOrderedAmount = collect($dashboardDatas)->sum('TotalOrderedAmount');
        $TotalDeliveredAmount = collect($dashboardDatas)->sum('TotalDeliveredAmount');
        $TotalCanceledAmount = collect($dashboardDatas)->sum('TotalCanceledAmount');

        return view('admin.index', compact('orders', 'dashboardDatas', 'AmountM', 'OrderedAmountM', 'DeliveredAmountM', 'CanceledAmountM', 'TotalAmount', 'TotalOrderedAmount', 'TotalDeliveredAmount', 'TotalCanceledAmount'));
    }

    public function brands()
    {
        $brands = Brand::orderBy('id', 'desc')->paginate(10);
        // compact('variable') is the same as $variable
        return view('admin.brands', compact('brands'));
    }

    public function addBrand()
    {
        return view('admin.add-brand');
    }

    public function brand_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:jpg,png,jpeg|max:5000',
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateBrandThumbnailsImage($image, $file_name);
        $brand->image = $file_name;
        $brand->save();

        return redirect()->route('admin.brands')->with('status', 'Brand added successfully');
    }

    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $request->id,
            'image' => 'mimes:jpg,png,jpeg|max:5000',
        ]);

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);

        if($request->hasFile('image')){
            if(File::exists(public_path('uploads/brands') . '/' . $brand->image))
            {
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateBrandThumbnailsImage($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand updated successfully');
    }

    public function GenerateBrandThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');
        // go to composer.json, add image intervention/image-laravel and composer update in terminal
        $img = Image::read($image->path());
        $img->cover(124, 124, "center");
        $img->resize(124, 124)->save($destinationPath . '/' . $imageName);
    }

    public function brand_delete($id)
    {
        $brand = Brand::find($id);
        if(File::exists(public_path('uploads/brands') . '/' . $brand->image))
        {
            File::delete(public_path('uploads/brands') . '/' . $brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand deleted successfully');
    }

    public function categories()
    {
        $categories = Category::orderBy('id', 'desc')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    public function category_add()
    {
        return view('admin.category-add');
    }

    public function category_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:jpg,png,jpeg|max:5000',
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateCategoryThumbnailsImage($image, $file_name);
        $category->image = $file_name;
        $category->save();

        return redirect()->route('admin.categories')->with('status', 'Category added successfully');
    }

    public function GenerateCategoryThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, "center");
        $img->resize(124, 124)->save($destinationPath . '/' . $imageName);
    }

    public function category_edit($id)
    {
        $category = Category::find($id);
        return view('admin.category-edit', compact('category'));
    }

    public function category_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,' . $request->id,
            'image' => 'mimes:jpg,png,jpeg|max:5000',
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);

        if($request->hasFile('image')){
            if(File::exists(public_path('uploads/categories') . '/' . $category->image))
            {
                File::delete(public_path('uploads/categories') . '/' . $category->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateCategoryThumbnailsImage($image, $file_name);
            $category->image = $file_name;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category updated successfully');
    }

    public function category_delete($id)
    {
        $category = Category::find($id);
        if(File::exists(public_path('uploads/categories') . '/' . $category->image))
        {
            File::delete(public_path('uploads/categories') . '/' . $category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category deleted successfully');
    }

    public function products()
    {
        $search = request('search');
        
        $products = Product::query()
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhereHas('brand', function($brandQuery) use ($search) {
                          $brandQuery->where('name', 'like', '%'.$search.'%');
                      })
                      ->orWhereHas('category', function($categoryQuery) use ($search) {
                          $categoryQuery->where('name', 'like', '%'.$search.'%');
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('admin.products', compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-add', compact('categories', 'brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:jpg,png,jpeg|max:5000',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = carbon::now()->timestamp;

        if($request->hasFile('image'))
        {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images'))
        {
            $allowedfileExtensions = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach($files as $file)
            {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtensions);
                if($gcheck)
                {
                    //gfile and gextention => short for gallery file and gallery extension
                    $gfileName = $current_timestamp  . '-' . $counter . '.' . $gextension;
                    $this->GenerateProductThumbnailImage($file, $gfileName);
                    array_push($gallery_arr, $gfileName);
                    $counter++;
                }
            }

            $gallery_images = implode(',', $gallery_arr);
        }

        $product->images = $gallery_images;
        $product->save();

        return redirect()->route('admin.products')->with('status', 'Product added successfully');
    }

    public function GenerateProductThumbnailImage($image, $imageName)
    {
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540, 689, "center");
        $img->resize(540, 689)->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104)->save($destinationPathThumbnail . '/' . $imageName);
    }

    public function product_edit($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function product_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:jpg,png,jpeg|max:5000',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = Product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = carbon::now()->timestamp;

        if($request->hasFile('image'))
        {
            if(File::exists(public_path('uploads/products') . '/' . $product->image))
            {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if(File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image))
            {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images'))
        {
            // ofile => short for other files (images) so all the other images from that product
            foreach(explode( ',', $product->images) as $ofile)
            {
                if(File::exists(public_path('uploads/products') . '/' . $ofile))
                {
                File::delete(public_path('uploads/products') . '/' . $ofile);
                }
            if(File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile))
                {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
                }
            }
            $allowedfileExtensions = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach($files as $file)
            {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtensions);
                if($gcheck)
                {
                    //gfile and gextention => short for gallery file and gallery extension
                    $gfileName = $current_timestamp  . '-' . $counter . '.' . $gextension;
                    $this->GenerateProductThumbnailImage($file, $gfileName);
                    array_push($gallery_arr, $gfileName);
                    $counter++;
                }
            }

            $gallery_images = implode(',', $gallery_arr);
            $product->images = $gallery_images;
        }

        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product updated successfully');
    }

    public function product_delete($id)
    {
        $product = Product::find($id);
        if(File::exists(public_path('uploads/products') . '/' . $product->image))
        {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if(File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image))
        {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }
        // delete all the other images from that product
        foreach(explode( ',', $product->images) as $ofile)
        {
            if(File::exists(public_path('uploads/products') . '/' . $ofile))
            {
            File::delete(public_path('uploads/products') . '/' . $ofile);
            }
        if(File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile))
            {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
            }
        }

        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product deleted successfully');
    }

    public function coupons()
    {
        $search = request('search');
        
        $coupons = Coupon::query()
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', '%'.$search.'%')
                      ->orWhere('type', 'like', '%'.$search.'%')
                      ->orWhere('value', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('admin.coupons', compact('coupons'));
    }

    public function coupon_add()
    {
        return view('admin.coupon-add');
    }

    public function coupon_store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $coupon = new Coupon();
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon added successfully!');
    }

    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.coupon-edit', compact('coupon'));
    }

    public function coupon_update(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $coupon = Coupon::find($request->id);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon updated successfully!');
    }

    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status', 'Coupon deleted successfully!');
    }

    public function orders()
    {
        $search = request('search');
        
        $orders = Order::query()
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhere('status', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('admin.orders', compact('orders'));
    }

    public function order_details($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();
        return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
    }

    public function update_order_status(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = $request->order_status;
        if($request->order_status == 'delivered')
        {
            $order->delivered_date = Carbon::now();
        }
        elseif($request->order_status == 'canceled')
        {
            $order->canceled_date = Carbon::now();
        }
        $order->save();

        if($request->order_status == 'delivered')
        {
            $transaction = Transaction::where('order_id', $request->order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with('status', 'Order status updated successfully!');
    }

    public function slides()
    {
        $slides = Slide::orderBy('id', 'desc')->paginate(12);
        return view('admin.slides', compact('slides'));
    }

    public function slide_add()
    {
        return view('admin.slide-add');
    }

    public function slide_store(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:5000',
        ]);

        $slide = new Slide();
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateSlideThumbnailsImage($image, $file_name);
        $slide->image = $file_name;

        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide added successfully!');
    }

    public function GenerateSlideThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/slides');
        $img = Image::read($image->path());
        $img->cover(400, 690, "center");
        $img->resize(400, 690)->save($destinationPath . '/' . $imageName);
    }

    public function slide_edit($id)
    {
        $slide = Slide::find($id);
        return view('admin.slide-edit', compact('slide'));
    }

    public function slide_update(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:5000',
        ]);

        $slide = Slide::find($request->id);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if($request->hasFile('image'))
        {
            if(File::exists(public_path('uploads/slides') . '/' . $slide->image))
            {
                File::delete(public_path('uploads/slides') . '/' . $slide->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateSlideThumbnailsImage($image, $file_name);
            $slide->image = $file_name;
        }

        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide updated successfully!');
    }

    public function slide_delete($id)
    {
        $slide = Slide::find($id);
        if(File::exists(public_path('uploads/slides') . '/' . $slide->image))
        {
            File::delete(public_path('uploads/slides') . '/' . $slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with('status', 'Slide deleted successfully!');
    }

    public function contacts()
    {
        $contacts = Contact::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.contacts', compact('contacts'));
    }

    public function contact_delete($id)
    {
        $contact = Contact::find($id);
        $contact->delete();
        return redirect()->route('admin.contacts')->with('status', 'Contact deleted successfully!');
    }

    public function search(request $request)
    {
        $query = $request->input('query');
        $results = Product::where('name', 'like', "%{$query}%")->get()->take(8);
        return response()->json($results);
    }
}

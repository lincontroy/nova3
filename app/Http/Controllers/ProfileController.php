<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use App\Models\Deposit;
use DB;


class ProfileController extends Controller
{

    public function approve($id)
    {
        try {
            DB::beginTransaction();
            
            $deposit = Deposit::with('user')->findOrFail($id);
            
            // Check if already approved
            if ($deposit->status === 'approved') {
                return redirect()->back()->with('swal_error', [
                    'title' => 'Already Approved!',
                    'text' => 'This deposit has already been approved.',
                    'icon' => 'warning'
                ]);
            }
            
            // Update deposit status
            $deposit->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);
            
            // Update user's wallet balance
            $user = $deposit->user;
            $user->increment('wallet_balance', $deposit->amount);
            
            DB::commit();
            
            return redirect()->back()->with('swal_success', [
                'title' => 'Deposit Approved!',
                'text' => "Deposit of $" . number_format($deposit->amount, 2) . " has been approved and added to {$user->name}'s wallet.",
                'icon' => 'success'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('swal_error', [
                'title' => 'Error!',
                'text' => 'An error occurred while approving the deposit. Please try again.',
                'icon' => 'error'
            ]);
        }
    }

    /**
     * Reject a deposit.
     */
    public function reject($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            
            // Check if already processed
            if ($deposit->status !== 'pending') {
                return redirect()->back()->with('swal_error', [
                    'title' => 'Already Processed!',
                    'text' => 'This deposit has already been processed.',
                    'icon' => 'warning'
                ]);
            }
            
            $deposit->update([
                'status' => 'rejected'
            ]);
            
            return redirect()->back()->with('swal_success', [
                'title' => 'Deposit Rejected!',
                'text' => "Deposit of $" . number_format($deposit->amount, 2) . " has been rejected.",
                'icon' => 'success'
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('swal_error', [
                'title' => 'Error!',
                'text' => 'An error occurred while rejecting the deposit. Please try again.',
                'icon' => 'error'
            ]);
        }
    }


    public function alldeposits(){
        $deposits=Deposit::where('id','>=',0)->get();

        return view('admin.deposits.index',compact('deposits'));
    }

    public function allorders(){
        $orders=Order::where('id','>=',0)->orderBy('id','DESC')->get();

        return view('admin.orders.index',compact('orders'));
    }

    public function postdeposit(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:999999.99',
            'payment_method' => 'required|in:bank_transfer,mobile_money,cash,chapa',
            'reference_number' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'proof' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please correct the errors and try again.');
        }

        try {
            // Handle file upload
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $file = $request->file('proof');
                $filename = time() . '_' . $file->getClientOriginalName();
                $proofPath = $file->storeAs('deposits/proofs', $filename, 'public');
            }

            // Create the deposit
            $deposit = Deposit::create([
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'proof_path' => $proofPath,
                'status' => 'pending', // Default status
            ]);

            return redirect()->back()->with('success', 'Deposit request submitted successfully! Reference ID: ' . $deposit->id);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Deposit creation failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while processing your deposit. Please try again.');
        }
    }

    public function packages(){
        return view('packages');
    }

    public function orders(){
        return view('orders');
    }
    public function showdeposit(){
        return view('deposit');
    }
    public function showPackage(string $name)
    {
        // Consider moving this to a config file or database
        $packages = collect([
            ['name' => 'Level 1', 'price' => 2500, 'product_value' => 800000],
            ['name' => 'Level 2', 'price' => 5500, 'product_value' => 1200000],
            ['name' => 'Level 3', 'price' => 8500, 'product_value' => 1600000],
            ['name' => 'Level 4', 'price' => 12000, 'product_value' => 2000000],
            ['name' => 'Level 5', 'price' => 24000, 'product_value' => 2500000],
        ]);
    
        $package = $packages->firstWhere('name', $name);
    
        abort_if(!$package, 404, 'Package not found');
    
        // Get the package index (1-based) to determine image count
        $packageIndex = $packages->search(function ($pkg) use ($name) {
            return $pkg['name'] === $name;
        }) + 1; // Add 1 to make it 1-based (Starter=1, Silver=2, etc.)
    
        $randomImages = $this->getRandomImages($packageIndex);
    
        return view('packages.show', compact('package', 'randomImages'));
    }
    
    /**
     * Process package order
     */
    public function processOrder(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'product_value' => 'required|numeric|min:0'
        ]);
    
        $user = auth()->user();
        $packagePrice = $request->price;

        // dd($user->wallet_balance);
        $commission = $request->product_value * 0.10;
    
        // Check if user has sufficient balance
        if ($user->wallet_balance < $packagePrice) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Required: ETB ' . number_format($packagePrice) . ', Available: ETB ' . number_format($user->wallet_balance ?? 0),
                'required_amount' => number_format($packagePrice),
                'available_balance' => number_format($user->wallet_balance ?? 0)
            ], 400);
        }
    
        // Deduct package price from wallet balance
        $user->decrement('wallet_balance', $packagePrice);
    
        // Create the order
        $order = $user->orders()->create([
            'package_name' => $request->name,
            'price' => $packagePrice,
            'product_value' => $request->product_value,
            'commission' => $commission,
            'status' => 'completed',
            'commission_processed_at' => now()
        ]);
    
        // Add commission to user's commission balance
        $user->increment('total_commissions', $commission);

        $user->increment('level');

    
        return response()->json([
            'success' => true,
            'message' => 'Order created successfully! Your commission of ETB ' . number_format($commission) . ' will be reflected in your dashboard within 5 minutes.',
            'order_id' => $order->id,
            'commission' => number_format($commission),
            'remaining_balance' => number_format($user->fresh()->wallet_balance)
        ]);
    }
    
    /**
     * Get random images based on user level
     */
    
    /**
     * Get random images based on user level
     */
    private function getRandomImages(int $count): \Illuminate\Support\Collection
    {
        if ($count <= 0) {
            return collect();
        }
    
        $imagesPath = public_path('images');
        
        // Check if directory exists
        if (!File::isDirectory($imagesPath)) {
            \Log::warning('Images directory not found: ' . $imagesPath);
            return collect();
        }
    
        $images = collect(File::files($imagesPath))
            ->filter(fn ($file) => $this->isImageFile($file))
            ->shuffle()
            ->take($count)
            ->map(fn ($file) => asset('images/' . $file->getFilename()));
    
        // Debug logging
        \Log::info('Found ' . $images->count() . ' images for user level ' . $count);
        
        return $images;
    }
    
    /**
     * Check if file is a valid image
     */
    private function isImageFile(\SplFileInfo $file): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        return in_array(strtolower($file->getExtension()), $allowedExtensions);
    }
    public function dashboard()
    {

        $user = auth()->user();

        $userCount=User::count();
        $categoryCount=Category::count();


        // dd($user->wallet_balance);

        return view('dashboard', [
            'user'=>$userCount,
            'category'=>$categoryCount,
            'walletBalance' => $user->wallet_balance,
            'totalCommissions' => $user->total_commissions,
            'totalWithdrawals' => $user->total_withdrawals,
        ]);
    }
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // dd($request->post());
        // dd($request->user());
        $request->user()->fill($request->validated());
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        User::where('id', $request->user()->id)->update(['mode'=>$request->mode]);

        $request->user()->save();

        return Redirect::route('admin.profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    
}

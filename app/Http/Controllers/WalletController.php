<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
class WalletController extends Controller
{
   
    //transfer
public function transfer(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id', // Sender's user ID
        'amount' => 'required|numeric|min:1',
        'recipient_id' => 'required|exists:users,id', // Recipient's user ID
    ]);

    $senderId = $request->user_id;
    $recipientId = $request->recipient_id;
    $amount = $request->amount;

    // Fetch sender's wallet
    $senderWallet = DB::table('wallets')->where('user_id', $senderId)->first();

    if (!$senderWallet || $senderWallet->balance < $amount) {
        return response()->json([
            'error' => 'Insufficient balance or wallet not found',
            'user_id' => $senderId,
            'balance' => $senderWallet ? $senderWallet->balance : 0,
        ], 400);
    }

    // Fetch recipient's wallet
    $recipientWallet = DB::table('wallets')->where('user_id', $recipientId)->first();

    if (!$recipientWallet) {
        return response()->json(['error' => 'Recipient wallet not found'], 404);
    }

    // Perform transfer in a transaction
    DB::transaction(function () use ($senderId, $recipientId, $amount) {
        // Deduct from sender
        DB::table('wallets')->where('user_id', $senderId)->decrement('balance', $amount);

        // Add to recipient
        DB::table('wallets')->where('user_id', $recipientId)->increment('balance', $amount);
    });

    return response()->json(['message' => 'Transfer successful']);
}

//Fetch Balance
public function fetchBalance(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id', // Validate user_id exists
    ]);

    $userId = $request->user_id;

    // Query the wallet table for the user's balance
    $wallet = DB::table('wallets')->where('user_id', $userId)->first();

    if (!$wallet) {
        return response()->json(['error' => 'Wallet not found for this user'], 404);
    }

    return response()->json([
        'user_id' => $userId,
        'balance' => $wallet->balance,
    ]);
}

//Initiate Order
public function initiateOrder(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:1',
        'order_details' => 'required|string', // Details about the order
    ]);

    $userId = $request->user_id;
    $amount = $request->amount;

    // Fetch the user's wallet
    $wallet = DB::table('wallets')->where('user_id', $userId)->first();

    if (!$wallet) {
        return response()->json(['error' => 'Wallet not found for this user'], 404);
    }

    // Check if balance permits the order
    if ($wallet->balance < $amount) {
        return response()->json([
            'error' => 'Insufficient balance',
            'current_balance' => $wallet->balance,
        ], 400);
    }

    // Deduct the order amount from the user's wallet
    DB::table('wallets')->where('user_id', $userId)->update([
        'balance' => $wallet->balance - $amount,
        'updated_at' => now(),
    ]);

    // Simulate order creation (e.g., in an orders table)
    DB::table('orders')->insert([
        'user_id' => $userId,
        'amount' => $amount,
        'order_details' => $request->order_details,
        'status' => 'initiated',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['message' => 'Order initiated successfully']);
}


public function getOrderStatus(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
    ]);

    $order = Order::find($request->order_id);

    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    return response()->json([
        'order_id' => $order->id,
        'status' => $order->status,
        'details' => $order->details,
    ]);
}
}

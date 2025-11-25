<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidReceiverException;
use App\Exceptions\SelfTransferException;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Get all transactions for the authenticated user (incoming and outgoing).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get all transactions where user is sender or receiver
        $transactions = Transaction::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender:id,name,email', 'receiver:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Get fresh balance from database (more efficient than refresh on entire model)
        $balance = (float) User::where('id', $user->id)->value('balance');

        return response()->json([
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Create a new transaction transfer.
     *
     * @param  TransactionRequest  $request
     * @param  TransactionService  $transactionService
     * @return JsonResponse
     */
    public function store(TransactionRequest $request, TransactionService $transactionService): JsonResponse
    {
        $user = Auth::user();

        try {
            $transaction = $transactionService->transfer(
                $user,
                $request->validated()['receiver_id'],
                $request->validated()['amount']
            );

            // Transaction already has relationships loaded, get balance from sender
            // The transaction service returns the transaction with relationships loaded
            $balance = (float) User::where('id', $user->id)->value('balance');

            return response()->json([
                'message' => 'Transaction completed successfully.',
                'transaction' => $transaction,
                'balance' => $balance,
            ], 201);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (InvalidReceiverException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (SelfTransferException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}


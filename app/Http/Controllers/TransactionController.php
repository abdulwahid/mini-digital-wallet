<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidReceiverException;
use App\Exceptions\SelfTransferException;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
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
        
        // Refresh user to get latest balance
        $user->refresh();

        // Get all transactions where user is sender or receiver
        $transactions = Transaction::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender:id,name,email', 'receiver:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'balance' => (float) $user->balance,
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

            // Refresh user to get updated balance
            $user->refresh();

            return response()->json([
                'message' => 'Transaction completed successfully.',
                'transaction' => $transaction->load(['sender:id,name,email', 'receiver:id,name,email']),
                'balance' => (float) $user->balance,
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


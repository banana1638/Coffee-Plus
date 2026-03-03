<?php

namespace App\Services;

use App\Models\{CartItem, Order, OrderItem, Transaction, User};
use Illuminate\Support\Facades\{DB, Auth};

class CheckoutService
{
    /**
     * Process the checkout for the authenticated user.
     *
     * @param array $useOzIds
     * @return Order
     * @throws \Exception
     */
    public function processCheckout(array $useOzIds): Order
    {
        $user = Auth::user();
        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            throw new \Exception('Your cart is empty.');
        }

        return DB::transaction(function () use ($user, $cartItems, $useOzIds) {
            $totalCashToPay = 0;
            $totalOzToDrain = 0;
            $totalRewardOz = 0;

            $order = new Order();
            $order->user_id = $user->id;
            $order->bill_id = 'CP-' . strtoupper(uniqid());
            $order->status = 'pending';
            $order->subtotal = 0;
            $order->final_amount = 0;
            $order->oz_used = 0;
            $order->save();

            foreach ($cartItems as $item) {
                $unitPrice = $item->unit_price;
                $isRedeem = in_array($item->id, $useOzIds);

                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->options = [
                    'size' => $item->size,
                    'temp' => $item->temp,
                    'addons' => $item->addons
                ];
                $orderItem->price = $item->product->price;

                if ($isRedeem) {
                    $ozNeeded = (int) ($unitPrice * 100 * $item->quantity);
                    $totalOzToDrain += $ozNeeded;
                    $orderItem->oz_at_time = $ozNeeded;
                    $orderItem->price_at_time = 0;
                } else {
                    $itemTotal = $unitPrice * $item->quantity;
                    $totalCashToPay += $itemTotal;
                    $orderItem->oz_at_time = 0;
                    $orderItem->price_at_time = $unitPrice;
                    $totalRewardOz += (int) ($itemTotal * 50);
                }
                $orderItem->save();
            }

            if ($user->tangki_oz < $totalOzToDrain) {
                throw new \Exception("Insufficient OZ Balance. You need " . number_format($totalOzToDrain) . " OZ.");
            }
            if ($user->tangki_balance < $totalCashToPay) {
                throw new \Exception("Insufficient Account Balance. Required: RM " . number_format($totalCashToPay, 2));
            }

            // Update User Balances
            $user->tangki_oz -= $totalOzToDrain;
            $user->tangki_oz += $totalRewardOz;
            $user->tangki_balance -= $totalCashToPay;
            $user->save();

            $order->subtotal = $totalCashToPay + ($totalOzToDrain / 100);
            $order->final_amount = $totalCashToPay;
            $order->oz_used = $totalOzToDrain;
            $order->save();

            // Log Transactions
            if ($totalOzToDrain > 0) {
                $this->logTransaction($user->id, $order->bill_id, -$totalOzToDrain, 'drain', "Cart Redemption (Order: {$order->bill_id})");
            }
            if ($totalRewardOz > 0) {
                $this->logTransaction($user->id, $order->bill_id, $totalRewardOz, 'refill', "Cart Purchase Reward (Order: {$order->bill_id})");
            }

            // Clear Cart
            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });
    }

    /**
     * Log a transaction for the user.
     *
     * @param int $userId
     * @param string $billId
     * @param int $delta
     * @param string $type
     * @param string $desc
     * @return void
     */
    private function logTransaction(int $userId, string $billId, int $delta, string $type, string $desc): void
    {
        $transaction = new Transaction();
        $transaction->user_id = $userId;
        $transaction->bill_id = $billId;
        $transaction->oz_delta = $delta;
        $transaction->type = $type;
        $transaction->description = $desc;
        $transaction->save();
    }
}

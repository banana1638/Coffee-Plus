<?php

namespace App\Services;

use App\Models\{CartItem, Order, OrderItem, Transaction, User};
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process checkout
     */
    public function processCheckout(User $user, array $useOzIds): Order
    {
        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();

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
                $quantity = $item->quantity;
                $itemTotal = $unitPrice * $quantity;

                $isRedeem = in_array($item->id, $useOzIds);

                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $quantity;
                $orderItem->options = [
                    'size' => $item->size,
                    'temp' => $item->temp,
                    'addons' => $item->addons
                ];
                $orderItem->price = $item->product->price;

                // =========================
                // OZ 兑换逻辑
                // =========================
                if ($isRedeem) {

                    $ozNeeded = (int) ($itemTotal * 100);

                    $totalOzToDrain += $ozNeeded;

                    $orderItem->oz_at_time = $ozNeeded;
                    $orderItem->price_at_time = 0;
                }

                // =========================
                // 现金支付逻辑
                // =========================
                else {

                    $totalCashToPay += $itemTotal;

                    $orderItem->oz_at_time = 0;
                    $orderItem->price_at_time = $unitPrice;

                    // ⭐ 奖励 OZ（只在现金支付时）
                    $totalRewardOz += (int) (($itemTotal * 100) / 2);
                }

                $orderItem->save();
            }

            // =========================
            // 余额检查
            // =========================
            if ($user->tangki_oz < $totalOzToDrain) {
                throw new \Exception(
                    "Insufficient OZ Balance. Required: " . number_format($totalOzToDrain)
                );
            }

            if ($user->tangki_balance < $totalCashToPay) {
                throw new \Exception(
                    "Insufficient Balance. Required: RM " . number_format($totalCashToPay, 2)
                );
            }

            // =========================
            // 更新用户资产
            // =========================

            // 扣 OZ（兑换）
            if ($totalOzToDrain > 0) {
                $user->tangki_oz -= $totalOzToDrain;
            }

            // 扣 balance + 加奖励 OZ
            if ($totalCashToPay > 0) {
                $user->tangki_balance -= $totalCashToPay;
                $user->tangki_oz += $totalRewardOz;
            }

            $user->save();

            // =========================
            // 更新订单
            // =========================
            $order->subtotal = $totalCashToPay + ($totalOzToDrain / 100);
            $order->final_amount = $totalCashToPay;
            $order->oz_used = $totalOzToDrain;
            $order->save();

            // =========================
            // 交易记录
            // =========================

            if ($totalOzToDrain > 0) {
                $this->logTransaction(
                    $user->id,
                    $order->bill_id,
                    -$totalOzToDrain,
                    'drain',
                    "OZ Redeem (Order: {$order->bill_id})"
                );
            }

            if ($totalRewardOz > 0) {
                $this->logTransaction(
                    $user->id,
                    $order->bill_id,
                    $totalRewardOz,
                    'refill',
                    "Cash Reward (Order: {$order->bill_id})"
                );
            }

            // =========================
            // 清空购物车
            // =========================
            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });
    }

    /**
     * Log transaction
     */
    private function logTransaction(
        int $userId,
        string $billId,
        int $delta,
        string $type,
        string $desc
    ): void {
        $transaction = new Transaction();
        $transaction->user_id = $userId;
        $transaction->bill_id = $billId;
        $transaction->oz_delta = $delta;
        $transaction->type = $type;
        $transaction->description = $desc;
        $transaction->save();
    }
}
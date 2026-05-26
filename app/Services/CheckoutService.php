<?php

namespace App\Services;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\TangkiServiceInterface;
use App\Models\{CartItem, Order, OrderItem, User};
use Illuminate\Support\Facades\DB;

class CheckoutService implements CheckoutServiceInterface
{
    protected TangkiServiceInterface $tangkiService;

    public function __construct(TangkiServiceInterface $tangkiService)
    {
        $this->tangkiService = $tangkiService;
    }

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
            // 余额/OZ 检查与扣除
            // =========================
            if ($totalOzToDrain > 0) {
                $description = "OZ Redeem (Order: {$order->bill_id})";
                $drained = $this->tangkiService->drainOz($user, $totalOzToDrain, $order->bill_id, $description);
                if (!$drained) {
                    throw new \Exception(
                        "Insufficient OZ Balance. Required: " . number_format($totalOzToDrain)
                    );
                }
            }

            if ($totalCashToPay > 0) {
                $description = "Cash Reward (Order: {$order->bill_id})";
                $deducted = $this->tangkiService->deductBalanceAndRewardOz($user, $totalCashToPay, $totalRewardOz, $order->bill_id, $description);
                if (!$deducted) {
                    throw new \Exception(
                        "Insufficient Balance. Required: RM " . number_format($totalCashToPay, 2)
                    );
                }
            }

            // =========================
            // 更新订单
            // =========================
            $order->subtotal = $totalCashToPay + ($totalOzToDrain / 100);
            $order->final_amount = $totalCashToPay;
            $order->oz_used = $totalOzToDrain;
            $order->save();

            // =========================
            // 清空购物车
            // =========================
            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });
    }
}
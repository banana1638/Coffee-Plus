<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAllAsRead()
    {
        // 这里的逻辑与你之前闭包中的一致，但放在控制器中更易于维护
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * (可选) 以后如果你想点击单条通知标记已读并跳转：
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // 也可以根据通知内容进行跳转，例如跳转到订单详情
        return back();
    }
}

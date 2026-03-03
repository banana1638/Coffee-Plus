<?php

namespace App\Services;

class TangkiService {
    public static function drain($user, $ozAmount, $billId) {
        if ($user->tangki_oz < $ozAmount) return false;
        
        $user->decrement('tangki_oz', $ozAmount);
        $user->transactions()->create([
            'bill_id' => $billId,
            'oz_delta' => -$ozAmount,
            'type' => 'drain',
            'description' => 'Redeemed items'
        ]);
        return true;
    }
}
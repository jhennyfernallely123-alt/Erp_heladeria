<?php

namespace App\Services\Invoicing;

use App\Contracts\InvoiceProviderInterface;
use App\Models\BusinessSetting;

class InvoiceService
{
    public static function getProvider(?string $mode = null): InvoiceProviderInterface
    {
        $activeMode = $mode ?? BusinessSetting::get('billing_mode', env('BILLING_MODE', 'internal'));

        return match ($activeMode) {
            'dian' => app(DianInvoiceProvider::class),
            default => app(InternalInvoiceProvider::class),
        };
    }
}

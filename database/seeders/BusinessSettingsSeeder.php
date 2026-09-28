<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessSetting;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'shop_name' => 'Heladería Artesanal Nieve Real',
            'shop_nit' => '901.458.789-1',
            'shop_address' => 'Carrera 15 # 85 - 32, Zona Rosa',
            'shop_phone' => '+57 (601) 789-4560',
            'shop_email' => 'contacto@nievereal.com',
            'invoice_prefix' => 'FAC-',
            'invoice_consecutive' => '1',
            'dian_prefix' => 'SETP-',
            'dian_consecutive' => '1',
            'billing_mode' => 'internal', // internal or dian
            'tax_percentage' => '0', // 0% or 8% INC / 19% IVA if applicable
            'suggested_tip_percentage' => '10',
            'delivery_fee' => '3000', // tarifa fija de envio a domicilio, en pesos
        ];

        foreach ($settings as $k => $v) {
            BusinessSetting::firstOrCreate(['key' => $k], ['value' => $v]);
        }
    }
}

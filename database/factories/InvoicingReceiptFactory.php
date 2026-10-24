<?php

namespace Database\Factories;

use App\Models\InvoicingReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicingReceipt>
 */
class InvoicingReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 10, 500);
        $subtotalExclude = fake()->randomFloat(2, 1000000, 50000000);

        return [
            'tanggal_terima_grs' => fake()->date(),
            'jenis' => 'GRS',
            'no_grs' => fake()->numerify('53########'),
            'no_po' => fake()->numerify('45########'),
            'no_do' => fake()->numerify('DO-#####'),
            'padi' => 'Non-PADI',
            'material_type' => 'ZSP',
            'vendor_name' => fake()->company(),
            'deskripsi' => fake()->sentence(4),
            'email_vendor' => fake()->companyEmail(),
            'tanggal_info_grs' => fake()->date(),
            'nama_barang' => fake()->words(3, true),
            'qty_penagihan' => $qty,
            'qty_po' => $qty,
            'uoi' => 'PCS',
            'subtotal_exclude_ppn' => $subtotalExclude,
            'subtotal_include_ppn' => round($subtotalExclude * 1.11, 2),
            'tanggal_terima_koreksi_invoice' => fake()->date(),
            'status' => 'Menunggu Verifikasi',
        ];
    }
}

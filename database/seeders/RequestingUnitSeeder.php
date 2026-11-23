<?php

namespace Database\Seeders;

use App\Models\RequestingUnit;
use Illuminate\Database\Seeder;

class RequestingUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Rendal TA P5',
                'requisitioner_aliases' => ['RENDAL-TAP5', 'RENDAL TA P5', 'HAR1-TAP5', 'RENHAR-TAP5', 'RENHAR P5', 'BENGKEL-TAP5', 'MEKANIK-TAP5', 'MMM P5'],
                'avp_title' => 'AVP Rendal TA P5',
                'vp_title' => 'VP Pemeliharaan / TA',
                'is_pilot' => true,
            ],
            [
                'name' => 'Pemeliharaan / Rendalhar',
                'requisitioner_aliases' => ['RENDALHAR', 'RENHAR', 'RENHAR-P1A', 'RENHAR-P2', 'RENHAR P7', 'HAR-2', 'HAR1', 'MMM PABRIK', 'MMMP6', 'MMMP7'],
                'avp_title' => 'AVP Perencanaan & Pengendalian Pemeliharaan',
                'vp_title' => 'VP Pemeliharaan',
                'is_pilot' => true,
            ],
            [
                'name' => 'Operasi Pabrik',
                'requisitioner_aliases' => ['OP P1A', 'OP P2', 'OP P3', 'OP P4', 'OP P5', 'OPERASI P5', 'OPERASI PABRIK', 'PABRIK 6'],
                'avp_title' => 'AVP Operasi Pabrik',
                'vp_title' => 'VP Operasi',
                'is_pilot' => true,
            ],
            [
                'name' => 'Laboratorium',
                'requisitioner_aliases' => ['LAB', 'LABORATORIUM'],
                'avp_title' => 'AVP Laboratorium',
                'vp_title' => 'VP Pengendalian Proses & Lab',
                'is_pilot' => true,
            ],
            [
                'name' => 'Rendal TA (Area Lain / All Area)',
                'requisitioner_aliases' => ['RENDAL-TAP1A', 'RENDAL-TAP2', 'RENDAL-TAP3', 'RENDAL-TAP4', 'RENDAL TA', 'HAR1-TAP2'],
                'avp_title' => 'AVP Rendal TA',
                'vp_title' => 'VP Pemeliharaan / TA',
                'is_pilot' => false,
            ],
            [
                'name' => 'Bengkel & Mekanik',
                'requisitioner_aliases' => ['BENGKEL-TA1A', 'BENGKEL-TAP2', 'BENGKEL-TAP4', 'BENGKEL-TAP6', 'BENGKEL', 'MEKANIK-P1A', 'MEKANIK-TA1A', 'MEKANIK-TAP4', 'MEKANIK'],
                'avp_title' => 'AVP Bengkel & Mekanik',
                'vp_title' => 'VP Pemeliharaan',
                'is_pilot' => false,
            ],
            [
                'name' => 'Listrik & Instrumen',
                'requisitioner_aliases' => ['LISTRIK-TA1A', 'LIST TA PABRIK', 'LISTRIK', 'INSTRUMEN'],
                'avp_title' => 'AVP Listrik & Instrumen',
                'vp_title' => 'VP Pemeliharaan',
                'is_pilot' => false,
            ],
            [
                'name' => 'K3 & Manajemen Aset',
                'requisitioner_aliases' => ['K3', 'MANSET'],
                'avp_title' => 'AVP K3 / Manajemen Aset',
                'vp_title' => 'VP K3 / Umum',
                'is_pilot' => false,
            ],
        ];

        foreach ($units as $data) {
            RequestingUnit::updateOrCreate(
                ['name' => $data['name']],
                [
                    'requisitioner_aliases' => $data['requisitioner_aliases'],
                    'avp_title' => $data['avp_title'],
                    'vp_title' => $data['vp_title'],
                    'is_pilot' => $data['is_pilot'],
                    'is_active' => true,
                ]
            );
        }
    }
}

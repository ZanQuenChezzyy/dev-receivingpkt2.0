<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeLegacyDataCommand extends Command
{
    protected $signature = 'app:migrate-legacy-merge';
    protected $description = 'Safely merge data from legacy database to active production database mapping IDs.';

    protected $mapUsers = [];
    protected $mapLocations = [];
    protected $mapPo = [];
    protected $mapDo = [];
    protected $mapDoDetails = [];

    public function handle()
    {
        ini_set('memory_limit', '-1'); // Just in case, allow unlimited memory for CLI
        
        $this->info('Starting legacy data MERGE (Safe ID Mapping)...');

        try {
            $oldDb = DB::connection('mysql_old');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to mysql_old: ' . $e->getMessage());
            return;
        }

        try {
            DB::beginTransaction();

            // Auto cleanup duplicates (if the script was previously run multiple times without checks)
            $this->info('Cleaning up any accidentally duplicated details from previous runs...');
            DB::statement('DELETE t1 FROM delivery_order_receipt_details t1 INNER JOIN delivery_order_receipt_details t2 WHERE t1.id > t2.id AND t1.delivery_order_receipt_id = t2.delivery_order_receipt_id AND t1.item_no = t2.item_no AND t1.purchase_order_issued_id = t2.purchase_order_issued_id');
            DB::statement('DELETE t1 FROM material_issue_details t1 INNER JOIN material_issue_details t2 WHERE t1.id > t2.id AND t1.material_issue_id = t2.material_issue_id AND t1.delivery_order_receipt_detail_id = t2.delivery_order_receipt_detail_id');
            DB::statement('DELETE t1 FROM transmittal_items t1 INNER JOIN transmittal_items t2 WHERE t1.id > t2.id AND t1.transmittal_id = t2.transmittal_id AND t1.delivery_order_receipt_id = t2.delivery_order_receipt_id');

            $this->mergeUsers($oldDb);
            $this->mergeLocationReceivings($oldDb);
            $this->mergePurchaseOrders($oldDb);
            $this->mergeDeliveryOrders($oldDb);
            $this->mergeDeliveryOrderDetails($oldDb);
            $this->mergeMaterialIssues($oldDb);
            $this->mergeTransmittals($oldDb);
            $this->mergeMonitoringNpks($oldDb);

            DB::commit();
            $this->info('All merges completed successfully without overwriting IDs!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Merge failed! All changes have been UNDONE. Error: ' . $e->getMessage());
        }
    }

    private function mergeUsers($oldDb)
    {
        $this->info('Merging Users...');
        $oldDb->table('users')->orderBy('id')->chunk(500, function ($users) {
            foreach ($users as $user) {
                $existing = DB::table('users')->where('email', $user->email)->first();
                if ($existing) {
                    $this->mapUsers[$user->id] = $existing->id;
                } else {
                    $newId = DB::table('users')->insertGetId([
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $user->password,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ]);
                    $this->mapUsers[$user->id] = $newId;
                }
            }
        });
    }

    private function mergeLocationReceivings($oldDb)
    {
        $this->info('Merging Location Receivings...');
        if (!Schema::connection('mysql_old')->hasTable('locations')) return;
        
        $oldDb->table('locations')->orderBy('id')->chunk(500, function ($locations) {
            foreach ($locations as $loc) {
                $existing = DB::table('location_receivings')->where('name', $loc->name)->first();
                if ($existing) {
                    $this->mapLocations[$loc->id] = $existing->id;
                } else {
                    $newId = DB::table('location_receivings')->insertGetId([
                        'name' => $loc->name,
                        'created_at' => $loc->created_at ?? null,
                        'updated_at' => $loc->updated_at ?? null,
                    ]);
                    $this->mapLocations[$loc->id] = $newId;
                }
            }
        });
    }

    private $existingPosCache = null;

    private function mergePurchaseOrders($oldDb)
    {
        $this->info('Merging Purchase Orders...');

        if ($this->existingPosCache === null) {
            $this->info('Caching existing POs for faster duplicate checking...');
            $existing = DB::table('purchase_order_issueds')->select('id', 'purchase_order_no', 'item_no')->get();
            $this->existingPosCache = [];
            foreach ($existing as $e) {
                $this->existingPosCache[$e->purchase_order_no . '-' . $e->item_no] = $e->id;
            }
        }

        $oldDb->table('purchase_order_terbits')->orderBy('id')->chunk(500, function ($pos) {
            foreach ($pos as $po) {
                $cacheKey = $po->purchase_order_no . '-' . $po->item_no;
                if (isset($this->existingPosCache[$cacheKey])) {
                    $this->mapPo[$po->id] = $this->existingPosCache[$cacheKey];
                } else {
                    $newId = DB::table('purchase_order_issueds')->insertGetId([
                        'purchase_order_and_item' => $po->purchase_order_and_item,
                        'material_type' => null,
                        'mrp_type' => $po->mrp_type ?? '',
                        'purchase_order_no' => $po->purchase_order_no,
                        'item_no' => $po->item_no,
                        'material_code' => $po->material_code,
                        'aac' => $po->aac,
                        'abc_indicator' => $po->abc_indicator,
                        'description' => $po->description,
                        'qty_po' => floatval($po->qty_po),
                        'uoi' => $po->uoi,
                        'vendor_id' => $po->vendor,
                        'vendor_name' => $po->vendor_id_name,
                        'date_create' => $po->date_create ?? now(),
                        'delivery_date_po' => $po->delivery_date_po,
                        'po_status' => $po->po_status,
                        'incoterm' => $po->incoterm,
                        'currency' => 'IDR',
                        'net_price' => 0,
                        'total_amount_in_lc' => floatval($po->total_amount_in_lc),
                        'requisitioner' => '',
                        'created_at' => $po->created_at,
                        'updated_at' => $po->updated_at,
                    ]);
                    $this->mapPo[$po->id] = $newId;
                    $this->existingPosCache[$cacheKey] = $newId;
                }
            }
        });
    }

    private function mergeDeliveryOrders($oldDb)
    {
        $this->info('Merging Delivery Orders...');
        if (!Schema::connection('mysql_old')->hasTable('delivery_order_receipts')) return;

        $oldDb->table('delivery_order_receipts')->orderBy('id')->chunk(500, function ($dors) {
            foreach ($dors as $dor) {
                $existing = null;
                if (!empty($dor->do_code)) {
                    $existing = DB::table('delivery_order_receipts')->where('document_code', $dor->do_code)->first();
                }

                if ($existing) {
                    $this->mapDo[$dor->id] = $existing->id;
                    
                    // Update if missing in production
                    if (empty($existing->post_103) && !empty($dor->post_103) || empty($existing->description) && !empty($dor->keterangan)) {
                        DB::table('delivery_order_receipts')
                            ->where('id', $existing->id)
                            ->update([
                                'post_103' => $dor->post_103 ?? $existing->post_103,
                                'description' => $dor->keterangan ?? $existing->description,
                            ]);
                    }
                } else {
                    $receivedBy = $this->mapUsers[$dor->received_by ?? 1] ?? 1;
                    $createdBy = $this->mapUsers[$dor->created_by ?? 1] ?? 1;

                    $newId = DB::table('delivery_order_receipts')->insertGetId([
                        'delivery_order_no' => $dor->nomor_do ?? '',
                        'document_code' => $dor->do_code ?? null,
                        'received_date' => $dor->received_date ?? now(),
                        'received_by' => $receivedBy,
                        'created_by' => $createdBy,
                        'stage' => $dor->tahapan ?? null,
                        'status' => 'Diterima',
                        'post_103' => $dor->post_103 ?? null,
                        'description' => $dor->keterangan ?? null,
                        'created_at' => $dor->created_at ?? null,
                        'updated_at' => $dor->updated_at ?? null,
                    ]);
                    $this->mapDo[$dor->id] = $newId;
                }
            }
        });
    }

    private function mergeDeliveryOrderDetails($oldDb)
    {
        $this->info('Merging Delivery Order Details...');
        if (!Schema::connection('mysql_old')->hasTable('delivery_order_receipt_details')) return;

        $oldDb->table('delivery_order_receipt_details')->orderBy('id')->chunk(500, function ($details) use ($oldDb) {
            foreach ($details as $detail) {
                $parentDorId = $this->mapDo[$detail->delivery_order_receipt_id] ?? null;
                if (!$parentDorId) continue;

                $poId = 1;
                $parentDorOld = $oldDb->table('delivery_order_receipts')->where('id', $detail->delivery_order_receipt_id)->first();
                if ($parentDorOld && isset($this->mapPo[$parentDorOld->purchase_order_terbit_id])) {
                    $poId = $this->mapPo[$parentDorOld->purchase_order_terbit_id];
                }

                $existingDetail = DB::table('delivery_order_receipt_details')
                    ->where('delivery_order_receipt_id', $parentDorId)
                    ->where('item_no', $detail->item_no ?? 1)
                    ->where('purchase_order_issued_id', $poId)
                    ->first();
                
                if ($existingDetail) {
                    $this->mapDoDetails[$detail->id] = $existingDetail->id;
                    continue;
                }
                
                $locId = $this->mapLocations[$detail->location_id ?? 1] ?? 1;

                $newId = DB::table('delivery_order_receipt_details')->insertGetId([
                    'delivery_order_receipt_id' => $parentDorId,
                    'purchase_order_issued_id' => $poId,
                    'item_no' => $detail->item_no ?? 1,
                    'quantity' => floatval($detail->quantity ?? 0),
                    'material_code' => $detail->material_code ?? null,
                    'description' => $detail->description ?? null,
                    'uoi' => $detail->uoi ?? null,
                    'mrp_type' => $detail->mrp_type ?? null,
                    'aac' => $detail->aac ?? null,
                    'abc_indicator' => $detail->abc_indicator ?? null,
                    'total_amount_snapshot' => floatval($detail->total_amount_in_lc ?? 0),
                    'location_id' => $locId,
                    'created_at' => $detail->created_at ?? null,
                    'updated_at' => $detail->updated_at ?? null,
                ]);
                $this->mapDoDetails[$detail->id] = $newId;
            }
        });
    }

    private function mergeMaterialIssues($oldDb)
    {
        $this->info('Merging Material Issues...');
        if (Schema::connection('mysql_old')->hasTable('material_issued_requests')) {
            $oldDb->table('material_issued_requests')->orderBy('id')->chunk(500, function ($rows) use ($oldDb) {
                foreach ($rows as $row) {
                    $mirNo = $row->mir_no ?? ('LEGACY-MIR-' . $row->id);
                    $existing = DB::table('material_issues')->where('mir_number', $mirNo)->first();
                    if ($existing) continue; 

                    $attachment = $oldDb->table('material_issued_request_attachments')
                                        ->where('material_issued_request_id', $row->id)
                                        ->first();

                    $poId = isset($this->mapPo[$row->purchase_order_terbit_id]) ? $this->mapPo[$row->purchase_order_terbit_id] : null;

                    $newId = DB::table('material_issues')->insertGetId([
                        'jenis_mir' => 'Manual',
                        'image_path' => $attachment ? $attachment->file_path : null,
                        'mir_number' => $mirNo,
                        'tanggal' => $row->tanggal ?? now(),
                        'purchase_order_issued_id' => $poId,
                        'no_hp' => '',
                        'no_reservasi' => $row->reservation_no ?? null,
                        'departemen' => $row->department ?? '',
                        'bagian' => '',
                        'no_jor_wo' => $row->jor_no ?? null,
                        'digunakan_untuk' => $row->used_for ?? '',
                        'no_alat' => $row->equipment_no ?? null,
                        'kode_biaya' => $row->cost_center ?? null,
                        'diminta_oleh' => $row->requested_by ?? null,
                        'npk' => null,
                        'disetujui_oleh' => null,
                        'diketahui_oleh' => null,
                        'diserahkan_oleh' => $row->handed_over_by ?? null,
                        'diterima_oleh' => null,
                        'created_by' => $this->mapUsers[$row->created_by ?? 1] ?? null,
                        'created_at' => $row->created_at ?? null,
                        'updated_at' => $row->updated_at ?? null,
                    ]);

                    if (Schema::connection('mysql_old')->hasTable('material_issued_request_details')) {
                        $details = $oldDb->table('material_issued_request_details')
                            ->where('material_issued_request_id', $row->id)
                            ->get();
                        
                        $insertDetails = [];
                        foreach ($details as $d) {
                            $newDoDetailId = $this->mapDoDetails[$d->delivery_order_receipt_detail_id ?? 0] ?? null;
                            $insertDetails[] = [
                                'material_issue_id' => $newId,
                                'delivery_order_receipt_detail_id' => $newDoDetailId,
                                'diminta' => floatval($d->requested_qty ?? 0),
                                'diserahkan' => floatval($d->issued_qty ?? 0),
                                'boh' => 0,
                                'stage_when_issued' => null,
                                'created_at' => $d->created_at ?? null,
                                'updated_at' => $d->updated_at ?? null,
                            ];
                        }
                        if (count($insertDetails) > 0) {
                            DB::table('material_issue_details')->insert($insertDetails);
                        }
                    }
                }
            });
        }
    }

    private function mergeTransmittals($oldDb)
    {
        $this->info('Merging Transmittals...');

        if (Schema::connection('mysql_old')->hasTable('transmittal_kirims')) {
            $oldDb->table('transmittal_kirims')->orderBy('id')->chunk(500, function ($rows) {
                // Group them by date
                $groupedKirims = collect($rows)->groupBy(function($item) {
                    if (!empty($item->tanggal_kirim)) return \Carbon\Carbon::parse($item->tanggal_kirim)->format('Y-m-d');
                    if (!empty($item->created_at)) return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
                    return '1970-01-01';
                });

                foreach ($groupedKirims as $date => $groupRows) {
                    $no = 'TRM-' . str_replace('-', '', $date) . '-LEGACY-K-' . crc32($date);
                    
                    $existing = DB::table('transmittals')->where('transmittal_no', $no)->first();
                    if ($existing) continue;

                    $newId = DB::table('transmittals')->insertGetId([
                        'transmittal_no' => $no,
                        'type' => 'kirim',
                        'destination' => $groupRows->first()->qc_destination ?? 'ISTEK',
                        'created_by' => $this->mapUsers[$groupRows->first()->created_by ?? 1] ?? 1,
                        'created_at' => $groupRows->first()->created_at ?? $date,
                        'updated_at' => $groupRows->first()->updated_at ?? null,
                    ]);
                    
                    $insertItems = [];
                    foreach ($groupRows as $row) {
                        if (!empty($row->delivery_order_receipt_id)) {
                            $newDoId = $this->mapDo[$row->delivery_order_receipt_id] ?? null;
                            if ($newDoId) {
                                $insertItems[] = [
                                    'transmittal_id' => $newId,
                                    'delivery_order_receipt_id' => $newDoId,
                                    'status' => 'Kirim',
                                    'created_at' => $row->created_at ?? null,
                                    'updated_at' => $row->updated_at ?? null,
                                ];
                            }
                        }
                    }
                    if (!empty($insertItems)) DB::table('transmittal_items')->insert($insertItems);
                }
            });
        }

        if (Schema::connection('mysql_old')->hasTable('transmittal_kembalis')) {
            $oldDb->table('transmittal_kembalis')->orderBy('id')->chunk(500, function ($rows) use ($oldDb) {
                foreach ($rows as $row) {
                    $dateStr = '19700101';
                    if (!empty($row->tanggal_kembali)) $dateStr = \Carbon\Carbon::parse($row->tanggal_kembali)->format('Ymd');
                    elseif (!empty($row->created_at)) $dateStr = \Carbon\Carbon::parse($row->created_at)->format('Ymd');

                    $no = 'TRM-' . $dateStr . '-LEGACY-B-' . $row->id;
                    $existing = DB::table('transmittals')->where('transmittal_no', $no)->first();
                    if ($existing) continue;

                    $newId = DB::table('transmittals')->insertGetId([
                        'transmittal_no' => $no,
                        'type' => 'kembali',
                        'destination' => 'ISTEK',
                        'created_by' => $this->mapUsers[$row->created_by ?? 1] ?? 1,
                        'created_at' => $row->created_at ?? null,
                        'updated_at' => $row->updated_at ?? null,
                    ]);

                    $oldDetails = $oldDb->table('transmittal_kembali_details')->where('transmittal_kembali_id', $row->id)->get();
                    $insertItems = [];
                    foreach ($oldDetails as $detail) {
                        if (!empty($detail->delivery_order_receipt_id)) {
                            $newDoId = $this->mapDo[$detail->delivery_order_receipt_id] ?? null;
                            if ($newDoId) {
                                $insertItems[] = [
                                    'transmittal_id' => $newId,
                                    'delivery_order_receipt_id' => $newDoId,
                                    'status' => 'Kembali',
                                    'created_at' => $detail->created_at ?? null,
                                    'updated_at' => $detail->updated_at ?? null,
                                ];
                            }
                        }
                    }
                    if (!empty($insertItems)) DB::table('transmittal_items')->insert($insertItems);
                }
            });
        }
    }

    private function mergeMonitoringNpks($oldDb)
    {
        $this->info('Merging Monitoring NPKs...');
        if (Schema::connection('mysql_old')->hasTable('npk_monitorings')) {
            $oldDb->table('npk_monitorings')->orderBy('id')->chunk(500, function ($rows) use ($oldDb) {
                foreach ($rows as $row) {
                    $poId = 1;
                    if (Schema::connection('mysql_old')->hasTable('npk_monitoring_details')) {
                        $detail = $oldDb->table('npk_monitoring_details')->where('npk_monitoring_id', $row->id)->first();
                        if ($detail && isset($detail->purchase_order_terbit_id) && isset($this->mapPo[$detail->purchase_order_terbit_id])) {
                            $poId = $this->mapPo[$detail->purchase_order_terbit_id];
                        }
                    }

                    $existing = DB::table('monitoring_npks')
                        ->where('created_at', $row->created_at)
                        ->where('delivery_oder_number', $row->nomor_do)
                        ->first();

                    $data = [
                        'purchase_order_terbit_id' => $poId,
                        'location_id' => $this->mapLocations[$row->location_id ?? 1] ?? 1,
                        'delivery_oder_number' => $row->nomor_do ?? null,
                        'sample_receivied_date' => $row->tanggal_kedatangan_sample ?? null,
                        'stage' => $row->tahapan ?? null,
                        'delivery_oder_delivery_date' => $row->tanggal_do_dikirim ?? null,
                        'purchase_order_103_date' => $row->tanggal_103 ?? null,
                        'received_date' => $row->tanggal_penerimaan ?? null,
                        'purchase_order_status' => $row->status_po ?? null,
                        'purchase_order_status_a_date' => $row->status_po_date ?? null,
                        'purchase_order_status_b_date' => $row->status_po_b_date ?? null,
                        'purchase_order_status_a_files' => $row->status_po_files ?? null,
                        'laprima_date' => $row->tanggal_laprima ?? null,
                        'coa_date' => $row->tanggal_coa ?? null,
                        'coa_files' => $row->coa_files ?? null,
                        'doc_status' => 'Outstanding',
                        'created_by' => $this->mapUsers[$row->created_by ?? 1] ?? 1,
                        'created_at' => $row->created_at ?? null,
                        'updated_at' => $row->updated_at ?? null,
                    ];

                    if ($existing) {
                        DB::table('monitoring_npks')->where('id', $existing->id)->update($data);
                        $newId = $existing->id;
                    } else {
                        $newId = DB::table('monitoring_npks')->insertGetId($data);
                    }

                    if (Schema::connection('mysql_old')->hasTable('npk_monitoring_details')) {
                        $details = $oldDb->table('npk_monitoring_details')->where('npk_monitoring_id', $row->id)->get();
                        foreach ($details as $d) {
                            $existingDetail = DB::table('monitoring_npk_details')
                                ->where('monitoring_npk_id', $newId)
                                ->where('item_no', $d->item_no ?? 1)
                                ->first();

                            if (!$existingDetail) {
                                DB::table('monitoring_npk_details')->insert([
                                    'monitoring_npk_id' => $newId,
                                    'item_no' => $d->item_no ?? 1,
                                    'material_code' => $d->material_code ?? null,
                                    'description' => $d->description ?? null,
                                    'quantity' => floatval($d->qty ?? 0),
                                    'uoi' => $d->uoi ?? null,
                                    'location_id' => 1,
                                    'is_qty_tolerance' => $d->is_qty_tolerance ?? 0,
                                    'created_at' => $d->created_at ?? null,
                                    'updated_at' => $d->updated_at ?? null,
                                ]);
                            }
                        }
                    }
                }
            });
        }
    }
}

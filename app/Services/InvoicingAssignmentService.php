<?php

namespace App\Services;

use App\Models\InvoicingReceipt;
use App\Models\PicMaterialAssignment;
use App\Models\User;

class InvoicingAssignmentService
{
    /**
     * @var array<int, int>|null [user_id => current_workload]
     */
    protected ?array $staffWorkloads = null;

    /**
     * Inisialisasi daftar beban kerja awal untuk semua user dengan role 'Staff Invoice'.
     */
    public function initializeWorkloads(): void
    {
        $staffUsers = User::role('Staff Invoice')
            ->withCount(['invoicingReceipts as active_count' => function ($q) {
                $q->whereIn('status', ['Menunggu Verifikasi', 'Terverifikasi']);
            }])
            ->orderBy('active_count', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->staffWorkloads = [];
        foreach ($staffUsers as $staff) {
            $this->staffWorkloads[$staff->id] = (int) $staff->active_count;
        }
    }

    /**
     * Dapatkan ID user Staff Invoice berikutnya dengan beban kerja terendah (Round-Robin / Fair Distribution).
     */
    public function getNextStaffInvoiceId(): ?int
    {
        if ($this->staffWorkloads === null) {
            $this->initializeWorkloads();
        }

        if (empty($this->staffWorkloads)) {
            return null;
        }

        // Urutkan berdasarkan beban kerja terendah (ascending). Jika beban kerja sama, urutkan berdasarkan user_id (ascending)
        uksort($this->staffWorkloads, function ($a, $b) {
            $diff = $this->staffWorkloads[$a] - $this->staffWorkloads[$b];

            return $diff !== 0 ? $diff : ($a <=> $b);
        });
        $selectedUserId = array_key_first($this->staffWorkloads);

        // Tambah beban kerja untuk user yang terpilih dalam sesi pemrosesan ini
        $this->staffWorkloads[$selectedUserId]++;

        return $selectedUserId;
    }

    /**
     * Dapatkan ID user berikutnya dengan beban kerja terendah di antara grup user tertentu (Sub-group Round-Robin).
     *
     * @param  array<int>  $candidateUserIds
     */
    public function getNextStaffInvoiceIdAmong(array $candidateUserIds): ?int
    {
        if (empty($candidateUserIds)) {
            return null;
        }

        if ($this->staffWorkloads === null) {
            $this->initializeWorkloads();
        }

        // Pastikan setiap user dalam kandidat terdaftar di workloads
        foreach ($candidateUserIds as $id) {
            if (! isset($this->staffWorkloads[$id])) {
                $this->staffWorkloads[$id] = 0;
            }
        }

        // Ambil hanya workload milik user dalam candidateUserIds
        $subWorkloads = array_intersect_key($this->staffWorkloads, array_flip($candidateUserIds));

        if (empty($subWorkloads)) {
            return $candidateUserIds[0];
        }

        uksort($subWorkloads, function ($a, $b) use ($subWorkloads) {
            $diff = $subWorkloads[$a] - $subWorkloads[$b];

            return $diff !== 0 ? $diff : ($a <=> $b);
        });

        $selectedUserId = array_key_first($subWorkloads);

        // Tambah workload di master array
        $this->staffWorkloads[$selectedUserId]++;

        return $selectedUserId;
    }

    /**
     * Tentukan PIC untuk dokumen Invoicing:
     * - Jika material_type kosong, null, atau 'none' -> Bagi rata ke Staff Invoice (Fair Round-Robin).
     * - Jika material_type spesifik (ZSP, ZFP, ZPM, ZRM, ZSM):
     *     - Jika mode 'user':
     *         - Pertama, cek apakah ada conditional_rules (aturan pengecualian kode material / keyword deskripsi).
     *           Jika cocok, tugaskan ke user_ids aturan tersebut (dibagi rata jika > 1).
     *         - Jika tidak ada aturan tambahan yang cocok, fallback ke PIC utama (users).
     *     - Jika mode 'free_pool' -> dibagikan secara merata ke seluruh pengguna ber-role Staff Invoice.
     * - Jika rule tidak ditemukan -> Bagi rata ke Staff Invoice.
     *
     * @param  string|array<string>|null  $materialCode
     * @param  string|array<string>|null  $description
     */
    public function determinePicId(?string $materialType, string|array|null $materialCode = null, string|array|null $description = null): ?int
    {
        $cleanType = trim((string) $materialType);
        $isNoneOrEmpty = empty($cleanType) || in_array(strtolower($cleanType), ['none', 'null', '-', 'kosong'], true);

        if ($isNoneOrEmpty) {
            return $this->getNextStaffInvoiceId();
        }

        $rule = PicMaterialAssignment::with('users')->where('material_type', $cleanType)->first();

        if ($rule) {
            if ($rule->assignment_mode === 'user') {
                // 1. Evaluasi Aturan Tambahan (Conditional Rules) jika ada
                if (! empty($rule->conditional_rules) && is_array($rule->conditional_rules)) {
                    foreach ($rule->conditional_rules as $cRule) {
                        $criteriaType = $cRule['criteria_type'] ?? 'material_code';
                        $matched = false;

                        if ($criteriaType === 'material_code') {
                            $targetCodesRaw = (string) ($cRule['material_code'] ?? '');
                            $targetCodes = array_filter(array_map('trim', explode(',', $targetCodesRaw)));

                            $codesToCheck = is_array($materialCode) ? $materialCode : [$materialCode];
                            foreach ($codesToCheck as $code) {
                                $cleanCode = trim((string) $code);
                                if ($cleanCode !== '' && in_array($cleanCode, $targetCodes, true)) {
                                    $matched = true;
                                    break;
                                }
                            }
                        } elseif ($criteriaType === 'description_contains') {
                            $keyword = trim((string) ($cRule['description_keyword'] ?? ''));
                            if ($keyword !== '') {
                                $descsToCheck = is_array($description) ? $description : [$description];
                                foreach ($descsToCheck as $desc) {
                                    if ($desc !== null && stripos((string) $desc, $keyword) !== false) {
                                        $matched = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($matched) {
                            $cUserIds = array_values(array_filter(array_map('intval', (array) ($cRule['user_ids'] ?? []))));
                            if (count($cUserIds) === 1) {
                                $singleId = $cUserIds[0];
                                if ($this->staffWorkloads !== null && isset($this->staffWorkloads[$singleId])) {
                                    $this->staffWorkloads[$singleId]++;
                                }

                                return $singleId;
                            }

                            if (count($cUserIds) > 1) {
                                return $this->getNextStaffInvoiceIdAmong($cUserIds);
                            }
                        }
                    }
                }

                // 2. Fallback ke PIC Utama jika tidak ada conditional rule yang cocok
                $candidateIds = $rule->users->pluck('id')->toArray();
                if (empty($candidateIds) && $rule->user_id) {
                    $candidateIds = [$rule->user_id];
                }

                if (count($candidateIds) === 1) {
                    $singleId = $candidateIds[0];
                    if ($this->staffWorkloads !== null && isset($this->staffWorkloads[$singleId])) {
                        $this->staffWorkloads[$singleId]++;
                    }

                    return $singleId;
                }

                if (count($candidateIds) > 1) {
                    return $this->getNextStaffInvoiceIdAmong($candidateIds);
                }

                return null;
            }

            // Mode 'free_pool': bagikan secara merata ke seluruh pengguna ber-role Staff Invoice
            return $this->getNextStaffInvoiceId();
        }

        // Jika material type tidak terdaftar di PicMaterialAssignment, bagikan secara rata
        return $this->getNextStaffInvoiceId();
    }

    /**
     * Membagikan kumpulan dokumen InvoicingReceipt secara merata ke seluruh Staff Invoice.
     *
     * @param  iterable<InvoicingReceipt>  $records
     * @return int Jumlah dokumen yang berhasil dibagikan
     */
    public function distributeEvenly(iterable $records): int
    {
        $count = 0;
        foreach ($records as $record) {
            $nextPicId = $this->getNextStaffInvoiceId();
            if ($nextPicId) {
                $record->update(['pic_id' => $nextPicId]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Dapatkan snapshot workload memori saat ini (berguna untuk testing).
     *
     * @return array<int, int>|null
     */
    public function getStaffWorkloads(): ?array
    {
        return $this->staffWorkloads;
    }

    /**
     * Set workload secara manual (berguna untuk testing).
     *
     * @param  array<int, int>  $workloads
     */
    public function setStaffWorkloads(array $workloads): void
    {
        $this->staffWorkloads = $workloads;
    }
}

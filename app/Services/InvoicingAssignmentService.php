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
     *     - Jika mode 'user' -> ditugaskan ke user-user yang ditentukan (bisa 1 atau lebih, dibagi rata).
     *     - Jika mode 'free_pool' -> dibiarkan null (Open Pool) agar dapat diklaim bebas.
     * - Jika rule tidak ditemukan -> Bagi rata ke Staff Invoice.
     */
    public function determinePicId(?string $materialType): ?int
    {
        $cleanType = trim((string) $materialType);
        $isNoneOrEmpty = empty($cleanType) || in_array(strtolower($cleanType), ['none', 'null', '-', 'kosong']);

        if ($isNoneOrEmpty) {
            return $this->getNextStaffInvoiceId();
        }

        $rule = PicMaterialAssignment::with('users')->where('material_type', $cleanType)->first();

        if ($rule) {
            if ($rule->assignment_mode === 'user') {
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

            // Mode 'free_pool': biarkan open pool (null)
            return null;
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

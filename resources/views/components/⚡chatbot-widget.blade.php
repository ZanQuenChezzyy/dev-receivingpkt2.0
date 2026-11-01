<?php

use App\Models\DeliveryOrderReceipt;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\Attributes\On; // Tambahkan import ini untuk Event Listener

new class extends Component {
    public bool $isOpen = false;
    public bool $isAiActive = false;
    public string $message = '';
    public array $chats = [];
    public bool $isTyping = false; // Tambahkan state untuk animasi loading AI
    public array $suggestedQuestions = [];

    public function mount()
    {
        $this->refreshAiStatus();
        $this->generateSuggestions();
    }

    #[On('ai-system-toggled')]
    public function refreshAiStatus(): void
    {
        try {
            $setting = Setting::where('key', 'ai_system_active')->first();
            $this->isAiActive = $setting ? $setting->value === '1' : false;
        } catch (\Throwable $e) {
            $this->isAiActive = false;
        }
    }

    public function generateSuggestions()
    {
        $pool = [
            "Cek status Penerimaan untuk PO (Nomor PO)",
            "Cari Material Issue untuk PO (Nomor PO)",
            "Cek status Penerimaan Material yang memiliki SN (Nomor SN)",
            "Cek riwayat pengambilan barang PO (Nomor PO)"
        ];
        shuffle($pool);
        // Ambil 2 atau 3 saran pertanyaan secara acak
        $this->suggestedQuestions = array_slice($pool, 0, rand(2, 3));
    }

    public function useSuggestedMessage($text)
    {
        if (! $this->isAiActive) {
            return;
        }

        $this->message = $text;
        // Hanya memasukkan teks ke input, user harus tekan kirim sendiri
    }

    public function toggleChat()
    {
        $this->refreshAiStatus();
        $this->isOpen = !$this->isOpen;
        if (empty($this->chats)) {
            $this->initGreeting();
        }
        $this->dispatch('chat-toggled', isOpen: $this->isOpen);
    }

    public function clearChat(): void
    {
        $this->refreshAiStatus();
        $this->chats = [];
        $this->initGreeting();
        $this->generateSuggestions();
    }

    private function initGreeting(): void
    {
        $this->chats[] = [
            'role' => 'assistant',
            'content' => $this->isAiActive
                ? 'Halo! Saya **ALEX**, Asisten AI Receiving PT Pupuk Kalimantan Timur. Silakan ketik **Nomor PO**, **Nomor Surat Jalan (DO)**, **Stock Number**, atau **Nomor MIR** yang ingin Anda tanyakan.'
                : 'Mohon maaf, **ALEX — Asisten AI Receiving** PT Pupuk Kalimantan Timur saat ini sedang **Offline / Maintenance** (dalam masa pemeliharaan & training). Silakan gunakan menu **Daftar Material** atau **Formulir MIR** untuk pengecekan manual.'
        ];
    }

    public function sendMessage()
    {
        $this->refreshAiStatus();
        if (! $this->isAiActive) {
            $this->chats[] = [
                'role' => 'assistant',
                'content' => 'Mohon maaf, **ALEX — Asisten AI Receiving** saat ini sedang **Offline / Maintenance**. Silakan coba kembali saat layanan telah diaktifkan.'
            ];
            $this->message = '';

            return;
        }

        $this->validate(['message' => 'required|string|max:255']);

        // 1. Simpan pesan user ke variabel lokal
        $userMessage = $this->message;

        // 2. Render pesan user ke UI secepat mungkin & kosongkan input
        $this->chats[] = ['role' => 'user', 'content' => $userMessage];
        $this->message = '';
        $this->isTyping = true; // Nyalakan indikator AI sedang "mengetik"

        // 3. Dispatch event ke frontend agar Livewire segera merender UI,
        // lalu memanggil method fetchAiResponse di request terpisah secara otomatis.
        $this->dispatch('process-ai-response', userMessage: $userMessage);
    }


    private function toolCariDataPenerimaan($kataKunci)
    {
        $searchTerms = preg_split('/\s+/', $kataKunci, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($searchTerms)) return "Tidak ada kata kunci yang dicari.";

        $query = DeliveryOrderReceipt::with([
            'deliveryOrderReceiptDetails.purchaseOrderIssued', 
            'deliveryOrderReceiptDetails.materialIssueDetails.materialIssue',
            'deliveryOrderReceiptDetails.warehouseTransmittalItems.transmittal',
            'deliveryOrderReceiptDetails.locationReceiving',
            'deliveryOrderReceiptDetails.warehouseDestination',
            'qcHistories', 
            'transmittals',
            'grsRdtvItems.grsRdtv',
            'delayLogs'
        ]);

        $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                if (strlen($term) < 3 && !is_numeric($term)) continue;

                $q->orWhere('delivery_order_no', 'LIKE', "%{$term}%")
                    ->orWhereHas('deliveryOrderReceiptDetails', function ($qDetail) use ($term) {
                        $qDetail->where('material_code', 'LIKE', "%{$term}%")
                            ->orWhereHas('purchaseOrderIssued', function ($qPo) use ($term) {
                                $qPo->where('purchase_order_no', 'LIKE', "%{$term}%");
                            });
                    });
            }
        });

        $recentReceipts = $query->latest('received_date')->take(20)->get();

        if ($recentReceipts->isEmpty()) {
            return "Data Penerimaan, PO, atau DO tidak ditemukan untuk kata kunci: {$kataKunci}.";
        }

        \Carbon\Carbon::setLocale('id');

        return $recentReceipts->map(function ($receipt) {
            $details = $receipt->deliveryOrderReceiptDetails->map(function ($detail) {
                $poNumber = $detail->purchaseOrderIssued ? $detail->purchaseOrderIssued->purchase_order_no : 'Tidak Ada PO';
                
                $mirInfo = "";
                if ($detail->materialIssueDetails->isNotEmpty()) {
                    $mirs = $detail->materialIssueDetails->map(function ($mid) {
                        $fase = $mid->stage_when_issued ? " (Fase: {$mid->stage_when_issued})" : "";
                        return "MIR " . ($mid->materialIssue->mir_number ?? 'Draft') . " (Qty Diambil: " . (float)$mid->diserahkan . ", Oleh: " . ($mid->materialIssue->diminta_oleh ?? 'Tidak diketahui') . ", Tgl: " . ($mid->materialIssue->tanggal ? \Carbon\Carbon::parse($mid->materialIssue->tanggal)->isoFormat('D MMM YYYY') : '-') . "){$fase}";
                    })->implode(", ");
                    $mirInfo = " | Riwayat Pengambilan: {$mirs}";
                }

                $warehouseInfo = "";
                if ($detail->warehouseTransmittalItems && $detail->warehouseTransmittalItems->isNotEmpty()) {
                    $whMirs = $detail->warehouseTransmittalItems->map(function ($wh) {
                        $trans = $wh->transmittal;
                        return $trans ? "Transmital Gudang No: {$trans->transmittal_no} (Tipe: {$trans->type}) ke {$trans->destination} pd " . ($trans->created_at ? \Carbon\Carbon::parse($trans->created_at)->isoFormat('D MMM YYYY') : '-') : '';
                    })->filter()->implode(", ");
                    if ($whMirs) {
                        $warehouseInfo = " | Kirim ke Gudang: {$whMirs}";
                    }
                }

                $locationStr = "";
                $locs = [];
                if ($detail->locationReceiving) {
                    $locs[] = "Receiving: " . $detail->locationReceiving->name;
                }
                if ($detail->warehouseDestination) {
                    $locs[] = "Gudang: " . $detail->warehouseDestination->name;
                }
                if (!empty($locs)) {
                    $locationStr = " | Lokasi Penyimpanan: " . implode(' / ', $locs);
                }

                return "- Item: {$detail->description} ({$detail->material_code}) | Qty: " . (float)$detail->quantity . " {$detail->uoi} | PO: {$poNumber}{$locationStr}{$mirInfo}{$warehouseInfo}";
            })->implode("\n");

            if ($receipt->transmittals->isNotEmpty()) {
                $transmittalsList = $receipt->transmittals->sortBy('created_at')->map(function ($trans) {
                    $tanggal = $trans->created_at ? $trans->created_at->isoFormat('D MMMM YYYY') : '-';
                    if ($trans->type === 'Kembali') {
                        return "- Dikembalikan dari QC via Transmittal No: {$trans->transmittal_no} pada {$tanggal}";
                    } else {
                        return "- Dikirim ke {$trans->destination} via Transmittal No: {$trans->transmittal_no} pada {$tanggal}";
                    }
                })->implode("\n");
                $transmittalInfo = "Riwayat Transmittal QC:\n{$transmittalsList}";
            } else {
                $transmittalInfo = "Belum ada riwayat Transmittal.";
            }

            $qcNotes = $receipt->qcHistories->map(function ($qc) {
                return "- [{$qc->created_at->isoFormat('D MMMM YYYY HH:mm')}] Status QC: {$qc->status} | Catatan: " . strip_tags($qc->notes);
            })->implode("\n");

            if (empty($qcNotes)) {
                $qcNotes = "- Tidak ada catatan QC.";
            }

            $pendingInfo = "";
            if ($receipt->status === 'Pending' || $receipt->status === 'Pending (Menunggu Pengajuan Ulang)') {
                $pendingInfo = "\nKendala Saat Ini (Pending): " . ($receipt->delay_reason ?? 'Menunggu Pengajuan Ulang/Revisi') . " | Catatan: " . ($receipt->delay_notes ?? '-');
            } elseif ($receipt->delay_reason) {
                $pendingInfo = "\nRiwayat Kendala Sebelumnya (Sudah Resolusi): " . $receipt->delay_reason;
            }

            if ($receipt->delayLogs && $receipt->delayLogs->isNotEmpty()) {
                $delayHistories = $receipt->delayLogs->map(function ($log) {
                    return "- [{$log->created_at->isoFormat('D MMMM YYYY HH:mm')}] Alasan: {$log->delay_reason} | Catatan: {$log->delay_notes}";
                })->implode("\n");
                $pendingInfo .= "\nRiwayat Kendala / Pengajuan Ulang:\n" . $delayHistories;
            }

            $grsRdtvInfo = "";
            if ($receipt->grsRdtvItems->isNotEmpty()) {
                $grsRdtvList = $receipt->grsRdtvItems->map(function ($item) {
                    $cat = $item->grsRdtv->category ?? 'Unknown';
                    $date = $item->grsRdtv->transaction_date ? \Carbon\Carbon::parse($item->grsRdtv->transaction_date)->isoFormat('D MMMM YYYY') : '-';
                    $reason = $item->reason ? " | Alasan: {$item->reason}" : "";
                    return "- Kategori: {$cat} | Status: {$item->status}{$reason} | Tanggal: {$date}";
                })->implode("\n");
                $grsRdtvInfo = "Status GRS/RDTV:\n{$grsRdtvList}";
            } else {
                $grsRdtvInfo = "Status GRS/RDTV: Belum ada riwayat GRS atau RDTV.";
            }
            
            $post103Date = $receipt->post_103 ? \Carbon\Carbon::parse($receipt->post_103)->isoFormat('D MMMM YYYY') : 'Belum Posting 103';

            return "DO No: {$receipt->delivery_order_no} | Status Utama: {$receipt->status} {$pendingInfo} | Tanggal Terima: {$receipt->received_date->isoFormat('D MMMM YYYY')} | Tgl Posting 103: {$post103Date}\n{$grsRdtvInfo}\nPosisi/Status Dokumen (Transmittal): {$transmittalInfo}\nHistori QC & Masalah:\n{$qcNotes}\nDetail Barang:\n{$details}";
        })->implode("\n\n-------------------\n\n");
    }

    private function toolCariDataPengambilan($kataKunci, $tanggalMulai = null, $tanggalSelesai = null)
    {
        $searchTerms = preg_split('/\s+/', $kataKunci, -1, PREG_SPLIT_NO_EMPTY);
        $mirQuery = \App\Models\MaterialIssue::with([
            'materialIssueDetails.deliveryOrderReceiptDetail.purchaseOrderIssued'
        ]);

        if ($tanggalMulai && $tanggalSelesai) {
            $mirQuery->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
        }

        if (!empty($searchTerms)) {
            $mirQuery->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (strlen($term) < 3 && !is_numeric($term)) continue;

                    $q->orWhere('mir_number', 'LIKE', "%{$term}%")
                      ->orWhere('diminta_oleh', 'LIKE', "%{$term}%")
                      ->orWhere('departemen', 'LIKE', "%{$term}%")
                      ->orWhereHas('materialIssueDetails.deliveryOrderReceiptDetail', function ($qDetail) use ($term) {
                          $qDetail->where('material_code', 'LIKE', "%{$term}%")
                              ->orWhere('description', 'LIKE', "%{$term}%")
                              ->orWhereHas('purchaseOrderIssued', function ($qPo) use ($term) {
                                  $qPo->where('purchase_order_no', 'LIKE', "%{$term}%");
                              });
                      });
                }
            });
        }

        $recentMirs = $mirQuery->latest('tanggal')->take(50)->get();
        
        if ($recentMirs->isEmpty()) {
            return "Data Material Issue (MIR) / Pengambilan Barang tidak ditemukan.";
        }

        return "DATA PENGAMBILAN BARANG (MIR / MATERIAL ISSUE):\n" . $recentMirs->map(function ($mir) {
            $details = $mir->materialIssueDetails->map(function ($detail) {
                $desc = $detail->deliveryOrderReceiptDetail->description ?? 'N/A';
                $matCode = $detail->deliveryOrderReceiptDetail->material_code ?? 'N/A';
                $po = $detail->deliveryOrderReceiptDetail->purchaseOrderIssued->purchase_order_no ?? 'N/A';
                return "- {$desc} ({$matCode}) | Qty Diambil: " . (float)$detail->diserahkan . " | PO: {$po}";
            })->implode("\n");
            
            $tgl = $mir->tanggal ? \Carbon\Carbon::parse($mir->tanggal)->isoFormat('D MMMM YYYY') : '-';
            return "MIR No: {$mir->mir_number} | Tanggal: {$tgl} | Diminta oleh: {$mir->diminta_oleh} (Dept: {$mir->departemen})\nDetail Item yang diambil:\n{$details}";
        })->implode("\n\n-------------------\n\n");
    }

    #[On('process-ai-response')]
    public function fetchAiResponse(string $userMessage)
    {
        $userName = auth()->check() ? auth()->user()->name : 'Tamu';
        $sapaan = auth()->check() ? "Kak {$userName}" : "Kak";
        
        $hour = now()->format('H');
        if ($hour < 11) {
            $waktu = 'pagi';
        } elseif ($hour < 15) {
            $waktu = 'siang';
        } elseif ($hour < 18) {
            $waktu = 'sore';
        } else {
            $waktu = 'malam';
        }
        $currentTime = now()->isoFormat('D MMMM YYYY, HH:mm');

        $systemPrompt = view('prompts.chatbot-system', [
            'userName' => $userName,
            'currentTime' => $currentTime,
            'waktu' => $waktu,
            'sapaan' => $sapaan
        ])->render();

        $ollamaMessages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach ($this->chats as $chat) {
            if (isset($chat['tool_calls'])) {
                $ollamaMessages[] = [
                    'role' => $chat['role'],
                    'content' => $chat['content'] ?? '',
                    'tool_calls' => $chat['tool_calls']
                ];
            } elseif (isset($chat['name'])) {
                $ollamaMessages[] = [
                    'role' => $chat['role'],
                    'name' => $chat['name'],
                    'content' => $chat['content'] ?? ''
                ];
            } else {
                $ollamaMessages[] = [
                    'role' => $chat['role'],
                    'content' => $chat['content'] ?? ''
                ];
            }
        }

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'cari_data_penerimaan',
                    'description' => 'Mencari data penerimaan barang (Delivery Order / PO), Transmittal, QC, dan GRS.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'kata_kunci' => [
                                'type' => 'string',
                                'description' => 'Nomor PO, Nomor DO, atau kode material (gabungkan dengan spasi jika lebih dari satu).'
                            ]
                        ],
                        'required' => ['kata_kunci']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'cari_data_pengambilan',
                    'description' => 'Mencari riwayat data pengambilan barang (Material Issue Request / MIR).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'kata_kunci' => [
                                'type' => 'string',
                                'description' => 'Nomor PO, kode material, nama departemen, atau nomor MIR.'
                            ],
                            'tanggal_mulai' => [
                                'type' => 'string',
                                'description' => 'Tanggal mulai (format: YYYY-MM-DD). Opsional.'
                            ],
                            'tanggal_selesai' => [
                                'type' => 'string',
                                'description' => 'Tanggal selesai (format: YYYY-MM-DD). Opsional.'
                            ]
                        ],
                        'required' => ['kata_kunci']
                    ]
                ]
            ]
        ];

        $ollamaUrl = config('services.ollama.url') . '/api/chat';
        $ollamaModel = 'llama3.1';

        try {
            while (true) {
                $response = Http::withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_RESOLVE => [
                            "ai.receivingpkt.com:443:104.21.73.54"
                        ]
                    ]
                ])
                ->connectTimeout(30)
                ->timeout(120)
                ->post($ollamaUrl, [
                    'model' => $ollamaModel,
                    'messages' => $ollamaMessages,
                    'tools' => $tools,
                    'stream' => true,
                    'options' => [
                        'temperature' => 0.15,
                        'top_p' => 0.4,
                    ]
                ]);

                if (!$response->successful()) {
                    \Illuminate\Support\Facades\Log::error('Ollama API Error: ' . $response->body());
                    $this->chats[] = ['role' => 'assistant', 'content' => 'Maaf, API mengalami gangguan: ' . $response->body()];
                    break;
                }

                $body = $response->toPsrResponse()->getBody();
                $fullReply = '';
                $toolCalls = [];

                while (!$body->eof()) {
                    $line = '';
                    while (!$body->eof()) {
                        $char = $body->read(1);
                        $line .= $char;
                        if ($char === "\n") {
                            break;
                        }
                    }

                    if (trim($line) === '') continue;

                    $data = json_decode($line, true);
                    if ($data) {
                        if (isset($data['message']['content']) && $data['message']['content'] !== '') {
                            $fullReply .= $data['message']['content'];
                            $renderedHtml = str($fullReply)->markdown([
                                'html_input' => 'escape',
                                'allow_unsafe_links' => false,
                            ]);
                            $this->stream(to: 'ai-reply-stream', content: $renderedHtml, replace: true);
                        }

                        if (isset($data['message']['tool_calls']) && !empty($data['message']['tool_calls'])) {
                            foreach ($data['message']['tool_calls'] as $tc) {
                                $toolCalls[] = $tc;
                            }
                        }
                    }
                }

                if (!empty($fullReply) || !empty($toolCalls)) {
                    $assistantMsg = ['role' => 'assistant', 'content' => $fullReply];
                    if (!empty($toolCalls)) {
                        $assistantMsg['tool_calls'] = $toolCalls;
                    }
                    $ollamaMessages[] = $assistantMsg;
                    $this->chats[] = $assistantMsg;
                }

                // If no tools were called, we are done
                if (empty($toolCalls)) {
                    break;
                }

                $this->stream(to: 'ai-reply-stream', content: '<div class="italic text-slate-500 dark:text-slate-400 text-[12px] flex items-center gap-1.5 py-1"><svg class="w-3.5 h-3.5 animate-spin text-[#F47920]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencari data ke database...</div>', replace: true);

                foreach ($toolCalls as $call) {
                    $funcName = $call['function']['name'];
                    $args = $call['function']['arguments'] ?? [];
                    
                    $result = '';
                    if ($funcName === 'cari_data_penerimaan') {
                        $result = $this->toolCariDataPenerimaan($args['kata_kunci'] ?? '');
                    } elseif ($funcName === 'cari_data_pengambilan') {
                        $result = $this->toolCariDataPengambilan($args['kata_kunci'] ?? '', $args['tanggal_mulai'] ?? null, $args['tanggal_selesai'] ?? null);
                    } else {
                        $result = 'Unknown tool';
                    }
                    
                    $toolMsg = ['role' => 'tool', 'name' => $funcName, 'content' => (string)$result];
                    $ollamaMessages[] = $toolMsg;
                    $this->chats[] = $toolMsg;
                }
            }
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ollama Connection Exception: ' . $e->getMessage());
            $this->chats[] = ['role' => 'assistant', 'content' => 'Maaf, koneksi ke AI terputus. Silakan coba lagi.'];
        }

        $this->isTyping = false;
        $this->generateSuggestions();
    }
};
?>
<div id="chatbot-widget-container" class="fixed bottom-6 right-6 z-50 font-sans">
    <!-- Tombol Chat (Floating - Mencolok & Interaktif) -->
    <div class="relative flex items-center gap-3 group" id="tour-chatbot-button">
        <!-- Floating Callout Bubble (Muncul saat chat tertutup) -->
        <div x-show="!$wire.isOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            @click="$wire.toggleChat()"
            class="cursor-pointer select-none relative flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900/95 border-2 {{ $isAiActive ? 'border-[#F47920]/50 shadow-[0_10px_30px_-5px_rgba(244,121,32,0.35)] hover:shadow-[0_14px_35px_-5px_rgba(244,121,32,0.55)] hover:border-[#F47920] animate-bounce' : 'border-amber-500/40 dark:border-slate-700 shadow-lg hover:border-amber-500/70' }} transition-all duration-300 hover:-translate-y-0.5"
            style="animation-duration: 3.2s;">
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                @if($isAiActive)
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                @endif
            </span>
            <div class="text-left leading-tight">
                @if($isAiActive)
                    <span class="block text-[10px] font-black uppercase tracking-wider text-[#F47920]">ALEX • Asisten AI Receiving</span>
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-800 dark:text-white whitespace-nowrap">Tanya ALEX • Cek Kedatangan</span>
                @else
                    <span class="block text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">ALEX • Asisten AI Receiving</span>
                    <span class="block text-xs sm:text-sm font-extrabold text-slate-700 dark:text-slate-300 whitespace-nowrap">Offline • Sedang Maintenance</span>
                @endif
            </div>
            <!-- Segitiga panah ke arah tombol -->
            <div class="w-2.5 h-2.5 bg-white dark:bg-slate-900 border-t-2 border-r-2 {{ $isAiActive ? 'border-[#F47920]/50' : 'border-amber-500/40 dark:border-slate-700' }} rotate-45 -mr-5.5 shrink-0 hidden sm:block"></div>
        </div>

        <!-- Tombol Utama Bulat Bergradasi Mencolok -->
        <div class="relative">
            @if($isAiActive)
                <!-- Efek Ping & Glow Oranye Kuat di belakang tombol saat AI Online -->
                <span x-show="!$wire.isOpen"
                    class="animate-ping absolute -inset-1.5 rounded-full bg-[#F47920] opacity-40 duration-1000"></span>
                <div x-show="!$wire.isOpen"
                    class="absolute -inset-2.5 bg-gradient-to-r from-[#F47920] via-amber-400 to-[#BE5A27] rounded-full blur-xl opacity-60 group-hover:opacity-90 transition duration-500 animate-pulse">
                </div>
            @endif

            <button wire:click="toggleChat"
                aria-label="Buka ALEX Asisten AI Receiving"
                class="relative w-15 h-15 sm:w-16 sm:h-16 rounded-full flex items-center justify-center {{ $isAiActive ? 'bg-gradient-to-br from-[#F47920] via-[#ff852e] to-[#BE5A27] shadow-[0_12px_32px_-4px_rgba(244,121,32,0.85)] hover:shadow-[0_16px_40px_-2px_rgba(244,121,32,1)]' : 'bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 shadow-lg' }} text-white border-2 border-white/80 dark:border-white/30 transition-all duration-300 ease-out transform hover:scale-110 active:scale-95 cursor-pointer">

                <!-- Badge Notifikasi Online/Offline di Sudut Atas Tombol -->
                <span x-show="!$wire.isOpen"
                    class="absolute -top-1 -right-1 flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $isAiActive ? 'bg-emerald-500' : 'bg-amber-500' }} text-white rounded-full border-2 border-white dark:border-slate-900 shadow-md">
                    {{ $isAiActive ? 'ON' : 'OFF' }}
                </span>

                <!-- Icon Robot / Chat (Saat Tertutup) -->
                <svg x-show="!$wire.isOpen" class="w-7 h-7 sm:w-8 sm:h-8 text-white drop-shadow-sm transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8V4H8"></path>
                    <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                    <path d="M2 14h2"></path>
                    <path d="M20 14h2"></path>
                    <path d="M15 13v2"></path>
                    <path d="M9 13v2"></path>
                </svg>

                <!-- Icon Close (Saat Terbuka) -->
                <svg x-show="$wire.isOpen" style="display: none;"
                    class="w-6 h-6 transition-transform duration-300 rotate-90 text-white" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Jendela Chat (Modern Elevated UI) -->
    <div x-show="$wire.isOpen" style="display: none;" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="fixed top-0 left-0 right-0 bottom-0 md:absolute md:top-auto md:left-auto md:bottom-20 md:right-0 w-full md:w-[410px] md:max-w-[410px] h-[100dvh] md:h-[40rem] bg-white/95 dark:bg-[#041829]/95 backdrop-blur-2xl md:rounded-[1.75rem] border-0 md:border-2 {{ $isAiActive ? 'md:border-[#F47920]/35 shadow-[0_25px_70px_-15px_rgba(3,21,37,0.55)]' : 'md:border-amber-500/35 shadow-[0_25px_70px_-15px_rgba(0,0,0,0.55)]' }} overflow-hidden flex flex-col z-[100] md:z-auto" id="tour-chatbot-window">

        <!-- Header Bergradasi Korporat PKT -->
        <div class="relative px-5 py-4 flex items-center justify-between z-20 overflow-hidden {{ $isAiActive ? 'bg-gradient-to-r from-[#031525] via-[#0A4F86] to-[#073B66]' : 'bg-gradient-to-r from-slate-800 via-slate-900 to-slate-800' }} text-white border-b border-white/10 shadow-md">
            <!-- Ambient Glow di Header -->
            <div class="absolute -top-10 -right-10 w-32 h-32 {{ $isAiActive ? 'bg-[#F47920]/35' : 'bg-amber-500/20' }} rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center gap-3.5 relative z-10">
                <div class="relative">
                    <div class="w-11 h-11 rounded-2xl {{ $isAiActive ? 'bg-gradient-to-br from-[#F47920] to-[#BE5A27] shadow-lg shadow-[#F47920]/30' : 'bg-slate-700' }} flex items-center justify-center border border-white/25">
                        <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8V4H8"></path>
                            <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                            <path d="M2 14h2"></path>
                            <path d="M20 14h2"></path>
                            <path d="M15 13v2"></path>
                            <path d="M9 13v2"></path>
                        </svg>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5">
                        @if($isAiActive)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-[#031525]"></span>
                        @else
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500 border-2 border-slate-900"></span>
                        @endif
                    </span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black text-white tracking-tight">ALEX</h3>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider {{ $isAiActive ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30' }}">
                            {{ $isAiActive ? '● Online' : '● Offline' }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-200/85 font-medium tracking-wide mt-0.5">
                        {{ $isAiActive ? 'Asisten AI Receiving • Pupuk Kaltim' : 'Asisten AI Receiving • Maintenance' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 relative z-10">
                <button wire:click="clearChat"
                    title="Mulai Ulang Obrolan"
                    class="text-white/75 hover:text-white transition-all duration-200 bg-white/10 hover:bg-white/20 p-2 rounded-xl active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <button wire:click="toggleChat"
                    title="Tutup Jendela ALEX"
                    class="text-white/75 hover:text-white transition-all duration-200 bg-white/10 hover:bg-rose-500/80 p-2 rounded-xl active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sub-Header Pintasan Cepat (User PKT) -->
        <div class="px-4 py-2 bg-slate-100/90 dark:bg-[#031525]/90 border-b border-slate-200/80 dark:border-white/10 flex items-center justify-between gap-2 text-[11px]">
            <span class="font-bold text-slate-500 dark:text-slate-400 truncate">Pintasan User:</span>
            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('frontend.list-material') }}" wire:navigate class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-white/10 font-bold text-slate-700 dark:text-slate-200 hover:border-[#F47920] hover:text-[#F47920] transition-colors">
                    Daftar Material
                </a>
                <a href="{{ route('frontend.mir.create') }}" wire:navigate class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-white/10 font-bold text-slate-700 dark:text-slate-200 hover:border-[#F47920] hover:text-[#F47920] transition-colors">
                    Formulir MIR
                </a>
            </div>
        </div>

        <!-- Ruang Obrolan -->
        <div class="flex-1 p-4 sm:p-5 overflow-y-auto bg-gradient-to-b from-slate-50/90 via-white to-slate-50/90 dark:from-[#031525]/60 dark:via-[#041829]/80 dark:to-[#031525]/90 flex flex-col gap-5 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700 scrollbar-track-transparent relative" id="chat-container">

            <!-- Tanggal Hari Ini -->
            <div class="flex justify-center">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200/70 dark:bg-slate-800/80 border border-slate-300/60 dark:border-white/10 shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isAiActive ? 'bg-[#F47920]' : 'bg-amber-500' }}"></span>
                    <span class="text-[10px] font-extrabold tracking-wider uppercase text-slate-600 dark:text-slate-300">Percakapan Hari Ini</span>
                </div>
            </div>

            <!-- Banner Peringatan jika AI Sedang Offline / Maintenance -->
            @if(!$isAiActive)
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300/80 dark:border-amber-700/60 text-amber-900 dark:text-amber-200 text-xs space-y-2.5 shadow-sm">
                    <div class="flex items-center gap-2 font-black text-amber-700 dark:text-amber-300">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Mode Pemeliharaan (ALEX Offline)</span>
                    </div>
                    <p class="leading-relaxed text-[11.5px] text-amber-800 dark:text-amber-200/90">
                        ALEX — Asisten AI Receiving sedang dinonaktifkan sementara oleh Administrator. Bagi User PKT, silakan cek kedatangan barang atau ajukan pengambilan melalui tombol di bawah ini:
                    </p>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="{{ route('frontend.list-material') }}" wire:navigate class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-[#F47920] hover:bg-[#BE5A27] text-white font-bold text-[11px] shadow-sm transition-colors text-center">
                            <span>Daftar Material</span>
                        </a>
                        <a href="{{ route('frontend.mir.create') }}" wire:navigate class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-white dark:bg-slate-800 border border-amber-400/60 dark:border-white/15 text-slate-800 dark:text-white font-bold text-[11px] hover:bg-amber-100/50 transition-colors text-center">
                            <span>Formulir MIR</span>
                        </a>
                    </div>
                </div>
            @endif

            @foreach($chats as $chat)
                @if($chat['role'] === 'tool')
                    @continue
                @endif
                
                @if($chat['role'] === 'assistant' && empty($chat['content']))
                    @continue
                @endif

                @if($chat['role'] === 'assistant')
                    <!-- Bubble AI -->
                    <div class="flex items-start gap-2.5 max-w-[94%] group animate-fade-in-up">
                        <div class="relative flex-shrink-0 mt-1">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#0A4F86] to-[#031525] text-white flex items-center justify-center shadow-sm border border-white/20">
                                <svg class="w-4 h-4 text-[#F47920]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 8V4H8"></path>
                                    <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                                    <path d="M2 14h2"></path>
                                    <path d="M20 14h2"></path>
                                    <path d="M15 13v2"></path>
                                    <path d="M9 13v2"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 min-w-0">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#0A4F86] dark:text-sky-400 ml-1">ALEX • Asisten AI Receiving</span>
                            <div class="bg-white dark:bg-slate-800/95 px-4 py-3.5 rounded-2xl rounded-tl-xs shadow-sm border border-slate-200/90 dark:border-white/10 text-[13.5px] text-slate-700 dark:text-slate-200 leading-relaxed ai-markdown-content min-w-0 overflow-hidden">
                                {!! str($chat['content'])->markdown([
                                    'html_input' => 'escape',
                                    'allow_unsafe_links' => false,
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Bubble User -->
                    <div class="flex flex-col items-end w-full animate-fade-in-up">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mr-1 mb-1">Anda</span>
                        <div class="bg-gradient-to-br from-[#F47920] to-[#BE5A27] text-white px-4 py-3 rounded-2xl rounded-tr-xs shadow-md shadow-[#F47920]/20 text-[13.5px] font-medium leading-relaxed max-w-[85%] relative overflow-hidden">
                            <span class="relative z-10 block">{{ $chat['content'] }}</span>
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- Loading Indicator & Streaming Bubble -->
            @if($isTyping)
                <div class="flex items-start gap-2.5 max-w-[94%] group" wire:key="typing-indicator" x-data="{
                    texts: [
                        'Menganalisis pertanyaan...',
                        'Mencari data PO/DO terkait...',
                        'Menyusun riwayat pergerakan material...',
                        'ALEX sedang merangkai balasan...'
                    ],
                    currentIndex: 0,
                    interval: null,
                    isStreaming: false,
                    init() {
                        this.interval = setInterval(() => {
                            this.currentIndex = (this.currentIndex + 1) % this.texts.length;
                        }, 2500);
                        
                        const observer = new MutationObserver(() => {
                            this.isStreaming = true;
                            if (this.interval) clearInterval(this.interval);
                        });
                        observer.observe(this.$refs.streamContainer, { childList: true, subtree: true, characterData: true });
                    },
                    destroy() {
                        if (this.interval) clearInterval(this.interval);
                    }
                }">
                    <div class="relative flex-shrink-0 mt-1">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#0A4F86] to-[#031525] flex items-center justify-center shadow-sm border border-white/20" :class="{'animate-pulse': !isStreaming}">
                            <svg class="w-4 h-4 text-[#F47920]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8V4H8"></path>
                                <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                                <path d="M2 14h2"></path>
                                <path d="M20 14h2"></path>
                                <path d="M15 13v2"></path>
                                <path d="M9 13v2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0 w-full overflow-hidden">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#F47920] ml-1" x-text="isStreaming ? 'ALEX • Mengetik...' : texts[currentIndex]">Berpikir...</span>
                        <div class="bg-white dark:bg-slate-800/95 px-4 py-3.5 rounded-2xl rounded-tl-xs shadow-sm border border-[#F47920]/30 dark:border-[#F47920]/30 text-[13.5px] text-slate-700 dark:text-slate-200 leading-relaxed ai-markdown-content min-w-[60px] w-fit"
                             wire:stream="ai-reply-stream" 
                             x-ref="streamContainer">
                            <div class="flex items-center gap-1.5 h-5 px-1">
                                <span class="w-2 h-2 bg-[#F47920] rounded-full animate-bounce"></span>
                                <span class="w-2 h-2 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                                <span class="w-2 h-2 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Saran Pertanyaan -->
            @if($isAiActive && !$isTyping && !empty($suggestedQuestions))
                <div id="tour-chatbot-suggestions" class="mt-1 mb-1 animate-fade-in-up bg-orange-50/60 dark:bg-slate-800/50 p-3.5 rounded-2xl border border-[#F47920]/20 dark:border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#F47920] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Saran Pertanyaan Cepat
                        </span>
                    </div>
                    <div class="flex flex-col gap-2">
                        @foreach($suggestedQuestions as $suggestion)
                            <button wire:click="useSuggestedMessage('{{ $suggestion }}')"
                                class="px-3.5 py-2.5 bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-white/10 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-[#F47920]/10 hover:border-[#F47920] hover:text-[#F47920] dark:hover:text-[#F89B53] transition-all duration-200 shadow-2xs text-left w-full group flex items-center justify-between gap-2 cursor-pointer">
                                <span class="truncate">{{ $suggestion }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#F47920] group-hover:translate-x-0.5 transition-all shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Input Area -->
        <form wire:submit="sendMessage"
            class="bg-white dark:bg-[#031525] border-t border-slate-200/80 dark:border-white/10 p-4 pb-7 md:pb-4 relative z-20 md:rounded-b-[1.75rem]" id="tour-chatbot-input">
            <div class="relative flex flex-col rounded-2xl bg-slate-50 dark:bg-slate-900/90 border-2 border-slate-200 dark:border-slate-700/80 focus-within:border-[#F47920] dark:focus-within:border-[#F47920] focus-within:ring-4 focus-within:ring-[#F47920]/15 transition-all p-1.5 pl-3.5"
                 x-data="{ 
                    resize() { 
                        $refs.input.style.height = 'auto'; 
                        $refs.input.style.height = Math.min($refs.input.scrollHeight, 120) + 'px'; 
                    } 
                 }"
                 x-init="$watch('$wire.message', () => { setTimeout(() => resize(), 10) })"
            >

                <textarea wire:model="message" x-ref="input" placeholder="{{ $isAiActive ? 'Tanya ALEX seputar nomor PO, DO, atau material...' : 'ALEX Asisten AI Receiving sedang Offline (Maintenance)...' }}"
                    class="w-full pt-2 pb-1.5 pr-2 bg-transparent text-[15px] md:text-[13.5px] text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed font-medium resize-none overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600 max-h-[120px] leading-relaxed"
                    required autocomplete="off" @disabled($isTyping || !$isAiActive)
                    @input="resize()"
                    @keydown.enter.prevent="if(!$event.shiftKey && $wire.message.trim() !== '') { $wire.sendMessage(); $refs.input.style.height = 'auto'; }"
                    rows="1"></textarea>

                <div class="flex justify-between items-center w-full mt-1 pr-0.5">
                    <!-- Branding (Left) -->
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $isAiActive ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 tracking-wide">
                            ALEX • Asisten AI Receiving • <span class="{{ $isAiActive ? 'text-[#F47920]' : 'text-amber-500' }}">{{ $isAiActive ? 'Online' : 'Offline' }}</span>
                        </span>
                    </div>

                    <!-- Button (Right) -->
                    <button type="submit"
                        class="px-3.5 py-2 {{ $isAiActive ? 'bg-gradient-to-r from-[#F47920] to-[#BE5A27] hover:shadow-lg hover:shadow-[#F47920]/30' : 'bg-slate-400 dark:bg-slate-700' }} rounded-xl flex items-center justify-center gap-1.5 text-white text-xs font-extrabold shadow-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95 cursor-pointer"
                        @disabled($isTyping || !$isAiActive)>
                        <span>Kirim</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Script auto-scroll -->
    @script
    <script>
        document.addEventListener('livewire:initialized', () => {
            const container = document.getElementById('chat-container');
            
            Livewire.hook('morph.updated', () => {
                if (container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            });

            if (container) {
                const observer = new MutationObserver(() => {
                    // Only scroll if we're near the bottom to avoid fighting user scroll
                    if (container.scrollHeight - container.scrollTop - container.clientHeight < 150) {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'auto'
                        });
                    }
                });
                observer.observe(container, { childList: true, subtree: true, characterData: true });
            }
        });
    </script>
    @endscript

    <style>
        .ai-markdown-content {
            font-size: 13.5px;
            line-height: 1.6;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .ai-markdown-content p {
            margin-bottom: 0.75rem;
        }

        .ai-markdown-content p:last-child {
            margin-bottom: 0;
        }

        .ai-markdown-content strong {
            color: #F47920;
            font-weight: 700;
        }
        
        /* Dark mode specific for markdown strong */
        .dark .ai-markdown-content strong {
            color: #F89B53;
        }

        .ai-markdown-content ul {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .ai-markdown-content ul li {
            position: relative;
            padding-left: 1.25rem;
            margin-bottom: 0.35rem;
        }

        .ai-markdown-content ul li::before {
            content: "•";
            color: #F47920;
            font-weight: bold;
            font-size: 18px;
            position: absolute;
            left: 0;
            top: -2px;
        }

        .ai-markdown-content hr {
            border-top: 1px dashed #e2e8f0;
            margin: 1rem 0;
        }
        
        .dark .ai-markdown-content hr {
            border-top-color: rgba(255,255,255,0.1);
        }

        /* Mencegah link panjang keluar dari bubble chat */
        .ai-markdown-content a {
            word-break: break-all;
            color: #F47920; /* Warna jingga (orange) untuk link sesuai tema aplikasi */
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 600;
        }

        .dark .ai-markdown-content a {
            color: #F89B53;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
    </style>
</div>
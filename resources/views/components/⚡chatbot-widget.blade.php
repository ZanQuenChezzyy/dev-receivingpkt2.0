<?php

use App\Models\DeliveryOrderReceipt;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\Attributes\On; // Tambahkan import ini untuk Event Listener

new class extends Component {
    public bool $isOpen = false;
    public string $message = '';
    public array $chats = [];
    public bool $isTyping = false; // Tambahkan state untuk animasi loading AI
    public array $suggestedQuestions = [];

    public function mount()
    {
        $this->generateSuggestions();
    }

    public function generateSuggestions()
    {
        $pool = [
            "Cek status Penerimaan untuk PO 53000XXXXX",
            "Cari Material Issue untuk PO 53000XXXXX",
            "Cek status Penerimaan Material yang memiliki SN 60XXXXX",
            "Cek riwayat pengambilan barang PO 53000XXXXX"
        ];
        shuffle($pool);
        // Ambil 2 atau 3 saran pertanyaan secara acak
        $this->suggestedQuestions = array_slice($pool, 0, rand(2, 3));
    }

    public function useSuggestedMessage($text)
    {
        $this->message = $text;
        // Hanya memasukkan teks ke input, user harus tekan kirim sendiri
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if (empty($this->chats)) {
            $this->chats[] = [
                'role' => 'assistant',
                'content' => 'Halo! Saya ALEX, Asisten AI Receiving. Ada yang bisa saya bantu terkait status PO, Delivery Order, atau pengecekan status material?'
            ];
        }
        $this->dispatch('chat-toggled', isOpen: $this->isOpen);
    }

    public function sendMessage()
    {
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
                        return "MIR " . ($mid->materialIssue->mir_number ?? 'Draft') . " (Qty Diambil: " . (float)$mid->diserahkan . ", Oleh: " . ($mid->materialIssue->diminta_oleh ?? 'Tidak diketahui') . ", Tgl: " . ($mid->materialIssue->tanggal ? \Carbon\Carbon::parse($mid->materialIssue->tanggal)->isoFormat('D MMM YYYY') : '-') . ")";
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

            $latestTransmittal = $receipt->transmittals->sortByDesc('created_at')->first();
            $transmittalInfo = $latestTransmittal
                ? "Dikirim ke {$latestTransmittal->destination} via Transmittal No: {$latestTransmittal->transmittal_no} (Tipe: {$latestTransmittal->type}) pada {$latestTransmittal->created_at->isoFormat('D MMMM YYYY')}"
                : "Belum ada riwayat Transmittal.";

            $qcNotes = $receipt->qcHistories->map(function ($qc) {
                return "- [{$qc->created_at->isoFormat('D MMMM YYYY HH:mm')}] Status QC: {$qc->status} | Catatan: " . strip_tags($qc->notes);
            })->implode("\n");

            if (empty($qcNotes)) {
                $qcNotes = "- Belum ada riwayat masalah QC.";
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
            } elseif (isset($chat['tool_name'])) {
                $ollamaMessages[] = [
                    'role' => $chat['role'],
                    'name' => $chat['tool_name'],
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

                // Append assistant's reply and tool calls to history for the next iteration
                if (!empty($fullReply) || !empty($toolCalls)) {
                    $assistantMsg = ['role' => 'assistant', 'content' => $fullReply];
                    if (!empty($toolCalls)) {
                        $assistantMsg['tool_calls'] = $toolCalls;
                    }
                    $ollamaMessages[] = $assistantMsg;
                }

                // If no tools were called, we are done
                if (empty($toolCalls)) {
                    if (!empty($fullReply)) {
                        $this->chats[] = ['role' => 'assistant', 'content' => $fullReply];
                    }
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
                    
                    $ollamaMessages[] = ['role' => 'tool', 'name' => $funcName, 'content' => (string)$result];
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
<div class="fixed bottom-6 right-6 z-50 font-sans">
    <!-- Tombol Chat (Floating) -->
    <div class="relative group" id="tour-chatbot-button">
        <!-- Efek Glow Oranye di belakang tombol -->
        <div x-show="!$wire.isOpen"
            class="absolute -inset-2 bg-[#F47920] rounded-full blur-xl opacity-20 group-hover:opacity-40 transition duration-500 animate-pulse">
        </div>
        <button wire:click="toggleChat"
            class="glass-btn relative w-14 h-14 rounded-full flex items-center justify-center text-[#F47920] transition-all duration-300 ease-out transform hover:scale-105 active:scale-95 group-hover:border-[#F47920]/50">

            <!-- Icon Chat (Tutup) -->
            <svg x-show="!$wire.isOpen" class="w-7 h-7 transition-transform duration-300 group-hover:animate-bounce" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                <path d="M8 12h.01"></path>
                <path d="M12 12h.01"></path>
                <path d="M16 12h.01"></path>
            </svg>

            <!-- Icon Close (Buka) -->
            <svg x-show="$wire.isOpen" style="display: none;"
                class="w-6 h-6 transition-transform duration-300 rotate-90 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Jendela Chat -->
    <div x-show="$wire.isOpen" style="display: none;" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="glass-panel fixed top-0 left-0 right-0 bottom-0 md:absolute md:top-auto md:left-auto md:bottom-20 md:right-0 w-full md:max-w-[380px] h-[100dvh] md:h-[38rem] md:rounded-[1.5rem] overflow-hidden flex flex-col z-[100] md:z-auto" id="tour-chatbot-window">

        <!-- Header -->
        <div class="glass-nav px-6 py-5 flex items-center justify-between relative z-20 overflow-hidden">
            <div class="flex items-center gap-3.5 relative z-10">
                <div class="relative group">
                    <div
                        class="w-11 h-11 bg-white dark:bg-slate-800 rounded-2xl flex items-center justify-center shadow-sm border border-slate-200/80 dark:border-white/5 group-hover:border-[#F47920]/50 transition-all duration-300">
                        <svg class="w-6 h-6 text-[#F47920]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8V4H8"></path>
                            <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                            <path d="M2 14h2"></path>
                            <path d="M20 14h2"></path>
                            <path d="M15 13v2"></path>
                            <path d="M9 13v2"></path>
                        </svg>
                    </div>
                    <span
                        class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-[#031525] rounded-full shadow-sm"></span>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-slate-800 dark:text-white tracking-tight">ALEX</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tracking-wide mt-0.5">Asisten AI Receiving</p>
                </div>
            </div>

            <button wire:click="toggleChat"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all duration-300 relative z-10 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 p-2 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"></path>
                </svg>
            </button>
        </div>

        <!-- Ruang Obrolan -->
        <div class="flex-1 p-5 overflow-y-auto bg-slate-50/50 dark:bg-transparent flex flex-col gap-6 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700 scrollbar-track-transparent relative" id="chat-container">

            <div class="flex justify-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/70 dark:bg-slate-800/50 border border-slate-200/50 dark:border-white/5 shadow-sm backdrop-blur-md">
                    <span class="text-[9px] font-bold tracking-widest uppercase text-slate-500 dark:text-slate-400">Hari ini</span>
                </div>
            </div>

            @foreach($chats as $chat)
                @if($chat['role'] === 'assistant')
                        <!-- Bubble AI -->
                        <div class="flex items-start gap-3 max-w-[92%] group">
                            <div class="relative flex-shrink-0 mt-1">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm border border-slate-200/80 dark:border-white/10 relative z-10">
                                    <svg class="w-4 h-4 text-[#F47920]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 8V4H8"></path>
                                        <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                                        <path d="M2 14h2"></path>
                                        <path d="M20 14h2"></path>
                                        <path d="M15 13v2"></path>
                                        <path d="M9 13v2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div
                                class="bg-white dark:bg-slate-800/80 px-5 py-4 rounded-[1.5rem] rounded-tl-sm shadow-sm border border-slate-200/60 dark:border-white/5 text-[13.5px] text-slate-700 dark:text-slate-300 leading-relaxed ai-markdown-content transition-shadow duration-300 backdrop-blur-md min-w-0 overflow-hidden">
                                {!! str($chat['content'])->markdown([
                                    'html_input' => 'escape',
                                    'allow_unsafe_links' => false,
                                ]) !!}
                            </div>
                        </div>
                @else
                    <!-- Bubble User -->
                    <div class="flex items-end justify-end w-full">
                        <div
                            class="bg-gradient-to-r from-[#F47920] to-[#BE5A27] text-white px-5 py-3.5 rounded-[1.5rem] rounded-tr-sm shadow-md shadow-[#F47920]/20 text-[13.5px] leading-relaxed max-w-[85%] relative overflow-hidden transform hover:-translate-y-0.5 transition-transform duration-300">
                            <span class="relative z-10 block">{{ $chat['content'] }}</span>
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- Loading Indicator & Streaming Bubble -->
            @if($isTyping)
                <div class="flex items-start gap-3 max-w-[92%] group" wire:key="typing-indicator" x-data="{
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
                        <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm border border-slate-200/80 dark:border-white/10 relative z-10" :class="{'animate-pulse bg-slate-200 dark:bg-slate-700': !isStreaming}">
                            <svg x-show="isStreaming" class="w-4 h-4 text-[#F47920]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M12 8V4H8"></path>
                                <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                                <path d="M2 14h2"></path>
                                <path d="M20 14h2"></path>
                                <path d="M15 13v2"></path>
                                <path d="M9 13v2"></path>
                            </svg>
                            <span x-show="!isStreaming" class="w-1.5 h-1.5 bg-slate-400 dark:bg-slate-500 rounded-full"></span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 min-w-0 w-full overflow-hidden">
                        <div class="bg-white dark:bg-slate-800/80 px-5 py-4 rounded-[1.5rem] rounded-tl-sm shadow-sm border border-slate-200/60 dark:border-white/5 text-[13.5px] text-slate-700 dark:text-slate-300 leading-relaxed ai-markdown-content backdrop-blur-md min-w-[50px] w-fit transition-shadow duration-300"
                             wire:stream="ai-reply-stream" 
                             x-ref="streamContainer">
                            <div class="flex items-center gap-1.5 h-6">
                                <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce"></span>
                                <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                                <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                            </div>
                        </div>
                        <span x-show="!isStreaming" class="text-[10px] font-medium text-slate-500 dark:text-slate-400 animate-pulse ml-2 tracking-wide" x-text="texts[currentIndex]">Berpikir...</span>
                    </div>
                </div>
            @endif

            <!-- Saran Pertanyaan -->
            @if(!$isTyping && !empty($suggestedQuestions))
                <div id="tour-chatbot-suggestions" class="mt-2 mb-2 animate-fade-in-up">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 ml-1">Saran:</p>
                    <div class="flex flex-col gap-2">
                        @foreach($suggestedQuestions as $suggestion)
                            <button wire:click="useSuggestedMessage('{{ $suggestion }}')"
                                class="px-3.5 py-2.5 bg-white/60 dark:bg-slate-800/60 border border-slate-200 dark:border-white/5 rounded-xl text-[11.5px] font-medium text-slate-700 dark:text-slate-300 hover:bg-[#F47920]/10 hover:border-[#F47920]/30 hover:text-[#F47920] dark:hover:text-[#F89B53] transition-all duration-300 shadow-sm backdrop-blur-sm text-left w-full hover:-translate-y-0.5 group flex items-start gap-2.5">
                                <svg class="w-3.5 h-3.5 text-[#F47920] opacity-70 group-hover:opacity-100 flex-shrink-0 mt-[2px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                </svg>
                                <span>{{ $suggestion }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Input Area -->
        <form wire:submit="sendMessage"
            class="glass-nav p-5 pb-8 md:pb-5 relative z-20 md:rounded-b-[1.5rem]" id="tour-chatbot-input">
            <div class="glass-input relative flex flex-col rounded-[1.5rem] group p-1.5 pl-4"
                 x-data="{ 
                    resize() { 
                        $refs.input.style.height = 'auto'; 
                        $refs.input.style.height = Math.min($refs.input.scrollHeight, 120) + 'px'; 
                    } 
                 }"
                 x-init="$watch('$wire.message', () => { setTimeout(() => resize(), 10) })"
            >

                <textarea wire:model="message" x-ref="input" placeholder="Tanya DO, PO, atau MIR..."
                    class="w-full pt-3 pb-2 pr-2 bg-transparent text-[16px] md:text-[13.5px] text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed font-medium resize-none overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-600 max-h-[120px] leading-relaxed"
                    required autocomplete="off" @disabled($isTyping)
                    @input="resize()"
                    @keydown.enter.prevent="if(!$event.shiftKey && $wire.message.trim() !== '') { $wire.sendMessage(); $refs.input.style.height = 'auto'; }"
                    rows="1"></textarea>

                <div class="flex justify-between items-center w-full mt-1 pr-0.5 mb-0.5">
                    <!-- Branding (Left) -->
                    <div class="flex items-center gap-1.5 ml-1">
                        <div class="relative flex items-center justify-center group-hover:animate-pulse">
                            <div class="absolute w-3 h-3 bg-[#F47920] rounded-full blur-[3px] opacity-60"></div>
                            <svg class="w-3 h-3 text-[#F47920] relative z-10 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 tracking-wider">Alex Mokondo AI <span class="text-[#F47920]">2.1</span> Pro</span>
                    </div>

                    <!-- Button (Right) -->
                    <button type="submit"
                        class="w-9 h-9 bg-[#F47920] hover:bg-[#E06714] rounded-full flex items-center justify-center text-white shadow-md hover:shadow-[#F47920]/40 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95"
                        @disabled($isTyping)>
                        <svg class="w-4 h-4 translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
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
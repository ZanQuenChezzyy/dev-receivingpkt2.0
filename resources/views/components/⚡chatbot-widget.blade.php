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

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if (empty($this->chats)) {
            $this->chats[] = [
                'role' => 'assistant',
                'content' => 'Halo! Saya ALEX, Asisten AI Receiving. Ada yang bisa saya bantu terkait status PO, Delivery Order, atau pengecekan status material?'
            ];
        }
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

    #[On('process-ai-response')]
    public function fetchAiResponse(string $userMessage)
    {
        // 1. Ekstrak potensi nomor (PO/DO) atau teks dari riwayat pesan user
        // Ambil dari 3 pesan terakhir user agar AI tidak lupa nomor PO jika user melakukan follow-up
        $contextText = '';
        $historyCount = count($this->chats);
        $messagesToCheck = min(6, $historyCount); // Cek 6 baris terakhir (3 pasang user-ai)
        for ($i = $historyCount - 1; $i >= $historyCount - $messagesToCheck; $i--) {
            if (isset($this->chats[$i]) && $this->chats[$i]['role'] === 'user') {
                $contextText .= ' ' . $this->chats[$i]['content'];
            }
        }
        
        // Regex ini akan mengambil semua deretan angka atau kata dari gabungan pesan user
        preg_match_all('/\b[A-Za-z0-9-]+\b/', $contextText, $matches);
        $searchTerms = $matches[0];

        // Mulai Query Dasar
        $query = DeliveryOrderReceipt::with([
            'deliveryOrderReceiptDetails.purchaseOrderIssued', 
            'deliveryOrderReceiptDetails.materialIssueDetails.materialIssue',
            'deliveryOrderReceiptDetails.warehouseTransmittalItems.transmittal',
            'qcHistories', 
            'transmittals',
            'grsRdtvItems.grsRdtv',
            'delayLogs'
        ]);

        // 2. Filter dinamis berdasarkan pesan user
        // Jika ada term pencarian, prioritaskan mencari data spesifik tersebut
        if (!empty($searchTerms)) {
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    // Abaikan kata-kata umum agar query tidak berat
                    if (strlen($term) < 3)
                        continue;

                    $q->orWhere('delivery_order_no', 'LIKE', "%{$term}%")
                        ->orWhereHas('deliveryOrderReceiptDetails', function ($qDetail) use ($term) {
                            $qDetail->where('material_code', 'LIKE', "%{$term}%")
                                ->orWhereHas('purchaseOrderIssued', function ($qPo) use ($term) {
                                    // PERBAIKAN: Ubah po_number menjadi purchase_order_no
                                    $qPo->where('purchase_order_no', 'LIKE', "%{$term}%");
                                });
                        });
                }
            });
        }

        // 3. Tarik Konteks Data dari Database (Batas 3 data spesifik atau terbaru)
        // Kurangi dari 10 ke 3 agar tidak terlalu berat membebani memori AI
        $recentReceipts = $query->latest('received_date')
            ->take(3)
            ->get();

        \Carbon\Carbon::setLocale('id');

        $contextData = $recentReceipts->map(function ($receipt) {
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

                return "- Item: {$detail->description} ({$detail->material_code}) | Qty: " . (float)$detail->quantity . " {$detail->uoi} | PO: {$poNumber}{$mirInfo}{$warehouseInfo}";
            })->implode("\n");

            // Info Transmittal (Posisi Dokumen)
            $latestTransmittal = $receipt->transmittals->sortByDesc('created_at')->first();
            $transmittalInfo = $latestTransmittal
                ? "Dikirim ke {$latestTransmittal->destination} via Transmittal No: {$latestTransmittal->transmittal_no} (Tipe: {$latestTransmittal->type}) pada {$latestTransmittal->created_at->isoFormat('D MMMM YYYY')}"
                : "Belum ada riwayat Transmittal.";

            // Info QC History (Masalah QC)
            $qcNotes = $receipt->qcHistories->map(function ($qc) {
                return "- [{$qc->created_at->isoFormat('D MMMM YYYY HH:mm')}] Status QC: {$qc->status} | Catatan: " . strip_tags($qc->notes);
            })->implode("\n");

            if (empty($qcNotes)) {
                $qcNotes = "- Belum ada riwayat masalah QC.";
            }

            // Info Pending/Delay
            $pendingInfo = "";
            if ($receipt->status === 'Pending' || $receipt->status === 'Pending (Menunggu Pengajuan Ulang)') {
                $pendingInfo = "\nKendala Saat Ini (Pending): " . ($receipt->delay_reason ?? 'Menunggu Pengajuan Ulang/Revisi') . " | Catatan: " . ($receipt->delay_notes ?? '-');
            } elseif ($receipt->delay_reason) {
                $pendingInfo = "\nRiwayat Kendala Sebelumnya (Sudah Resolusi): " . $receipt->delay_reason;
            }

            // Info Riwayat Kendala/Pengajuan Ulang
            if ($receipt->delayLogs && $receipt->delayLogs->isNotEmpty()) {
                $delayHistories = $receipt->delayLogs->map(function ($log) {
                    return "- [{$log->created_at->isoFormat('D MMMM YYYY HH:mm')}] Alasan: {$log->delay_reason} | Catatan: {$log->delay_notes}";
                })->implode("\n");
                $pendingInfo .= "\nRiwayat Kendala / Pengajuan Ulang:\n" . $delayHistories;
            }

            // Info GRS dan RDTV dari tabel GRSRDTV
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

            return "DO No: {$receipt->delivery_order_no} | Status Utama: {$receipt->status} {$pendingInfo} | Tanggal Terima: {$receipt->received_date->isoFormat('D MMMM YYYY')} | Tgl Posting 103: {$post103Date}
{$grsRdtvInfo}
Posisi/Status Dokumen (Transmittal): {$transmittalInfo}
Histori QC & Masalah:
{$qcNotes}
Detail Barang:
{$details}";
        })->implode("\n\n-------------------\n\n");

        // 4. Susun Prompt untuk Gemini
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
            'contextData' => $contextData,
            'sapaan' => $sapaan
        ])->render();

        // Susun format pesan untuk Ollama
        $ollamaMessages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach ($this->chats as $chat) {
            $ollamaMessages[] = [
                'role' => $chat['role'], // Ollama menggunakan 'assistant' dan 'user'
                'content' => $chat['content']
            ];
        }

        // Hit API Ollama Lokal (Bisa dikonfigurasi via .env)
        try {
            $ollamaUrl = config('services.ollama.url') . '/api/chat';
            $ollamaModel = config('services.ollama.model');

            // Mengatur timeout menjadi 120 detik dan memaksa penggunaan IPv4 serta membypass DNS (cURL error 6)
            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_RESOLVE => [
                        "ai.receivingpkt.com:443:104.21.73.54"
                    ]
                ]
            ])
            ->connectTimeout(30)
            ->timeout(120) // Waktu tunggu diset lama agar tidak putus di tengah jalan saat memikirkan jawaban
            ->post($ollamaUrl, [
                'model' => $ollamaModel,
                'messages' => $ollamaMessages,
                'stream' => false,
                'options' => [
                    'temperature' => 0.0, // Temperature 0 agar bot sangat akurat, konsisten, dan tidak halusinasi
                    'top_p' => 0.1,
                ]
            ]);

            if ($response->successful()) {
                $aiReply = $response->json('message.content');
                $this->chats[] = ['role' => 'assistant', 'content' => $aiReply];
            } else {
                \Illuminate\Support\Facades\Log::error('Ollama API Error: ' . $response->body());
                $this->chats[] = ['role' => 'assistant', 'content' => 'Maaf, AI dalam mode tidur, silahkan dicoba lagi nanti'];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ollama Connection Exception: ' . $e->getMessage());
            $this->chats[] = ['role' => 'assistant', 'content' => 'Maaf, AI dalam mode tidur, silahkan dicoba lagi nanti'];
        }

        // 4. Matikan indikator loading setelah mendapatkan balasan
        $this->isTyping = false;
    }
};
?>

<div class="fixed bottom-6 right-6 z-50 font-sans">
    <!-- Tombol Chat (Floating) -->
    <div class="relative group">
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
        class="glass-panel fixed top-0 left-0 right-0 bottom-0 md:absolute md:top-auto md:left-auto md:bottom-20 md:right-0 w-full md:max-w-[380px] h-[100dvh] md:h-[38rem] md:rounded-[1.5rem] overflow-hidden flex flex-col z-[100] md:z-auto">

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

            <!-- Loading Indicator -->
            @if($isTyping)
                <div class="flex items-start gap-3 max-w-[85%]" x-data="{
                    texts: [
                        'Menganalisis pertanyaan...',
                        'Mencari data PO/DO terkait...',
                        'Menyusun riwayat pergerakan material...',
                        'ALEX sedang mengetik balasan...'
                    ],
                    currentIndex: 0,
                    interval: null,
                    init() {
                        this.interval = setInterval(() => {
                            this.currentIndex = (this.currentIndex + 1) % this.texts.length;
                        }, 2500);
                    },
                    destroy() {
                        if (this.interval) clearInterval(this.interval);
                    }
                }">
                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center mt-1 animate-pulse border border-slate-300/50 dark:border-white/5">
                        <span class="w-1.5 h-1.5 bg-slate-400 dark:bg-slate-500 rounded-full"></span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <div class="bg-white dark:bg-slate-800/80 px-5 py-4 rounded-[1.5rem] rounded-tl-sm shadow-sm border border-slate-200/60 dark:border-white/5 flex items-center gap-1.5 backdrop-blur-md w-fit">
                            <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce"></span>
                            <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                            <span class="w-1.5 h-1.5 bg-[#F47920] rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                        </div>
                        <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 animate-pulse ml-2 tracking-wide" x-text="texts[currentIndex]">Berpikir...</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Input Area -->
        <form wire:submit="sendMessage"
            class="glass-nav p-5 pb-8 md:pb-5 relative z-20 md:rounded-b-[1.5rem]">
            <div class="glass-input relative flex items-center rounded-full group">

                <input wire:model="message" type="text" placeholder="Tanya DO, PO, atau MIR..."
                    class="w-full pl-6 pr-14 py-3.5 bg-transparent text-[16px] md:text-[13.5px] text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed rounded-full font-medium"
                    required autocomplete="off" @disabled($isTyping)>

                <button type="submit"
                    class="absolute right-1.5 w-10 h-10 bg-[#F47920] hover:bg-[#E06714] rounded-full flex items-center justify-center text-white shadow-md hover:shadow-[#F47920]/40 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95"
                    @disabled($isTyping)>
                    <svg class="w-4 h-4 translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path>
                    </svg>
                </button>
            </div>

            <!-- Footer Branding -->
            <div class="text-center mt-3.5 flex justify-center items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_5px_rgba(34,197,94,0.5)]"></div>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-widest uppercase">Powered by <span class="text-[#F47920]">Mokondo AI</span></span>
            </div>
        </form>
    </div>

    <!-- Script auto-scroll -->
    @script
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', () => {
                const container = document.getElementById('chat-container');
                if (container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            });
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
            color: #2563eb; /* Warna biru untuk link (bisa disesuaikan) */
            text-decoration: underline;
        }

        .dark .ai-markdown-content a {
            color: #60a5fa;
        }
    </style>
</div>
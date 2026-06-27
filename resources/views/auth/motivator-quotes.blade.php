@php
    $quotes = cache()->remember('motivator_quotes_v3', 3600, function () {
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(5)->get('https://quotes.liupurnomo.com/api/quotes', [
                'category' => 'motivasi',
                'limit' => 90,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                    $result = [];
                    foreach ($data['data'] as $q) {
                        $result[] = [
                            'text' => '"' . $q['text'] . '"',
                            'author' => $q['author']
                        ];
                    }
                    // Acak urutan 30 quotes sebelum disimpan
                    shuffle($result);
                    return $result;
                }
            }
        } catch (\Exception $e) {}
        
        return [
            [ 'text' => '"Kesuksesan bukanlah akhir, kegagalan bukanlah hal yang fatal: Keberanian untuk melanjutkan yang paling penting."', 'author' => 'Winston Churchill' ],
            [ 'text' => '"Satu-satunya cara untuk melakukan pekerjaan hebat adalah dengan mencintai apa yang Anda lakukan."', 'author' => 'Steve Jobs' ],
            [ 'text' => '"Jangan melihat jam; lakukan apa yang dilakukannya. Teruslah maju."', 'author' => 'Sam Levenson' ],
            [ 'text' => '"Masa depan adalah milik mereka yang percaya pada keindahan mimpi mereka."', 'author' => 'Eleanor Roosevelt' ],
            [ 'text' => '"Pendidikan adalah senjata paling ampuh yang dapat Anda gunakan untuk mengubah dunia."', 'author' => 'Nelson Mandela' ]
        ];
    });
@endphp

<script>
    function motivatorQuotesData() {
        return {
            allQuotes: @js($quotes),
            quotes: [],
            activeQuote: 0,
            currentIndex: 0,
            init() {
                this.loadNextBatch();
                
                setInterval(() => {
                    if(this.quotes.length > 1) {
                        if (this.activeQuote >= this.quotes.length - 1) {
                            // Jika sudah di quote terakhir (kelima), load 5 quote baru
                            this.loadNextBatch();
                            this.activeQuote = 0;
                        } else {
                            this.activeQuote++;
                        }
                    }
                }, 6000);
            },
            loadNextBatch() {
                if (this.currentIndex >= this.allQuotes.length) {
                    this.currentIndex = 0;
                    // Acak ulang ketika sudah habis
                    this.allQuotes.sort(() => Math.random() - 0.5); 
                }
                
                this.quotes = this.allQuotes.slice(this.currentIndex, this.currentIndex + 5);
                this.currentIndex += 5;
            }
        }
    }
</script>
<div class="absolute inset-0 flex items-center justify-center p-8 bg-black/20" x-data="motivatorQuotesData()">
    
    <div class="max-w-sm w-full backdrop-blur-md bg-white/10 dark:bg-black/40 p-5 md:p-6 rounded-2xl border border-white/20 shadow-2xl text-white relative overflow-hidden transition-all duration-500">
        <!-- Quote Icon -->
        <div class="absolute top-0 right-0 p-4 md:p-5 opacity-20">
            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14.017 21v-7.391c0-5.714 4.026-6.695 4.995-6.695v3.608c-1.121 0-1.995.83-1.995 2.11v1.368h3.988v7.001h-6.988zm-11.002 0v-7.391c0-5.714 4.025-6.695 4.995-6.695v3.608c-1.121 0-1.995.83-1.995 2.11v1.368h3.988v7.001h-6.988z"/>
            </svg>
        </div>
        
        <!-- Quotes Container -->
        <div class="min-h-[120px] flex flex-col justify-center relative z-10">
            <template x-for="(quote, index) in quotes" :key="index">
                <div x-show="activeQuote === index"
                     x-transition:enter="transition-all ease-out duration-1000"
                     x-transition:enter-start="opacity-0 blur-sm translate-y-6 scale-95"
                     x-transition:enter-end="opacity-100 blur-0 translate-y-0 scale-100"
                     x-transition:leave="transition-all ease-in-out duration-700 absolute inset-0 flex flex-col justify-center"
                     x-transition:leave-start="opacity-100 blur-0 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 blur-lg -translate-y-6 scale-105"
                     class="w-full flex flex-col justify-center">
                    <p class="text-sm md:text-md font-light italic leading-relaxed mb-3 md:mb-4 drop-shadow-md text-white" x-text="quote.text"></p>
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-[2px] bg-primary-500 rounded-full shadow-[0_0_6px_rgba(var(--primary-500),0.5)]"></div>
                        <p class="font-semibold text-xs md:text-sm tracking-wider text-gray-200 uppercase" x-text="quote.author"></p>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Pagination Dots -->
        <div class="mt-4 flex gap-1.5 justify-start relative z-10">
            <template x-for="(quote, index) in quotes" :key="'dot-'+index">
                <button @click="activeQuote = index" 
                        class="h-1.5 rounded-full transition-all duration-500"
                        :class="activeQuote === index ? 'bg-primary-500 w-5 shadow-[0_0_4px_rgba(var(--primary-500),0.8)]' : 'bg-white/30 hover:bg-white/50 w-1.5'">
                </button>
            </template>
        </div>
    </div>
</div>

<?php

namespace App\Services;

class GrsPdfParserService
{
    /**
     * Mengekstrak seluruh teks dari file PDF secara mandiri (pure PHP tanpa eksternal dependency).
     * Mendukung font CID Type0 (Identity-H) dengan /ToUnicode CMap yang umum digunakan oleh SAP ERP.
     */
    public function extractText(string $pdfContent): string
    {
        preg_match_all('/(\d+)\s+(\d+)\s+obj(.*?)endobj/s', $pdfContent, $objs, PREG_SET_ORDER);
        $objMap = [];
        foreach ($objs as $o) {
            $objMap[(int) $o[1]] = $o[3];
        }

        // 1. Parse semua CMap yang ada dalam objek PDF
        $allCMapsByObj = [];
        foreach ($objMap as $id => $body) {
            if (str_contains($body, 'begincmap') || str_contains($body, '/CMapType')) {
                $stream = $this->getStream($body);
                if ($stream) {
                    $allCMapsByObj[$id] = $this->parseCMap($stream);
                }
            }
        }

        // 2. Petakan identifier font (/F1, /F2, dst) ke CMap-nya
        $fontCMaps = [];
        foreach ($objMap as $id => $body) {
            if (preg_match('/\/Font\s*<<([^>]*)>>/s', $body, $fontDict)) {
                if (preg_match_all('/\/([A-Za-z0-9_]+)\s+(\d+)\s+\d+\s+R/', $fontDict[1], $fRefs, PREG_SET_ORDER)) {
                    foreach ($fRefs as $fRef) {
                        $fName = '/'.$fRef[1];
                        $targetObjId = (int) $fRef[2];
                        $targetBody = $objMap[$targetObjId] ?? '';
                        if (preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $targetBody, $tu)) {
                            $cmapId = (int) $tu[1];
                            if (isset($allCMapsByObj[$cmapId])) {
                                $fontCMaps[$fName] = $allCMapsByObj[$cmapId];
                            }
                        }
                    }
                }
            }
        }

        // CMap gabungan sebagai fallback jika font spesifik belum terpetakan
        $aggregatedCMap = [];
        foreach ($allCMapsByObj as $c) {
            foreach ($c as $k => $v) {
                $aggregatedCMap[$k] = $v;
            }
        }

        // 3. Dekode seluruh content stream
        $text = '';
        foreach ($objMap as $id => $body) {
            if (str_contains($body, 'stream')) {
                $cs = $this->getStream($body);
                if ($cs && (str_contains($cs, 'BT') || str_contains($cs, 'TJ') || str_contains($cs, 'Tj'))) {
                    $text .= $this->tokenizeAndDecodeStream($cs, $fontCMaps, $aggregatedCMap)."\n";
                }
            }
        }

        return $text;
    }

    /**
     * Parsing dan mengekstrak metadata spesifik dokumen GRS / RDTV Pupuk Kaltim.
     *
     * @return array{
     *     no_grs: ?string,
     *     tanggal_release_gr: ?string,
     *     delivery_date: ?string,
     *     tanggal_penerimaan: ?string,
     *     no_po: ?string,
     *     tanggal_po: ?string,
     *     no_do: ?string,
     *     vendor_name: ?string,
     *     nama_barang: ?string,
     *     uoi: ?string,
     *     qty_penagihan: ?float,
     *     subtotal_exclude_ppn: ?float,
     *     raw_text: string
     * }
     */
    public function parse(string $filePathOrContent): array
    {
        $content = file_exists($filePathOrContent) ? (string) file_get_contents($filePathOrContent) : $filePathOrContent;
        $text = $this->extractText($content);

        $data = [
            'no_grs' => null,
            'tanggal_release_gr' => null,
            'delivery_date' => null,
            'tanggal_penerimaan' => null,
            'no_po' => null,
            'tanggal_po' => null,
            'no_do' => null,
            'vendor_name' => null,
            'nama_barang' => null,
            'uoi' => null,
            'qty_penagihan' => null,
            'subtotal_exclude_ppn' => null,
            'raw_text' => $text,
        ];

        // 1. No Release GR (biasanya 10 digit angka diawali 500xxxxxxx)
        if (preg_match('/No\s*Release\s*GR\s*[:\s]+(\d+)/i', $text, $m)) {
            $data['no_grs'] = $m[1];
        }

        // 2. Delivery Date (e.g. "Currency 23.11.2026 Delivery Date" atau "Delivery Date : 23.11.2026")
        if (preg_match('/Delivery\s*Date\s*[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        } elseif (preg_match('/(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})\s*(?:[A-Z]{3}\s*)?Delivery\s*Date/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        } elseif (preg_match('/Currency\s*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        }

        // 3. Layout Kolom SAP (Label berurutan diikuti deretan titik dua lalu nilai)
        // No PO -> Tanggal PO -> Tanggal Penerimaan -> Tanggal Release GR -> No. Surat Jalan
        if (preg_match('/No\s*PO.*?Tanggal\s*PO.*?Tanggal\s*Penerimaan.*?Tanggal\s*Release\s*GR.*?No\.?\s*Surat\s*Jalan.*?(5\d{9})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+([A-Za-z0-9\/-]+)/s', $text, $colMatch)) {
            $data['no_po'] = $colMatch[1];
            $data['tanggal_po'] = $colMatch[2];
            $data['tanggal_penerimaan'] = $colMatch[3];
            $data['tanggal_release_gr'] = $colMatch[4];
            $data['no_do'] = $colMatch[5];
        }

        // Fallbacks jika layout berbentuk key-value biasa
        if (! $data['tanggal_penerimaan'] && preg_match('/Tanggal\s*Penerimaan\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_penerimaan'] = $m[1];
        }

        if (! $data['tanggal_release_gr'] && preg_match('/Tanggal\s*Release\s*GR\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_release_gr'] = $m[1];
        }

        if (! $data['no_po'] && preg_match('/No\s*PO\s*[:\s]+(\d+)/i', $text, $m)) {
            $data['no_po'] = $m[1];
        }

        if (! $data['tanggal_po'] && preg_match('/Tanggal\s*PO\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_po'] = $m[1];
        }

        if (! $data['no_do'] && preg_match('/No\.?\s*Surat\s*Jalan\s*[:\s]+([A-Za-z0-9\/-]+)/i', $text, $m)) {
            if ($m[1] !== $data['no_po']) {
                $data['no_do'] = $m[1];
            }
        }

        // 4. Nama Vendor
        if (preg_match('/(?:PT\.?|CV\.?|[A-Z\s]+,\s*(?:PT|CV)\.?)\s*\n\s*:\s*\n\s*:\s*\n\s*Nama\s*Vendor/i', $text, $vm)) {
            preg_match('/([A-Z0-9\s.,]+(?:PT|CV|LTD|TBK)[A-Z0-9\s.,]*)/i', $vm[0], $vname);
            if (! empty($vname[1])) {
                $data['vendor_name'] = trim($vname[1]);
            }
        } elseif (preg_match('/Nama\s*Vendor\s*[:\s]+([^\r\n]+)/i', $text, $vm)) {
            $data['vendor_name'] = trim($vm[1]);
        }

        // 5. Item description & UoM (e.g. 1 VIDEO CONFERENCE DEP DANBA D601 SET 1 1 1)
        if (preg_match('/(?:Keterangan\s+)?1\s+([A-Z0-9\s\/\-_\.,]+?)\s+[A-Z0-9]{4}\s+([A-Z]{2,4})\s+(\d+)/si', $text, $im)) {
            $cleanedDesc = trim(preg_replace('/\s+/', ' ', $im[1]));
            if (! empty($cleanedDesc)) {
                $data['nama_barang'] = $cleanedDesc;
                $data['uoi'] = trim($im[2]);
                $data['qty_penagihan'] = (float) $im[3];
            }
        }

        // 6. Total Amount
        if (preg_match('/Total\s*:\s*([0-9.,]+)/i', $text, $tm)) {
            $clean = str_replace('.', '', $tm[1]);
            $clean = str_replace(',', '.', $clean);
            $data['subtotal_exclude_ppn'] = (float) $clean;
        }

        // 7. Normalisasi Format Tanggal ke Y-m-d (MySQL date)
        foreach (['tanggal_release_gr', 'delivery_date', 'tanggal_penerimaan', 'tanggal_po'] as $field) {
            if (! empty($data[$field])) {
                $val = str_replace(['/', '-'], '.', $data[$field]);
                $parts = explode('.', $val);
                if (count($parts) === 3) {
                    $data[$field] = sprintf('%04d-%02d-%02d', (int) $parts[2], (int) $parts[1], (int) $parts[0]);
                }
            }
        }

        return $data;
    }

    private function getStream(string $objStr): string
    {
        if (preg_match('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $objStr, $m)) {
            $s = @gzuncompress($m[1]);
            if ($s === false) {
                $s = @gzuncompress(ltrim($m[1]));
            }

            return $s !== false ? $s : $m[1];
        }

        return '';
    }

    private function parseCMap(string $cmapStr): array
    {
        $map = [];

        // 1. bfchar: didalam beginbfchar ... endbfchar
        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmapStr, $bfSections)) {
            foreach ($bfSections[1] as $section) {
                if (preg_match_all('/<([0-9a-fA-F]+)>\s+<([0-9a-fA-F]+)>/', $section, $chars, PREG_SET_ORDER)) {
                    foreach ($chars as $ch) {
                        $src = strtoupper(str_pad($ch[1], 4, '0', STR_PAD_LEFT));
                        $dst = hexdec($ch[2]);
                        $map[$src] = mb_chr($dst, 'UTF-8');
                    }
                }
            }
        } else {
            if (preg_match_all('/<([0-9a-fA-F]+)>\s+<([0-9a-fA-F]+)>/', $cmapStr, $chars, PREG_SET_ORDER)) {
                foreach ($chars as $ch) {
                    $src = strtoupper(str_pad($ch[1], 4, '0', STR_PAD_LEFT));
                    $dst = hexdec($ch[2]);
                    $map[$src] = mb_chr($dst, 'UTF-8');
                }
            }
        }

        // 2. bfrange: secara ketat didalam beginbfrange ... endbfrange
        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmapStr, $rangeSections)) {
            foreach ($rangeSections[1] as $section) {
                if (preg_match_all('/<([0-9a-fA-F]+)>\s+<([0-9a-fA-F]+)>\s+<([0-9a-fA-F]+)>/', $section, $ranges, PREG_SET_ORDER)) {
                    foreach ($ranges as $r) {
                        $start = hexdec($r[1]);
                        $end = hexdec($r[2]);
                        $dstStart = hexdec($r[3]);
                        for ($i = $start; $i <= $end; $i++) {
                            $src = strtoupper(str_pad(dechex($i), 4, '0', STR_PAD_LEFT));
                            $map[$src] = mb_chr($dstStart + ($i - $start), 'UTF-8');
                        }
                    }
                }
            }
        }

        return $map;
    }

    private function tokenizeAndDecodeStream(string $cs, array $fontCMaps, array $aggregatedCMap): string
    {
        preg_match_all('/(\/F\d+)\s+([\d.]+)\s+Tf|\[(.*?)\]\s*TJ|<([0-9a-fA-F]+)>\s*Tj|\((.*?)\)\s*Tj|ET|BT|(?:[0-9.-]+\s+){6}Tm/s', $cs, $tokens, PREG_SET_ORDER);

        $curFont = '/F1';
        $fullText = '';

        foreach ($tokens as $t) {
            $matched = $t[0];
            if (isset($t[1]) && $t[1] !== '') {
                $curFont = $t[1];
            } elseif (isset($t[3]) && $t[3] !== '') {
                $inner = $t[3];
                $activeMap = $fontCMaps[$curFont] ?? $aggregatedCMap;
                $decoded = '';

                if (preg_match_all('/<([0-9a-fA-F]+)>|\((.*?)\)|(-?[\d.]+)/', $inner, $elem, PREG_SET_ORDER)) {
                    foreach ($elem as $el) {
                        if (! empty($el[1])) {
                            $hexes = str_split($el[1], 4);
                            foreach ($hexes as $h) {
                                $h = strtoupper(str_pad($h, 4, '0', STR_PAD_LEFT));
                                $decoded .= $activeMap[$h] ?? ($aggregatedCMap[$h] ?? '');
                            }
                        } elseif (isset($el[2]) && $el[2] !== '') {
                            $decoded .= $el[2];
                        } elseif (isset($el[3]) && (float) $el[3] < -100) {
                            $decoded .= ' ';
                        }
                    }
                }
                $fullText .= $decoded.' ';
            } elseif (isset($t[4]) && $t[4] !== '') {
                $activeMap = $fontCMaps[$curFont] ?? $aggregatedCMap;
                $hexes = str_split($t[4], 4);
                $decoded = '';
                foreach ($hexes as $h) {
                    $h = strtoupper(str_pad($h, 4, '0', STR_PAD_LEFT));
                    $decoded .= $activeMap[$h] ?? ($aggregatedCMap[$h] ?? '');
                }
                $fullText .= $decoded.' ';
            } elseif (isset($t[5]) && $t[5] !== '') {
                $fullText .= $t[5].' ';
            }

            if (str_contains($matched, 'Tm') || str_contains($matched, 'ET')) {
                $fullText .= "\n";
            }
        }

        return $fullText;
    }
}

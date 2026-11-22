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
            // Abaikan stream gambar agar tidak menghabiskan memori
            if (str_contains($body, '/Subtype /Image') || str_contains($body, '/Subtype/Image')) {
                continue;
            }

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
     *     no_rdtv: ?string,
     *     tanggal_release_gr: ?string,
     *     tanggal_penolakan: ?string,
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
     *     alasan_penolakan: ?string,
     *     raw_text: string
     * }
     */
    public function parse(string $filePathOrContent): array
    {
        $content = file_exists($filePathOrContent) ? (string) file_get_contents($filePathOrContent) : $filePathOrContent;
        $text = $this->extractText($content);

        // Jika teks hasil ekstraksi kosong dan input bukan berkas PDF (%PDF), gunakan input langsung sebagai raw text
        if (empty(trim($text)) && ! str_starts_with(ltrim($content), '%PDF')) {
            $text = $content;
        }

        $data = [
            'no_grs' => null,
            'no_rdtv' => null,
            'tanggal_release_gr' => null,
            'tanggal_penolakan' => null,
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
            'alasan_penolakan' => null,
            'raw_text' => $text,
        ];

        // 1. No Release GR / GRS (10 digit angka diawali 500xxxxxxx)
        if (preg_match('/No\s*Release\s*GR\s*[:\s]+(\d+)/i', $text, $m)) {
            $data['no_grs'] = $m[1];
        } elseif (preg_match('/(?:Nomor\s*GRS(?:\s*\(105\))?|GRS\s*\(105\)|105\))\s*[:\s]+(500\d{7})/i', $text, $m)) {
            $data['no_grs'] = $m[1];
        }

        // 1b. No Document Return / RDTV (e.g. "No Document Return : 5007756200" atau "RETURN DELIVERY TO VENDOR ...")
        if (preg_match('/(?:No\.?\s*Document\s*Return|Nomor\s*Document\s*Return|Document\s*Return|No\.?\s*RDTV|Nomor\s*RDTV)\s*[:\s]+([A-Za-z0-9]+)/i', $text, $m)) {
            $data['no_rdtv'] = $m[1];
        } elseif (preg_match('/RETURN\s*DELIVERY\s*TO\s*VENDOR.*?(?:No\.?\s*Document\s*Return|Document\s*Return|Return\s*No\.?)?[\s\S]*?[:\s]*(500\d{7}|\d{10})/si', $text, $m)) {
            $data['no_rdtv'] = $m[1];
        }

        // 2. Delivery Date (e.g. "Currency 23.11.2026 Delivery Date" atau "Delivery Date : 23.11.2026")
        if (preg_match('/Delivery\s*Date\s*[:\s]*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        } elseif (preg_match('/(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})\s*(?:[A-Z]{3}\s*)?Delivery\s*Date/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        } elseif (preg_match('/Currency\s*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['delivery_date'] = $m[1];
        }

        // 2b. Tanggal Penolakan (khusus dokumen RDTV / Return Delivery to Vendor)
        if (preg_match('/Tanggal\s*Penolakan\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_penolakan'] = $m[1];
        }

        // 3. Layout Kolom SAP (Label berurutan diikuti deretan titik dua lalu nilai)
        // Pola A: GRS (No PO -> Tanggal PO -> Tanggal Penerimaan -> Tanggal Release GR -> No. Surat Jalan)
        if (preg_match('/No\s*PO.*?Tanggal\s*PO.*?Tanggal\s*Penerimaan.*?Tanggal\s*Release\s*GR.*?No\.?\s*Surat\s*Jalan.*?(5\d{9})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+([A-Za-z0-9\/-]+)/s', $text, $colMatch)) {
            $data['no_po'] = $colMatch[1];
            $data['tanggal_po'] = $colMatch[2];
            $data['tanggal_penerimaan'] = $colMatch[3];
            $data['tanggal_release_gr'] = $colMatch[4];
            $data['no_do'] = $colMatch[5];
        }
        // Pola B: RDTV (No PO -> Tanggal PO -> Tanggal Penerimaan -> Tanggal Penolakan -> No Surat Jalan)
        elseif (preg_match('/No\s*PO.*?Tanggal\s*PO.*?Tanggal\s*Penerimaan.*?Tanggal\s*Penolakan.*?No\.?\s*Surat\s*Jalan.*?(5\d{9})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+(\d{1,2}\.\d{1,2}\.\d{4})\s+([A-Za-z0-9\/-]+)/s', $text, $rdtvColMatch)) {
            $data['no_po'] = $rdtvColMatch[1];
            $data['tanggal_po'] = $rdtvColMatch[2];
            $data['tanggal_penerimaan'] = $rdtvColMatch[3];
            $data['tanggal_penolakan'] = $rdtvColMatch[4];
            $data['no_do'] = $rdtvColMatch[5];
        }

        // Fallbacks jika layout berbentuk key-value biasa (umum pada RDTV dan GRS)
        if (! $data['tanggal_penerimaan'] && preg_match('/Tanggal\s*Penerimaan\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_penerimaan'] = $m[1];
        }

        if (! $data['tanggal_release_gr'] && preg_match('/Tanggal\s*Release\s*GR\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_release_gr'] = $m[1];
        }

        if (! $data['tanggal_penolakan'] && preg_match('/Tanggal\s*Penolakan\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $data['tanggal_penolakan'] = $m[1];
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
            preg_match('/([A-Z\s.,]+(?:PT|CV|LTD|TBK)[A-Z\s.,]*)/i', $vm[0], $vname);
            if (! empty($vname[1])) {
                $data['vendor_name'] = trim($vname[1]);
            }
        } elseif (preg_match('/Nama\s*Vendor\s*\n\s*Alamat\s*Vendor\s*\n\s*:\s*\n\s*:\s*\n\s*([^\r\n:]+)/i', $text, $vm)) {
            $data['vendor_name'] = trim($vm[1]);
        } elseif (preg_match('/Nama\s*Vendor\s*[:\s]+([^\r\n]+)/i', $text, $vm)) {
            $data['vendor_name'] = trim($vm[1]);
        }

        // 5. Item description & UoM
        // Pola A: Format RDTV (e.g. 1 6043966 IMPELLER,PUMP:RHEINHT;RCE-80/320;POS.230 EA D601 1 1 1 10000464480 ...)
        if (preg_match('/(?:^|\n)\s*1\s+(\d{6,10})\s+([A-Z0-9\s\/\-_\.,:;]+?)\s+([A-Z]{2,4})\s+([A-Z0-9]{4})\s+(\d+(?:[.,]\d+)?)\s+(\d+(?:[.,]\d+)?)\s+(\d+(?:[.,]\d+)?)/mi', $text, $rm)) {
            $cleanedDesc = trim(preg_replace('/\s+/', ' ', $rm[2]));
            if (! empty($cleanedDesc)) {
                $data['nama_barang'] = $cleanedDesc;
                $data['uoi'] = trim($rm[3]);
                $data['qty_penagihan'] = (float) str_replace(',', '.', $rm[7]); // Qty Reject
            }
        }
        // Pola B: Format GRS (e.g. 1 VIDEO CONFERENCE DEP DANBA D601 SET 1 1 1)
        elseif (preg_match('/(?:Keterangan\s+)?1\s+([A-Z0-9\s\/\-_\.,]+?)\s+[A-Z0-9]{4}\s+([A-Z]{2,4})\s+(\d+)/si', $text, $im)) {
            $cleanedDesc = trim(preg_replace('/\s+/', ' ', $im[1]));
            if (! empty($cleanedDesc)) {
                $data['nama_barang'] = $cleanedDesc;
                $data['uoi'] = trim($im[2]);
                $data['qty_penagihan'] = (float) $im[3];
            }
        }

        // 6. Alasan Penolakan / Reason for Movement & Keterangan (khusus RDTV)
        if (preg_match('/(?:Out\s*of\s*Scope|Return\s*to\s*Vendor)\s+([A-Za-z0-9\s\/\-_\.,:;]+?)\s*$/is', trim($text), $km)) {
            $data['alasan_penolakan'] = trim(preg_replace('/\s+/', ' ', $km[1]));
        } elseif (preg_match('/(?:Reason\s*for\s*Movement|Alasan\s*Penolakan)[\s\S]*?\b(?:Out of Scope|Return to Vendor|[A-Za-z\s]+)\s+([A-Za-z0-9\s\/\-_\.,:;]+)$/mi', $text, $km)) {
            $data['alasan_penolakan'] = trim(preg_replace('/\s+/', ' ', $km[1]));
        }

        // 7. Total Amount
        if (preg_match('/Total\s*:\s*([0-9.,]+)/i', $text, $tm)) {
            $clean = str_replace('.', '', $tm[1]);
            $clean = str_replace(',', '.', $clean);
            $data['subtotal_exclude_ppn'] = (float) $clean;
        }

        // 8. Normalisasi Format Tanggal ke Y-m-d (MySQL date)
        foreach (['tanggal_release_gr', 'tanggal_penolakan', 'delivery_date', 'tanggal_penerimaan', 'tanggal_po'] as $field) {
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

    /**
     * Parsing dan ekstraksi metadata dokumen Verifikasi Penerimaan Barang (GRS / Kode 105).
     *
     * @return array{
     *     no_grs: ?string,
     *     no_gr_103: ?string,
     *     no_po: ?string,
     *     no_do: ?string,
     *     vendor_name: ?string,
     *     nomor_surat: ?string,
     *     tanggal_verifikasi: ?string,
     *     tanggal_release_gr: ?string,
     *     detection_source: 'text'|'ocr'|'filename'|'none',
     *     raw_text: string
     * }
     */
    public function parseVerifikasiGrs(string $filePathOrContent, ?string $originalFilename = null): array
    {
        $content = file_exists($filePathOrContent) ? (string) file_get_contents($filePathOrContent) : $filePathOrContent;
        $detectionSource = 'none';

        // 1. Ekstrak teks digital langsung dari PDF stream (pure PHP, instan < 5ms)
        $text = $this->extractText($content);

        if (strlen(trim($text)) >= 30 && (str_contains($text, 'GRS') || str_contains($text, '500') || str_contains($text, '530'))) {
            $detectionSource = 'text';
        }

        // 2. Jika teks belum lengkap, scan langsung seluruh uncompressed stream (pure PHP, < 2ms)
        if ($detectionSource === 'none' || ! str_contains($text, '500')) {
            $streamText = $this->extractRawStreamsText($content);
            if (! empty($streamText)) {
                $text .= "\n".$streamText;
                if ($detectionSource === 'none' && (str_contains($text, '500') || str_contains($text, '530'))) {
                    $detectionSource = 'stream';
                }
            }
        }

        $data = [
            'no_grs' => null,
            'no_gr_103' => null,
            'no_po' => null,
            'no_do' => null,
            'vendor_name' => null,
            'nomor_surat' => null,
            'tanggal_verifikasi' => null,
            'tanggal_release_gr' => null,
            'detection_source' => $detectionSource,
            'raw_text' => $text,
        ];

        // Format tabel / sekuensial (sering muncul pada OCR atau dokumen dua kolom)
        if (preg_match('/Nomor\s*PO.*?Nomor\s*GR.*?Nomor\s*GRS.*?([345]\d{9})\s+(500\d{7})\s+(500\d{7})/si', $text, $seq)) {
            $data['no_po'] = $seq[1];
            $data['no_gr_103'] = $seq[2];
            $data['no_grs'] = $seq[3];
        }

        // 1. Nomor GRS (105) - Key-Value
        if (! $data['no_grs']) {
            if (preg_match('/(?:Nomor\s*GRS(?:\s*\(105\))?|GRS\s*\(105\)|105\))\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_grs'] = $m[1];
            } elseif (preg_match('/No\s*Release\s*GR\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_grs'] = $m[1];
            }
        }

        // 2. Nomor GR (103) - Key-Value
        if (! $data['no_gr_103']) {
            if (preg_match('/(?:Nomor\s*GR(?:\s*\(103\))?|GR\s*\(103\)|103\))\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_gr_103'] = $m[1];
            }
        }

        // 3. Nomor PO (530xxxxxxx / 5000xxxxxx / 4xxxxxxxxx)
        if (! $data['no_po']) {
            if (preg_match('/(?:Nomor\s*PO|No\.?\s*PO)\s*[:\s]+([345]\d{9})/i', $text, $m)) {
                if ($m[1] !== $data['no_grs'] && $m[1] !== $data['no_gr_103']) {
                    $data['no_po'] = $m[1];
                }
            } elseif (preg_match('/No\s*PO.*?Tanggal\s*PO.*?([345]\d{9})\s+(\d{1,2}\.\d{1,2}\.\d{4})/s', $text, $m)) {
                $data['no_po'] = $m[1];
            } elseif (preg_match('/(?:^|[^0-9])(530\d{7})(?:[^0-9]|$)/', $text, $m)) {
                $data['no_po'] = $m[1];
            }
        }

        // 4. Nomor DO/SJ
        if (! $data['no_do']) {
            if (preg_match('/Nomor\s*DO(?:\/SJ)?\s*[:\s]+([A-Za-z0-9\/-]+)/i', $text, $m)) {
                $cleanedDo = trim($m[1]);
                if (strtolower($cleanedDo) !== 'status' && $cleanedDo !== $data['no_po']) {
                    $data['no_do'] = $cleanedDo;
                }
            }
            if (! $data['no_do'] && preg_match('/(?:Rp[0-9A-Za-z.,]+\s+){2}([A-Za-z0-9\/-]+)\s+Dapat\s*kami/i', $text, $m)) {
                $data['no_do'] = trim($m[1]);
            } elseif (! $data['no_do'] && preg_match('/No\.?\s*Surat\s*Jalan\s*[:\s]+([A-Za-z0-9\/-]+)/i', $text, $m)) {
                if ($m[1] !== $data['no_po']) {
                    $data['no_do'] = trim($m[1]);
                }
            }
        }

        // 5. Nama Vendor / Penyedia Barang
        if (preg_match('/(?:Nama\s*Penyedia\s*Barang|Nama\s*Vendor)\s*[:\s]+([^\r\n:]+)/i', $text, $m)) {
            $cleanedVendor = trim($m[1]);
            if (preg_match('/(?:PT|CV)\.?\s+[A-Za-z0-9\s.,]+?(?=\s+Nilai|\s+Rp|\s+Nomor|\s+Status|\r|\n|$)/i', $cleanedVendor, $vm)) {
                $data['vendor_name'] = trim($vm[0]);
            } else {
                $data['vendor_name'] = $cleanedVendor;
            }
        }
        if (! $data['vendor_name'] && preg_match('/(?:PT|CV)\.?\s+[A-Z\s]+(?:CIPTA|INDONESIA|JAYA|MAKMUR|PERSADA|UTAMA|KARYA|ABADI|LESTARI|MANDIRI)/i', $text, $m)) {
            $data['vendor_name'] = trim($m[0]);
        }

        // 6. Nomor Surat Verifikasi (e.g. GRS- 55983/D/PL/D52100/IT/2026)
        if (preg_match('/Nomor:\s*(GRS-\s*[A-Za-z0-9\/_-]+)/i', $text, $m)) {
            $data['nomor_surat'] = trim($m[1]);
        }

        // 7. Tanggal Verifikasi / Surat (e.g. "Tanggal: 28 Oktober 2026" atau "Tanggal : 28.10.2026")
        if (preg_match('/Tanggal\s*[:\s]+(\d{1,2}\s+[A-Za-z]+\s+\d{4})/i', $text, $m)) {
            $data['tanggal_verifikasi'] = $this->parseIndonesianDate($m[1]);
            $data['tanggal_release_gr'] = $data['tanggal_verifikasi'];
        } elseif (preg_match('/Tanggal\s*[:\s]+(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/i', $text, $m)) {
            $val = str_replace(['/', '-'], '.', $m[1]);
            $parts = explode('.', $val);
            if (count($parts) === 3) {
                $data['tanggal_verifikasi'] = sprintf('%04d-%02d-%02d', (int) $parts[2], (int) $parts[1], (int) $parts[0]);
                $data['tanggal_release_gr'] = $data['tanggal_verifikasi'];
            }
        }

        // 8. Fallback: Ekstraksi Nomor GRS (500xxxxxxx) dan PO (530xxxxxxx) dari Nama File jika belum ditemukan
        if (! $data['no_grs'] || ! $data['no_po']) {
            $filenameToCheck = $originalFilename ?: (file_exists($filePathOrContent) ? basename($filePathOrContent) : '');
            if (! empty($filenameToCheck)) {
                if (! $data['no_grs']) {
                    // Prioritaskan jika ada label GRS atau 105 di nama file
                    if (preg_match('/(?:grs|105)[^0-9]*(500\d{7})/i', $filenameToCheck, $grsSpecific)) {
                        $data['no_grs'] = $grsSpecific[1];
                        $data['detection_source'] = 'filename';
                    } elseif (preg_match('/(?:^|[^0-9])(500\d{7})(?:[^0-9]|$)/', $filenameToCheck, $fnMatch)) {
                        $data['no_grs'] = $fnMatch[1];
                        $data['detection_source'] = 'filename';
                    }
                }
                if (! $data['no_po']) {
                    if (preg_match('/(?:po)[^0-9]*(53\d{8})/i', $filenameToCheck, $poSpecific)) {
                        $data['no_po'] = $poSpecific[1];
                    } elseif (preg_match('/(?:^|[^0-9])(53\d{8})(?:[^0-9]|$)/', $filenameToCheck, $fnPo)) {
                        $data['no_po'] = $fnPo[1];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Memindai seluruh stream uncompressed untuk menemukan teks string atau nomor dokumen
     * secara langsung tanpa dependensi font/CMap (pure PHP, instan < 2ms, zero timeout).
     */
    public function extractRawStreamsText(string $pdfContent): string
    {
        preg_match_all('/(\d+)\s+(\d+)\s+obj(.*?)endobj/s', $pdfContent, $objs, PREG_SET_ORDER);
        $text = '';

        foreach ($objs as $o) {
            $body = $o[3];

            // Abaikan object gambar, font, atau filter biner berat untuk mencegah lonjakan CPU & memori
            if (str_contains($body, '/Subtype /Image')
                || str_contains($body, '/Subtype/Image')
                || str_contains($body, '/Filter /DCTDecode')
                || str_contains($body, '/Filter/DCTDecode')
                || str_contains($body, '/Filter /JBIG2Decode')
                || str_contains($body, '/Filter /JPXDecode')
                || str_contains($body, '/Type /Font')
                || str_contains($body, '/Type/Font')
            ) {
                continue;
            }

            if (str_contains($body, 'stream')) {
                $stream = $this->getStream($body);
                // Hanya proses stream teks wajar (< 128KB)
                if (! empty($stream) && strlen($stream) < 131072) {
                    // Ekstrak teks dalam tanda kurung: (Nomor GRS: 5007697783) atau kata-kata
                    if (preg_match_all('/\(([^)]+)\)/', $stream, $matches)) {
                        $text .= implode(' ', $matches[1])."\n";
                    }
                    // Tangkap nomor dokumen 10-digit langsung jika ada di stream
                    if (preg_match_all('/(?:500\d{7}|530\d{7})/', $stream, $numMatches)) {
                        $text .= implode(' ', $numMatches[0])."\n";
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Mengubah tanggal bahasa Indonesia ("28 Oktober 2026") menjadi format Y-m-d.
     */
    private function parseIndonesianDate(string $rawDate): ?string
    {
        $months = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
            'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
        ];

        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $rawDate, $m)) {
            $month = strtolower($m[2]);
            if (isset($months[$month])) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $months[$month], (int) $m[1]);
            }
        }

        return null;
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

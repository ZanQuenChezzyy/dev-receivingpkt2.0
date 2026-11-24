<?php

namespace App\Services;

class GrsPdfParserService
{
    /**
     * Mengekstrak seluruh teks dari file PDF secara mandiri (pure PHP tanpa eksternal dependency).
     * Mendukung font CID Type0 (Identity-H), TrueType 1-byte /ToUnicode (Nitro Pro, Ghostscript, dsb.),
     * kamus /Font inline maupun indirect, serta literal string dan hex string pada content stream.
     */
    public function extractText(string $pdfContent): string
    {
        preg_match_all('/(\d+)\s+(\d+)\s+obj(.*?)endobj/s', $pdfContent, $objs, PREG_SET_ORDER);
        $objMap = [];
        foreach ($objs as $o) {
            $objMap[(int) $o[1]] = $o[3];
        }

        // 1. Kumpulkan seluruh ID objek CMap dari referensi /ToUnicode terlebih dahulu
        $toUnicodeObjIds = [];
        foreach ($objMap as $id => $body) {
            if (preg_match_all('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $body, $tuMatches)) {
                foreach ($tuMatches[1] as $cmapObjId) {
                    $toUnicodeObjIds[(int) $cmapObjId] = true;
                }
            }
        }

        // 2. Parse semua CMap (baik yang uncompressed maupun yang dikompresi FlateDecode)
        /** @var array<int, array{byte_length: int, map: array<string, string>}> $allCMapsByObj */
        $allCMapsByObj = [];
        foreach ($objMap as $id => $body) {
            $isReferencedCMap = isset($toUnicodeObjIds[$id]);
            $hasRawCMapMarker = str_contains($body, 'begincmap') || str_contains($body, '/CMapType');

            if (! $isReferencedCMap && ! $hasRawCMapMarker) {
                continue;
            }

            $stream = $this->getStream($body);
            if ($stream !== '' && (str_contains($stream, 'begincmap') || str_contains($stream, 'beginbfchar') || str_contains($stream, 'beginbfrange'))) {
                $parsed = $this->parseCMap($stream);
                if ($parsed['map'] !== []) {
                    $allCMapsByObj[$id] = $parsed;
                }
            }
        }

        // 3. Petakan identifier font (/F1, /F0, /R7, /R9, dst) ke CMap-nya (inline maupun indirect /Font N 0 R)
        /** @var array<string, array{byte_length: int, map: array<string, string>}> $fontCMaps */
        $fontCMaps = [];
        $fontDictBodies = [];

        foreach ($objMap as $id => $body) {
            if (preg_match_all('/\/Font\s*<<([^>]*)>>/s', $body, $inlineDicts)) {
                foreach ($inlineDicts[1] as $dictContent) {
                    $fontDictBodies[] = $dictContent;
                }
            }

            if (preg_match_all('/\/Font\s+(\d+)\s+\d+\s+R/', $body, $indirectRefs)) {
                foreach ($indirectRefs[1] as $fontDictObjId) {
                    $resolvedBody = $objMap[(int) $fontDictObjId] ?? '';
                    if ($resolvedBody !== '') {
                        $fontDictBodies[] = $resolvedBody;
                    }
                }
            }
        }

        foreach ($fontDictBodies as $dictBody) {
            if (preg_match_all('/\/([A-Za-z0-9_.\-+]+)\s+(\d+)\s+\d+\s+R/', $dictBody, $fRefs, PREG_SET_ORDER)) {
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

        // CMap gabungan per lebar byte (1-byte dan 2-byte) sebagai fallback jika font alias belum terpetakan
        $aggregatedCMap1 = [];
        $aggregatedCMap2 = [];
        foreach ($allCMapsByObj as $cmapData) {
            if ($cmapData['byte_length'] === 1) {
                foreach ($cmapData['map'] as $k => $v) {
                    $aggregatedCMap1[$k] ??= $v;
                }
            } else {
                foreach ($cmapData['map'] as $k => $v) {
                    $aggregatedCMap2[$k] ??= $v;
                }
            }
        }

        $fallbackCMap = $aggregatedCMap2 !== []
            ? ['byte_length' => 2, 'map' => $aggregatedCMap2]
            : ['byte_length' => 1, 'map' => $aggregatedCMap1];

        // 4. Dekode seluruh content stream (abaikan gambar, font biner, dan stream CMap)
        $text = '';
        foreach ($objMap as $id => $body) {
            if (isset($allCMapsByObj[$id]) || isset($toUnicodeObjIds[$id])) {
                continue;
            }

            if (str_contains($body, '/Subtype /Image')
                || str_contains($body, '/Subtype/Image')
                || str_contains($body, '/Filter /DCTDecode')
                || str_contains($body, '/Filter/DCTDecode')
                || str_contains($body, '/Filter /CCITTFaxDecode')
                || str_contains($body, '/Filter/CCITTFaxDecode')
                || str_contains($body, '/Filter /JBIG2Decode')
                || str_contains($body, '/Filter/JBIG2Decode')
                || str_contains($body, '/Length1')
            ) {
                continue;
            }

            if (str_contains($body, 'stream')) {
                $cs = $this->getStream($body);
                if ($cs !== '' && (str_contains($cs, 'BT') || str_contains($cs, 'TJ') || str_contains($cs, 'Tj'))) {
                    $text .= $this->tokenizeAndDecodeStream($cs, $fontCMaps, $fallbackCMap, $aggregatedCMap1, $aggregatedCMap2)."\n";
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
        if (preg_match('/No\.?\s*Release\s*GR\s*[:\s]+(\d+)/i', $text, $m)) {
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
        if (preg_match('/(?:Out\s*of\s*Scope|Return\s*to\s*Vendor)\s+([A-Za-z0-9\s\/\-_\.,:;]{2,500}?)\s*$/is', trim($text), $km)) {
            $data['alasan_penolakan'] = trim(preg_replace('/\s+/', ' ', $km[1]));
        } elseif (preg_match('/(?:Reason\s*for\s*Movement|Alasan\s*Penolakan)\s*[:\s]+([A-Za-z0-9 \/\-_\.,:;]{2,300})/i', $text, $km)) {
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
                $val = str_replace(['/', '-'], '.', trim((string) $data[$field]));
                $parts = explode('.', $val);
                if (count($parts) === 3) {
                    $day = (int) $parts[0];
                    $month = (int) $parts[1];
                    $year = (int) $parts[2];
                    if ($year < 100) {
                        $year += 2000;
                    }
                    $data[$field] = checkdate($month, $day, $year)
                        ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                        : null;
                } else {
                    $data[$field] = null;
                }
            }
        }

        return $data;
    }

    /**
     * Mengklasifikasikan satu file PDF apakah termasuk Slip GRS (105), Surat Verifikasi GRS,
     * Slip RDTV (124), atau Surat Verifikasi RDTV, sekaligus mengekstrak seluruh metadata pentingnya.
     *
     * @return array{
     *     role: 'grs_slip'|'verifikasi_grs'|'rdtv_slip'|'verifikasi_rdtv'|'unknown',
     *     jenis: ?string,
     *     document_code: ?string,
     *     no_grs: ?string,
     *     no_rdtv: ?string,
     *     no_po: ?string,
     *     no_do: ?string,
     *     delivery_date: ?string,
     *     tanggal_release_gr: ?string,
     *     tanggal_penolakan: ?string,
     *     tanggal_penerimaan: ?string,
     *     tanggal_po: ?string,
     *     vendor_name: ?string,
     *     alasan_penolakan: ?string,
     *     needs_ocr: bool,
     *     confidence: 'high'|'medium'|'low'
     * }
     */
    public function classifyDocument(
        string $filePathOrContent,
        ?string $originalFilename = null,
        bool $filenameMatchesDo = false,
    ): array {
        $content = file_exists($filePathOrContent) ? (string) file_get_contents($filePathOrContent) : $filePathOrContent;

        $slipParsed = $this->parse($content);
        $verifParsed = $this->parseVerifikasiGrs($content, $originalFilename);

        $combinedText = trim(($slipParsed['raw_text'] ?? '')."\n".($verifParsed['raw_text'] ?? ''));
        $filenameBase = $originalFilename !== null ? trim((string) pathinfo($originalFilename, PATHINFO_FILENAME)) : '';
        $filenameLower = strtolower($filenameBase);

        $isVerifText = preg_match(
            '/VERIFIKASI\s+PENERIMAAN\s+BARANG|VERIFIKASI\s+PENOLAKAN\s+BARANG|SURAT\s+VERIFIKASI|BERITA\s+ACARA\s+(?:PENERIMAAN|PENOLAKAN)|Nomor\s*:\s*(?:GRS|RDTV)-|Nomor\s+GR\s*\(\s*103\s*\)|TELAH\s+DIVERIFIKASI/i',
            $combinedText
        ) === 1;

        $isSlipText = preg_match(
            '/GOODS\s+RECEIPT\s+SLIP|No\s*Release\s*GR|Tanggal\s*Release\s*GR|RETURN\s+DELIVERY\s+TO\s+VENDOR|No\.?\s*Document\s*Return|Tanggal\s*Penolakan|Delivery\s*Date/i',
            $combinedText
        ) === 1;

        $isVerifFilename = preg_match('/\b(verif|verifikasi|surat[\s_-]*verif|berita[\s_-]*acara|ba[\s_-]*(?:grs|rdtv))\b/i', $filenameLower) === 1;
        $isRdtvText = preg_match(
            '/VERIFIKASI\s+PENOLAKAN\s+BARANG|RETURN\s+DELIVERY\s+TO\s+VENDOR|No\.?\s*Document\s*Return|Tanggal\s*Penolakan|Nomor\s*RDTV|RDTV\s*\(?124\)?|Nomor\s*:\s*RDTV-|Alasan\s+Pengembalian/i',
            $combinedText
        ) === 1;
        $isRdtvFilename = str_contains($filenameLower, 'rdtv');

        $noGrs = $slipParsed['no_grs'] ?: $verifParsed['no_grs'];
        $noRdtv = $slipParsed['no_rdtv'] ?: $verifParsed['no_rdtv'];
        $noPo = $slipParsed['no_po'] ?: $verifParsed['no_po'];
        $noDo = $slipParsed['no_do'] ?: $verifParsed['no_do'];

        // Deteksi jenis GRS vs RDTV
        $jenis = null;
        if ($isRdtvText || ($verifParsed['jenis'] ?? null) === 'RDTV' || filled($noRdtv) || $isRdtvFilename) {
            $jenis = 'RDTV';
        } elseif ($isSlipText || $isVerifText || ($verifParsed['jenis'] ?? null) === 'GRS' || filled($noGrs) || $filenameMatchesDo) {
            $jenis = 'GRS';
        }

        // Jika RDTV tapi nomor sempat tertangkap di no_grs, pindahkan ke no_rdtv
        if ($jenis === 'RDTV' && blank($noRdtv) && filled($noGrs)) {
            $noRdtv = $noGrs;
            $noGrs = null;
        }

        // Tentukan peran dokumen (Slip vs Verifikasi)
        $role = 'unknown';
        $confidence = 'low';

        if ($isVerifText && ! $isSlipText) {
            $role = $jenis === 'RDTV' ? 'verifikasi_rdtv' : 'verifikasi_grs';
            $confidence = 'high';
        } elseif ($isSlipText && ! $isVerifText) {
            $role = $jenis === 'RDTV' ? 'rdtv_slip' : 'grs_slip';
            $confidence = 'high';
        } elseif ($isVerifText && $isSlipText) {
            // Jika dua-duanya muncul (mis. kata "Goods Receipt Slip" disebut di dalam Surat Verifikasi),
            // penanda judul Verifikasi lebih kuat kecuali nama file cocok persis dengan document_code DO.
            if ($filenameMatchesDo && ! $isVerifFilename) {
                $role = $jenis === 'RDTV' ? 'rdtv_slip' : 'grs_slip';
                $confidence = 'medium';
            } else {
                $role = $jenis === 'RDTV' ? 'verifikasi_rdtv' : 'verifikasi_grs';
                $confidence = 'high';
            }
        } elseif ($isVerifFilename) {
            $role = $jenis === 'RDTV' ? 'verifikasi_rdtv' : 'verifikasi_grs';
            $confidence = 'medium';
        } elseif ($filenameMatchesDo) {
            $role = $jenis === 'RDTV' ? 'rdtv_slip' : 'grs_slip';
            $confidence = 'high';
        }

        $hasAnyNumber = filled($noGrs) || filled($noRdtv) || filled($noPo);
        $needsOcr = strlen(trim($combinedText)) < 25 && ! $hasAnyNumber;

        return [
            'role' => $role,
            'jenis' => $jenis,
            'document_code' => $filenameBase !== '' ? $filenameBase : null,
            'no_grs' => $noGrs ?: null,
            'no_rdtv' => $noRdtv ?: null,
            'no_po' => $noPo ?: null,
            'no_do' => $noDo ?: null,
            'delivery_date' => $slipParsed['delivery_date'] ?: null,
            'tanggal_release_gr' => $slipParsed['tanggal_release_gr'] ?: ($verifParsed['tanggal_release_gr'] ?: null),
            'tanggal_penolakan' => $slipParsed['tanggal_penolakan'] ?: null,
            'tanggal_penerimaan' => $slipParsed['tanggal_penerimaan'] ?: null,
            'tanggal_po' => $slipParsed['tanggal_po'] ?: null,
            'vendor_name' => $slipParsed['vendor_name'] ?: ($verifParsed['vendor_name'] ?: null),
            'alasan_penolakan' => $slipParsed['alasan_penolakan'] ?: null,
            'needs_ocr' => $needsOcr,
            'confidence' => $confidence,
        ];
    }

    /**
     * Parsing dan ekstraksi metadata dokumen Verifikasi Penerimaan Barang (GRS / Kode 105)
     * maupun Verifikasi Penolakan Barang (RDTV / Kode 124).
     *
     * @return array{
     *     jenis: ?string,
     *     no_grs: ?string,
     *     no_rdtv: ?string,
     *     no_gr_103: ?string,
     *     no_po: ?string,
     *     no_do: ?string,
     *     vendor_name: ?string,
     *     nomor_surat: ?string,
     *     tanggal_verifikasi: ?string,
     *     tanggal_release_gr: ?string,
     *     detection_source: 'text'|'stream'|'ocr'|'filename'|'none',
     *     raw_text: string
     * }
     */
    public function parseVerifikasiGrs(string $filePathOrContent, ?string $originalFilename = null): array
    {
        $content = file_exists($filePathOrContent) ? (string) file_get_contents($filePathOrContent) : $filePathOrContent;
        $detectionSource = 'none';

        // 1. Ekstrak teks digital langsung dari PDF stream (pure PHP, instan < 5ms)
        $text = $this->extractText($content);

        if (strlen(trim($text)) >= 30 && (str_contains($text, 'GRS') || str_contains($text, 'RDTV') || str_contains($text, '500') || str_contains($text, '530'))) {
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

        // Jika input bukan biner PDF melainkan teks mentah (mis. untuk unit test / teks hasil OCR)
        if (empty(trim($text)) && ! str_starts_with(ltrim($content), '%PDF') && ! empty(trim($content))) {
            $text = $content;
            $detectionSource = 'text';
        }

        $data = [
            'jenis' => null,
            'no_grs' => null,
            'no_rdtv' => null,
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

        // 0. Deteksi jenis dokumen: RDTV (Verifikasi Penolakan Barang / 124) vs GRS (Verifikasi Penerimaan Barang / 105)
        $isRdtvText = preg_match('/VERIFIKASI\s+PENOLAKAN\s+BARANG|Return\s+Delivery\s+To\s+Vendor|Berita\s+Acara\s+Penolakan|Nomor\s*:\s*RDTV-|Nomor\s*RDTV\s*\(?124\)?|RDTV\s*\(?124\)?|Alasan\s+Pengembalian/i', $text) === 1;
        $isGrsText = preg_match('/VERIFIKASI\s+PENERIMAAN\s+BARANG|Goods\s+Receipt\s+Slip|Berita\s+Acara\s+Penerimaan|Nomor\s*:\s*GRS-|Nomor\s*GRS\s*\(?105\)?|GRS\s*\(?105\)?/i', $text) === 1;

        if ($isRdtvText) {
            $data['jenis'] = 'RDTV';
        } elseif ($isGrsText) {
            $data['jenis'] = 'GRS';
        }

        // Format tabel / sekuensial (sering muncul pada OCR atau dokumen dua kolom)
        if ($data['jenis'] === 'RDTV' && preg_match('/Nomor\s*PO.*?Nomor\s*GR.*?Nomor\s*RDTV.*?([345]\d{9})\s+(500\d{7})\s+(500\d{7})/si', $text, $seqRdtv)) {
            $data['no_po'] = $seqRdtv[1];
            $data['no_gr_103'] = $seqRdtv[2];
            $data['no_rdtv'] = $seqRdtv[3];
        } elseif (preg_match('/Nomor\s*PO.*?Nomor\s*GR.*?Nomor\s*GRS.*?([345]\d{9})\s+(500\d{7})\s+(500\d{7})/si', $text, $seq)) {
            $data['no_po'] = $seq[1];
            $data['no_gr_103'] = $seq[2];
            $data['no_grs'] = $seq[3];
            $data['jenis'] ??= 'GRS';
        }

        // 1a. Nomor RDTV (124) - Key-Value
        if (! $data['no_rdtv']) {
            if (preg_match('/(?:Nomor\s*RDTV(?:\s*\(124\))?|RDTV\s*\(124\)|124\))\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_rdtv'] = $m[1];
                $data['jenis'] = 'RDTV';
            }
        }

        // 1b. Nomor GRS (105) - Key-Value
        if (! $data['no_grs'] && $data['jenis'] !== 'RDTV') {
            if (preg_match('/(?:Nomor\s*GRS(?:\s*\(105\))?|GRS\s*\(105\)|105\))\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_grs'] = $m[1];
                $data['jenis'] ??= 'GRS';
            } elseif (preg_match('/No\.?\s*Release\s*GR\s*[:\s]+(500\d{7})/i', $text, $m)) {
                $data['no_grs'] = $m[1];
                $data['jenis'] ??= 'GRS';
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

        // 6. Nomor Surat Verifikasi (e.g. GRS- 55983/D/PL/D52100/IT/2026 atau RDTV- 58872/D/PL/D52100/IT/2026)
        if (preg_match('/Nomor:\s*((?:GRS|RDTV)-\s*[A-Za-z0-9\/_-]+)/i', $text, $m)) {
            $data['nomor_surat'] = trim($m[1]);
            if ($data['jenis'] === null) {
                $data['jenis'] = stripos($data['nomor_surat'], 'RDTV') === 0 ? 'RDTV' : 'GRS';
            }
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

        // 8. Fallback: Ekstraksi Jenis (GRS/RDTV), Nomor GRS/RDTV (500xxxxxxx), dan PO (530xxxxxxx) dari Nama File jika belum ditemukan
        if ((! $data['no_grs'] && ! $data['no_rdtv']) || ! $data['no_po'] || ! $data['jenis']) {
            $filenameToCheck = $originalFilename ?: (file_exists($filePathOrContent) ? basename($filePathOrContent) : '');
            if (! empty($filenameToCheck)) {
                if (! $data['jenis']) {
                    if (preg_match('/\b(?:rdtv|124)\b|rdtv/i', $filenameToCheck) === 1) {
                        $data['jenis'] = 'RDTV';
                    } elseif (preg_match('/\b(?:grs|105)\b|grs/i', $filenameToCheck) === 1) {
                        $data['jenis'] = 'GRS';
                    }
                }

                if ($data['jenis'] === 'RDTV' && ! $data['no_rdtv']) {
                    if (preg_match('/(?:rdtv|124)[^0-9]*(500\d{7})/i', $filenameToCheck, $rdtvSpecific)) {
                        $data['no_rdtv'] = $rdtvSpecific[1];
                        $data['detection_source'] = 'filename';
                    } elseif (preg_match('/(?:^|[^0-9])(500\d{7})(?:[^0-9]|$)/', $filenameToCheck, $fnMatch)) {
                        $data['no_rdtv'] = $fnMatch[1];
                        $data['detection_source'] = 'filename';
                    }
                } elseif (! $data['no_grs'] && $data['jenis'] !== 'RDTV') {
                    // Prioritaskan jika ada label GRS atau 105 di nama file
                    if (preg_match('/(?:grs|105)[^0-9]*(500\d{7})/i', $filenameToCheck, $grsSpecific)) {
                        $data['no_grs'] = $grsSpecific[1];
                        $data['jenis'] ??= 'GRS';
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
        if (preg_match('/stream(?:\r\n|\n|\r)(.*?)endstream/s', $objStr, $m)) {
            $raw = $m[1];
            $trimmedRight = rtrim($raw, "\r\n");

            if (str_contains($objStr, '/FlateDecode')) {
                foreach ([$trimmedRight, $raw, ltrim($trimmedRight)] as $candidate) {
                    $decompressed = @gzuncompress($candidate);
                    if ($decompressed !== false) {
                        return $decompressed;
                    }
                    $inflated = @gzinflate($candidate);
                    if ($inflated !== false) {
                        return $inflated;
                    }
                }
            }

            $s = @gzuncompress($trimmedRight);
            if ($s === false) {
                $s = @gzuncompress($raw);
            }
            if ($s === false) {
                $s = @gzuncompress(ltrim($trimmedRight));
            }

            return $s !== false ? $s : $trimmedRight;
        }

        return '';
    }

    /**
     * @return array{byte_length: int, map: array<string, string>}
     */
    private function parseCMap(string $cmapStr): array
    {
        $map = [];
        $byteLength = 2;

        // Deteksi lebar byte kode dari begincodespacerange (<00><ff> = 1 byte, <0000><ffff> = 2 byte)
        if (preg_match('/begincodespacerange\s*<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>/s', $cmapStr, $csMatch)) {
            $hexLen = max(strlen($csMatch[1]), strlen($csMatch[2]));
            $byteLength = $hexLen <= 2 ? 1 : 2;
        }

        $padLen = $byteLength * 2;

        // 1. bfchar: didalam beginbfchar ... endbfchar (mendukung spasi maupun tanpa spasi antar <hex>)
        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmapStr, $bfSections)) {
            foreach ($bfSections[1] as $section) {
                if (preg_match_all('/<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>/', $section, $chars, PREG_SET_ORDER)) {
                    foreach ($chars as $ch) {
                        $src = strtoupper(str_pad($ch[1], $padLen, '0', STR_PAD_LEFT));
                        $map[$src] = $this->decodeUnicodeHex($ch[2]);
                    }
                }
            }
        }

        // 2. bfrange: format standar <start><end><dstStart> maupun format array <start><end>[<u1><u2>...]
        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmapStr, $rangeSections)) {
            foreach ($rangeSections[1] as $section) {
                if (preg_match_all('/<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>/', $section, $ranges, PREG_SET_ORDER)) {
                    foreach ($ranges as $r) {
                        $start = hexdec($r[1]);
                        $end = hexdec($r[2]);
                        $dstStart = hexdec($r[3]);
                        if ($end - $start > 2048) {
                            continue;
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            $src = strtoupper(str_pad(dechex($i), $padLen, '0', STR_PAD_LEFT));
                            $map[$src] = mb_chr($dstStart + ($i - $start), 'UTF-8');
                        }
                    }
                }

                if (preg_match_all('/<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>\s*\[([^\]]+)\]/s', $section, $arrayRanges, PREG_SET_ORDER)) {
                    foreach ($arrayRanges as $ar) {
                        $start = hexdec($ar[1]);
                        if (preg_match_all('/<([0-9a-fA-F]+)>/', $ar[3], $dstItems)) {
                            foreach ($dstItems[1] as $offset => $dstHex) {
                                $src = strtoupper(str_pad(dechex($start + $offset), $padLen, '0', STR_PAD_LEFT));
                                $map[$src] = $this->decodeUnicodeHex($dstHex);
                            }
                        }
                    }
                }
            }
        }

        return [
            'byte_length' => $byteLength,
            'map' => $map,
        ];
    }

    private function decodeUnicodeHex(string $hex): string
    {
        $hex = strtoupper($hex);
        if (strlen($hex) <= 4) {
            return mb_chr((int) hexdec($hex), 'UTF-8');
        }

        $out = '';
        foreach (str_split($hex, 4) as $chunk) {
            if (strlen($chunk) === 4) {
                $out .= mb_chr((int) hexdec($chunk), 'UTF-8');
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array{byte_length: int, map: array<string, string>}>  $fontCMaps
     * @param  array{byte_length: int, map: array<string, string>}  $fallbackCMap
     * @param  array<string, string>  $aggregatedCMap1
     * @param  array<string, string>  $aggregatedCMap2
     */
    private function tokenizeAndDecodeStream(
        string $cs,
        array $fontCMaps,
        array $fallbackCMap,
        array $aggregatedCMap1,
        array $aggregatedCMap2,
    ): string {
        $len = strlen($cs);
        $i = 0;
        $curFont = '/F1';
        $fullText = '';
        $operands = [];

        while ($i < $len) {
            $ch = $cs[$i];

            // Lewati whitespace
            if ($ch === ' ' || $ch === "\t" || $ch === "\n" || $ch === "\r" || $ch === "\f" || $ch === "\0") {
                $i++;

                continue;
            }

            // Komentar PDF (% ... newline)
            if ($ch === '%') {
                while ($i < $len && $cs[$i] !== "\n" && $cs[$i] !== "\r") {
                    $i++;
                }

                continue;
            }

            // Literal string: ( ... ) dengan dukungan tanda kurung bersarang dan escape PDF
            if ($ch === '(') {
                [$rawBytes, $nextIdx] = $this->readPdfLiteralString($cs, $i, $len);
                $operands[] = ['type' => 'literal', 'value' => $rawBytes];
                $i = $nextIdx;

                continue;
            }

            // Dictionary << ... >> (lewati) atau Hex string < ... >
            if ($ch === '<') {
                if ($i + 1 < $len && $cs[$i + 1] === '<') {
                    $depth = 1;
                    $i += 2;
                    while ($i < $len && $depth > 0) {
                        if ($cs[$i] === '<' && $i + 1 < $len && $cs[$i + 1] === '<') {
                            $depth++;
                            $i += 2;
                        } elseif ($cs[$i] === '>' && $i + 1 < $len && $cs[$i + 1] === '>') {
                            $depth--;
                            $i += 2;
                        } else {
                            $i++;
                        }
                    }

                    continue;
                }

                $end = strpos($cs, '>', $i + 1);
                if ($end === false) {
                    break;
                }
                $hex = preg_replace('/\s+/', '', substr($cs, $i + 1, $end - $i - 1));
                $operands[] = ['type' => 'hex', 'value' => (string) $hex];
                $i = $end + 1;

                continue;
            }

            // Array [ ... ] (digunakan oleh operator TJ)
            if ($ch === '[') {
                [$arrItems, $nextIdx] = $this->readPdfArray($cs, $i, $len);
                $operands[] = ['type' => 'array', 'value' => $arrItems];
                $i = $nextIdx;

                continue;
            }

            // Name /FontName
            if ($ch === '/') {
                $start = $i;
                $i++;
                while ($i < $len && ! str_contains(" \t\n\r\f\0()<>[]{}/%", $cs[$i])) {
                    $i++;
                }
                $operands[] = ['type' => 'name', 'value' => substr($cs, $start, $i - $start)];

                continue;
            }

            // Operator kutip tunggal (') atau ganda (") -> tampilkan teks di baris berikutnya
            if ($ch === "'" || $ch === '"') {
                $last = end($operands);
                if ($last && in_array($last['type'], ['literal', 'hex'], true)) {
                    $decoded = $this->decodeTextToken($last, $curFont, $fontCMaps, $fallbackCMap, $aggregatedCMap1, $aggregatedCMap2);
                    if ($decoded !== '') {
                        $fullText .= "\n".$decoded.' ';
                    }
                }
                $operands = [];
                $i++;

                continue;
            }

            // Token angka atau operator kata (Tf, Tj, TJ, Tm, Td, TD, T*, BT, ET, dsb.)
            $start = $i;
            while ($i < $len && ! str_contains(" \t\n\r\f\0()<>[]{}/%'\"", $cs[$i])) {
                $i++;
            }
            $word = substr($cs, $start, $i - $start);

            if ($word === '') {
                $i++;

                continue;
            }

            if (is_numeric($word)) {
                $operands[] = ['type' => 'number', 'value' => (float) $word];

                continue;
            }

            if ($word === 'Tf') {
                foreach ($operands as $op) {
                    if ($op['type'] === 'name') {
                        $curFont = $op['value'];
                    }
                }
            } elseif ($word === 'Tj') {
                $last = end($operands);
                if ($last && in_array($last['type'], ['literal', 'hex'], true)) {
                    $decoded = $this->decodeTextToken($last, $curFont, $fontCMaps, $fallbackCMap, $aggregatedCMap1, $aggregatedCMap2);
                    if ($decoded !== '') {
                        $fullText .= $decoded.' ';
                    }
                }
            } elseif ($word === 'TJ') {
                $last = end($operands);
                if ($last && $last['type'] === 'array') {
                    $decoded = '';
                    foreach ($last['value'] as $el) {
                        if ($el['type'] === 'hex' || $el['type'] === 'literal') {
                            $decoded .= $this->decodeTextToken($el, $curFont, $fontCMaps, $fallbackCMap, $aggregatedCMap1, $aggregatedCMap2);
                        } elseif ($el['type'] === 'number' && $el['value'] < -100) {
                            $decoded .= ' ';
                        }
                    }
                    if ($decoded !== '') {
                        $fullText .= $decoded.' ';
                    }
                }
            } elseif ($word === 'Tm' || $word === 'Td' || $word === 'TD' || $word === 'T*' || $word === 'ET') {
                $fullText .= "\n";
            }

            $operands = [];
        }

        return $fullText;
    }

    /**
     * Membaca PDF literal string (...) dengan unescape urutan escape standar PDF.
     *
     * @return array{0: string, 1: int}
     */
    private function readPdfLiteralString(string $cs, int $start, int $len): array
    {
        $i = $start + 1;
        $depth = 1;
        $out = '';

        while ($i < $len && $depth > 0) {
            $c = $cs[$i];
            if ($c === '\\') {
                $i++;
                if ($i >= $len) {
                    break;
                }
                $next = $cs[$i];
                if ($next === 'n') {
                    $out .= "\n";
                    $i++;
                } elseif ($next === 'r') {
                    $out .= "\r";
                    $i++;
                } elseif ($next === 't') {
                    $out .= "\t";
                    $i++;
                } elseif ($next === 'b') {
                    $out .= "\x08";
                    $i++;
                } elseif ($next === 'f') {
                    $out .= "\x0C";
                    $i++;
                } elseif ($next === '(' || $next === ')' || $next === '\\') {
                    $out .= $next;
                    $i++;
                } elseif ($next === "\r" || $next === "\n") {
                    if ($next === "\r" && $i + 1 < $len && $cs[$i + 1] === "\n") {
                        $i += 2;
                    } else {
                        $i++;
                    }
                } elseif ($next >= '0' && $next <= '7') {
                    $octal = $next;
                    $i++;
                    for ($k = 0; $k < 2 && $i < $len && $cs[$i] >= '0' && $cs[$i] <= '7'; $k++) {
                        $octal .= $cs[$i];
                        $i++;
                    }
                    $out .= chr(octdec($octal) & 0xFF);
                } else {
                    $out .= $next;
                    $i++;
                }
            } elseif ($c === '(') {
                $depth++;
                $out .= '(';
                $i++;
            } elseif ($c === ')') {
                $depth--;
                if ($depth > 0) {
                    $out .= ')';
                }
                $i++;
            } else {
                $out .= $c;
                $i++;
            }
        }

        return [$out, $i];
    }

    /**
     * Membaca elemen di dalam array PDF [ ... ] untuk operator TJ.
     *
     * @return array{0: array<int, array{type: string, value: mixed}>, 1: int}
     */
    private function readPdfArray(string $cs, int $start, int $len): array
    {
        $i = $start + 1;
        $items = [];

        while ($i < $len) {
            $c = $cs[$i];
            if ($c === ']') {
                $i++;
                break;
            }
            if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r" || $c === "\f" || $c === "\0") {
                $i++;

                continue;
            }
            if ($c === '(') {
                [$rawBytes, $nextIdx] = $this->readPdfLiteralString($cs, $i, $len);
                $items[] = ['type' => 'literal', 'value' => $rawBytes];
                $i = $nextIdx;

                continue;
            }
            if ($c === '<') {
                $end = strpos($cs, '>', $i + 1);
                if ($end === false) {
                    break;
                }
                $hex = preg_replace('/\s+/', '', substr($cs, $i + 1, $end - $i - 1));
                $items[] = ['type' => 'hex', 'value' => (string) $hex];
                $i = $end + 1;

                continue;
            }

            $numStart = $i;
            while ($i < $len && ! str_contains(" \t\n\r\f\0()<>[]", $cs[$i])) {
                $i++;
            }
            $tok = substr($cs, $numStart, $i - $numStart);
            if ($tok !== '' && is_numeric($tok)) {
                $items[] = ['type' => 'number', 'value' => (float) $tok];
            }
        }

        return [$items, $i];
    }

    /**
     * Mendekode satu token teks (hex maupun literal) menggunakan CMap font aktif.
     *
     * @param  array{type: string, value: mixed}  $token
     * @param  array<string, array{byte_length: int, map: array<string, string>}>  $fontCMaps
     * @param  array{byte_length: int, map: array<string, string>}  $fallbackCMap
     * @param  array<string, string>  $aggregatedCMap1
     * @param  array<string, string>  $aggregatedCMap2
     */
    private function decodeTextToken(
        array $token,
        string $curFont,
        array $fontCMaps,
        array $fallbackCMap,
        array $aggregatedCMap1,
        array $aggregatedCMap2,
    ): string {
        $fontCMap = $fontCMaps[$curFont] ?? null;

        if ($token['type'] === 'hex') {
            $hex = strtoupper((string) $token['value']);
            if ($hex === '') {
                return '';
            }

            $byteLen = $fontCMap['byte_length'] ?? $fallbackCMap['byte_length'];
            $activeMap = $fontCMap['map'] ?? $fallbackCMap['map'];
            $fallbackMap = $byteLen === 1 ? $aggregatedCMap1 : $aggregatedCMap2;
            $chunkSize = $byteLen * 2;

            $decoded = '';
            foreach (str_split($hex, $chunkSize) as $h) {
                $h = str_pad($h, $chunkSize, '0', STR_PAD_LEFT);
                $decoded .= $activeMap[$h] ?? ($fallbackMap[$h] ?? '');
            }

            return $decoded;
        }

        // Literal string: cek apakah font aktif memakai CMap (1-byte Nitro Pro atau 2-byte CID)
        $bytes = (string) $token['value'];
        if ($bytes === '') {
            return '';
        }

        if ($fontCMap !== null && $fontCMap['map'] !== []) {
            if ($fontCMap['byte_length'] === 1) {
                return $this->decodeBytesWith1ByteMap($bytes, $fontCMap['map'], $aggregatedCMap1);
            }

            if (strlen($bytes) % 2 === 0) {
                $decoded2 = $this->decodeBytesWith2ByteMap($bytes, $fontCMap['map'], $aggregatedCMap2);
                if ($decoded2 !== '') {
                    return $decoded2;
                }
            }
        }

        // Fallback untuk PDF hasil incremental update yang memakai alias font (mis. /F0) dengan 2-byte CID literal string (\x00\x38)
        if ($aggregatedCMap2 !== [] && strlen($bytes) >= 2 && strlen($bytes) % 2 === 0 && str_contains($bytes, "\0")) {
            $decoded2 = $this->decodeBytesWith2ByteMap($bytes, $aggregatedCMap2, $aggregatedCMap2);
            if ($decoded2 !== '') {
                return $decoded2;
            }
        }

        // Fallback untuk literal string 1-byte non-printable bila tersedia CMap 1-byte
        if ($aggregatedCMap1 !== [] && preg_match('/[\x01-\x1F]/', $bytes) === 1) {
            return $this->decodeBytesWith1ByteMap($bytes, $aggregatedCMap1, $aggregatedCMap1);
        }

        return $bytes;
    }

    /**
     * @param  array<string, string>  $primary
     * @param  array<string, string>  $fallback
     */
    private function decodeBytesWith1ByteMap(string $bytes, array $primary, array $fallback): string
    {
        $out = '';
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i++) {
            $hex = strtoupper(bin2hex($bytes[$i]));
            if (isset($primary[$hex])) {
                $out .= $primary[$hex];
            } elseif (isset($fallback[$hex])) {
                $out .= $fallback[$hex];
            } elseif ($bytes[$i] === ' ') {
                $out .= ' ';
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $primary
     * @param  array<string, string>  $fallback
     */
    private function decodeBytesWith2ByteMap(string $bytes, array $primary, array $fallback): string
    {
        $out = '';
        $len = strlen($bytes);
        for ($i = 0; $i + 1 < $len; $i += 2) {
            $hex = strtoupper(bin2hex($bytes[$i].$bytes[$i + 1]));
            if (isset($primary[$hex])) {
                $out .= $primary[$hex];
            } elseif (isset($fallback[$hex])) {
                $out .= $fallback[$hex];
            }
        }

        return $out;
    }
}

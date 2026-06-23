<?php
class SimplePDFParser {
    private $content;
    private $font_to_unicode = []; // maps font_name -> [src_hex => dest_char]
    private $pages = [];

    public function __construct($filename) {
        $this->content = file_get_contents($filename);
        $this->parseFonts();
        $this->parsePages();
    }

    // Helper to find the exact offset of an object definition (prevents suffix matching)
    private function findObjectOffset($id) {
        if (preg_match("/\b{$id}\s+0\s+obj\b/i", $this->content, $m, PREG_OFFSET_CAPTURE)) {
            return $m[0][1];
        }
        return false;
    }

    private function getObjectContent($id) {
        $pos = $this->findObjectOffset($id);
        if ($pos === false) return "";
        $end = strpos($this->content, "endobj", $pos);
        if ($end === false) return "";
        return substr($this->content, $pos, $end - $pos + 6);
    }

    private function parseFonts() {
        // 1. Find all font dictionaries with ToUnicode
        $font_to_cmap_obj = [];
        $pos = 0;
        while (($pos = strpos($this->content, '/Type /Font', $pos)) !== false) {
            $start = $pos;
            while ($start > 0 && substr($this->content, $start, 3) !== 'obj') {
                $start--;
            }
            $start_obj = $start;
            while ($start_obj > 0 && $this->content[$start_obj] !== "\n" && $this->content[$start_obj] !== "\r") {
                $start_obj--;
            }
            $header = trim(substr($this->content, $start_obj, $pos - $start_obj));
            if (preg_match("/\b(\d+)\s+\d+\s+obj/i", $header, $m)) {
                $font_obj_id = $m[1];
                
                // Find ToUnicode in this object
                $end_pos = strpos($this->content, "endobj", $pos);
                $obj_content = substr($this->content, $start_obj, $end_pos - $start_obj + 6);
                if (preg_match("/\/ToUnicode\s+(\d+)\s+\d+\s+R/i", $obj_content, $m2)) {
                    $font_to_cmap_obj[$font_obj_id] = $m2[1];
                }
            }
            $pos += 11;
        }

        // 2. Parse CMaps
        $cmaps = [];
        foreach ($font_to_cmap_obj as $font_id => $cmap_obj_id) {
            $cmap = $this->parseCMap($cmap_obj_id);
            if (!empty($cmap)) {
                $cmaps[$font_id] = $cmap;
            }
        }

        // 3. For each page, we map FTxx to font object ID
        preg_match_all("/\/Font\s*<<\s*(.*?)\s*>>/is", $this->content, $resource_matches);
        foreach ($resource_matches[1] as $res_content) {
            preg_match_all("/\/(FT\d+)\s+(\d+)\s+\d+\s+R/i", $res_content, $ft_matches);
            foreach ($ft_matches[1] as $idx => $ft_name) {
                $font_obj_id = $ft_matches[2][$idx];
                if (isset($cmaps[$font_obj_id])) {
                    $this->font_to_unicode[$ft_name] = $cmaps[$font_obj_id];
                }
            }
        }
    }

    private function parseCMap($cmap_obj_id) {
        $obj_content = $this->getObjectContent($cmap_obj_id);
        if (empty($obj_content)) return null;
        
        if (!preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
            return null;
        }

        $decompressed = @gzuncompress($stream_match[1]);
        if ($decompressed === false) {
            $decompressed = @gzuncompress(substr($stream_match[1], 2));
        }
        if ($decompressed === false) return null;

        $map = [];
        // Parse bfchar: <src> <dest>
        if (preg_match_all("/<([0-9a-fA-F]+)>[ \t]+<([0-9a-fA-F]+)>/i", $decompressed, $bfchar_matches)) {
            foreach ($bfchar_matches[1] as $idx => $src) {
                $dest = $bfchar_matches[2][$idx];
                $map[strtolower($src)] = html_entity_decode("&#" . hexdec($dest) . ";", ENT_NOQUOTES, 'UTF-8');
            }
        }

        // Parse bfrange: <src_start> <src_end> <dest_start>
        if (preg_match_all("/<([0-9a-fA-F]+)>[ \t]+<([0-9a-fA-F]+)>[ \t]+<([0-9a-fA-F]+)>/i", $decompressed, $bfrange_matches)) {
            foreach ($bfrange_matches[1] as $idx => $src_start) {
                $src_end = $bfrange_matches[2][$idx];
                $dest_start = $bfrange_matches[3][$idx];
                
                $start_val = hexdec($src_start);
                $end_val = hexdec($src_end);
                $dest_val = hexdec($dest_start);
                
                $len = strlen($src_start);
                for ($v = $start_val; $v <= $end_val; $v++) {
                    $src_hex = str_pad(dechex($v), $len, "0", STR_PAD_LEFT);
                    $dest_hex = dechex($dest_val + ($v - $start_val));
                    $map[strtolower($src_hex)] = html_entity_decode("&#" . hexdec($dest_hex) . ";", ENT_NOQUOTES, 'UTF-8');
                }
            }
        }

        return $map;
    }

    private function resolvePageKids($id) {
        $obj = $this->getObjectContent($id);
        if (empty($obj)) return [];
        
        if (preg_match("/\/Kids\s*\[(.*?)\]/is", $obj, $m)) {
            preg_match_all("/(\d+)\s+\d+\s+R/i", $m[1], $m_ids);
            $pages = [];
            foreach ($m_ids[1] as $kid_id) {
                $kid_obj = $this->getObjectContent($kid_id);
                if (preg_match("/\/Type\s*\/Page\b/i", $kid_obj)) {
                    $pages[] = $kid_id;
                } else if (preg_match("/\/Type\s*\/Pages\b/i", $kid_obj)) {
                    $pages = array_merge($pages, $this->resolvePageKids($kid_id));
                }
            }
            return $pages;
        }
        return [];
    }

    private function parsePages() {
        // Trace pages from catalog
        preg_match("/\b(\d+)\s+\d+\s+obj\s*<<\s*\/Type\s*\/Catalog/i", $this->content, $m);
        if (!$m) {
            preg_match("/\/Root\s+(\d+)\s+\d+\s+R/i", $this->content, $m);
        }
        if (!$m) return;
        
        $catalog_id = $m[1];
        $catalog_content = $this->getObjectContent($catalog_id);
        if (empty($catalog_content)) return;
        
        if (preg_match("/\/Pages\s+(\d+)\s+\d+\s+R/i", $catalog_content, $m2)) {
            $pages_root_id = $m2[1];
            $page_ids = $this->resolvePageKids($pages_root_id);
            
            foreach ($page_ids as $page_id) {
                $page_obj = $this->getObjectContent($page_id);
                
                $contents_ids = [];
                if (preg_match("/\/Contents\s+(\d+)\s+\d+\s+R/i", $page_obj, $m3)) {
                    $contents_ids[] = $m3[1];
                } else if (preg_match("/\/Contents\s*\[(.*?)\]/is", $page_obj, $m3)) {
                    preg_match_all("/(\d+)\s+\d+\s+R/i", $m3[1], $m_ids);
                    $contents_ids = $m_ids[1];
                }
                
                $page_text = "";
                foreach ($contents_ids as $stream_obj_id) {
                    $page_text .= $this->extractStreamText($stream_obj_id);
                }
                $this->pages[] = $page_text;
            }
        }
    }

    private function extractStreamText($stream_obj_id) {
        $obj_content = $this->getObjectContent($stream_obj_id);
        if (empty($obj_content)) return "";
        
        if (!preg_match("/stream[\r\n]+(.*?)[\r\n]+endstream/is", $obj_content, $stream_match)) {
            return "";
        }

        $decompressed = @gzuncompress($stream_match[1]);
        if ($decompressed === false) {
            $decompressed = @gzuncompress(substr($stream_match[1], 2));
        }
        if ($decompressed === false) return "";

        // Parse BT ... ET text blocks
        preg_match_all("/BT(.*?)ET/is", $decompressed, $bt_matches);
        
        $text = "";
        foreach ($bt_matches[0] as $bt) {
            $current_font = "default";
            
            // Parse tokens: Tf (font setting), Tj (single text), TJ (array text)
            preg_match_all("/\/(\w+)\s+\d+\s+Tf|(<[0-9a-fA-F]+>)\s*Tj|\[(.*?)\]\s*TJ/is", $bt, $tokens, PREG_SET_ORDER);
            
            foreach ($tokens as $t) {
                if (strpos($t[0], 'Tf') !== false) {
                    $current_font = $t[1];
                } else if (strpos($t[0], 'Tj') !== false) {
                    $hex = strtolower(trim($t[2], '<>'));
                    $text .= $this->decodeHex($hex, $current_font);
                } else if (strpos($t[0], 'TJ') !== false) {
                    preg_match_all("/<([0-9a-fA-F]+)>/i", $t[3], $hex_elements);
                    foreach ($hex_elements[1] as $hex) {
                        $text .= $this->decodeHex(strtolower($hex), $current_font);
                    }
                    $text .= " ";
                }
            }
            $text .= "\n";
        }
        
        // Restore spaces: convert multiple spaces into a vertical bar divider, strip single
        // spaces (which represent character kerning offsets), and restore bar to single space.
        $clean = preg_replace("/\s{2,}/", " | ", $text);
        $clean = str_replace(" ", "", $clean);
        $clean = str_replace("|", " ", $clean);
        
        return $clean . "\n";
    }

    private function decodeHex($hex, $font) {
        $res = "";
        $map = $this->font_to_unicode[$font] ?? null;
        
        $len = 4;
        if ($map) {
            $keys = array_keys($map);
            if (!empty($keys) && strlen($keys[0]) == 2) {
                $len = 2;
            }
        }
        
        for ($i = 0; $i < strlen($hex); $i += $len) {
            $sub = substr($hex, $i, $len);
            if ($map && isset($map[$sub])) {
                $res .= $map[$sub];
            } else {
                // Fallback: standard shift of +29
                $shifted = hexdec($sub) + 29;
                $res .= html_entity_decode("&#$shifted;", ENT_NOQUOTES, 'UTF-8');
            }
        }
        return $res;
    }

    public function getText() {
        return implode("\n--- PAGE BREAK ---\n", $this->pages);
    }
}
?>

<?php

namespace Botkaplus;

class Markdown {
    private $MARKDOWN_RE;
    private $MARKDOWN_TYPES;

    public function __construct() {
        // الگوی عبارات منظم برای شناسایی نشانه‌گذاری‌های Markdown
        $this->MARKDOWN_RE = '/```(.*?)```|\*\*(.*?)\*\*|`(.*?)`|__(.*?)__|--(.*?)--|~~(.*?)~~|\|\|(.*?)\|\||\[(.*?)\]\((\S+)\)/s';
        
        // نگاشت نوع نشانه‌گذاری به گروه مربوطه در الگوی رجکس
        $this->MARKDOWN_TYPES = [
            '```' => ['Pre', 1],
            '**' => ['Bold', 2],
            '`' => ['Mono', 3],
            '__' => ['Italic', 4],
            '--' => ['Underline', 5],
            '~~' => ['Strike', 6],
            '||' => ['Spoiler', 7],
            '[' => ['Link', 8]
        ];
    }


    private function javaLikeLength($text) {
        return strlen(mb_convert_encoding($text, 'UTF-16BE')) / 2;
    }

    private static function utf16Len($str) {
        return strlen(mb_convert_encoding($str, 'UTF-16BE', 'UTF-8')) / 2;
    }


    public function toMetadata2($text){
        preg_match_all($this->MARKDOWN_RE, $text, $matches, PREG_OFFSET_CAPTURE);

        $offset = 0;
        $metaDataParts = [];

        foreach ($matches[0] as $i => $match) {
            $group = $match[0];
            $start = $match[1];
            $adjustedStart = $this->javaLikeLength(substr($text, 0, $start)) - $offset;

            foreach ($this->MARKDOWN_TYPES as $prefix => [$mdType, $groupIdx]) {
                if (strpos($group, $prefix) === 0) {
                    $content = $matches[$groupIdx][$i][0];
                    $contentLength = $this->javaLikeLength($content);

                    $metaDataParts[] = [
                        'type' => $mdType,
                        'from_index' => $adjustedStart,
                        'length' => $contentLength
                    ];
                }
            }
        }

        return $metaDataParts;
    }

    public function transcribe($src) {
        $payloadParts = [];
        $normalizedText = $src;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $src, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + strlen($whole);

            $adjFrom = self::utf16Len(substr($src, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            $gname = null;
            foreach (self::$typeMap as $key => $type) {
                if (!empty($m[$key][0])) {
                    $gname = $key;
                    break;
                }
            }
            if (!$gname) continue;

            $inner = "";
            $linkHref = null;
            if ($gname === "link") {
                $inner = $m["link_text"][0] ?? "";
                $linkHref = $m["link_url"][0] ?? "";
            } else {
                $inner = $m["{$gname}_c"][0] ?? "";
            }

            if ($gname === "quote") {
                $innerMeta = $this->transcribe($inner, "MARKDOWN");
                $inner = $innerMeta["text"];
                if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                    foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                        $part["from_index"] += $adjFrom;
                        $payloadParts[] = $part;
                    }
                }
            }

            if ($inner === "") continue;

            $contentLen = self::utf16Len($inner);
            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $contentLen,
            ];
            if ($linkHref) {
                $part["link_url"] = $linkHref;
            }
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - $contentLen;
            $charOffset += strlen($whole) - strlen($inner);
        }

        $result = ["text" => trim($normalizedText)];
        if (!empty($payloadParts)) {
            $result["meta_data_parts"] = $payloadParts;
        }
        
        return $result;
    }

    public function toMetadata($text) {
        $metaDataParts = [];
        $currentText = $text;

        while (preg_match($this->MARKDOWN_RE, $currentText, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $start = $matches[0][1];

            foreach ($this->MARKDOWN_TYPES as $prefix => [$mdType, $groupIdx]) {
                if (strpos($fullMatch, $prefix) === 0) {
                    $content = $matches[$groupIdx][0];
                    $adjustedStart = $this->javaLikeLength(substr($currentText, 0, $start));

                    // پاک‌سازی نشانه‌گذاری‌ها برای محاسبه طول دقیق
                    $pattern_ = '/\|\|{1,}|~~{1,}|--{1,}|__{1,}|`|[\*]{2,}|```/';
                    $length_t = preg_replace($pattern_, '', $content);
                    $length_t = preg_replace('/\s+/', ' ', trim($length_t));
                    $pattern_link = '/\[(.*?)\]\((.*?)\)/';
                    $length_t = preg_replace($pattern_link, '$1', $length_t);
                    $length_text = mb_strlen($length_t);

                    // ساخت متادیتای اصلی
                    $metaDataPart = [
                        'type' => $mdType,
                        'from_index' => $adjustedStart,
                        'length' => $length_text
                    ];

                    // افزودن اطلاعات اضافی برای نوع Pre
                    if ($mdType === 'Pre') {
                        $lines = explode("\n", $content, 2);
                        $language = trim($lines[0]);
                        $metaDataPart['language'] = $language ?: "";
                    }

                    // افزودن اطلاعات اضافی برای نوع Link
                    elseif ($mdType === 'Link') {
                        $url = $matches[9][0] ?? '';
                        $label = $matches[8][0] ?? '';

                        $mentionTypes = ['u' => 'User', 'g' => 'Group', 'c' => 'Channel', 'b' => 'Bot'];
                        $mentionType = $mentionTypes[$url[0]] ?? 'hyperlink';

                        if ($mentionType === 'hyperlink') {
                            $metaDataPart['link'] = [
                                'type' => $mentionType,
                                'hyperlink_data' => ['url' => $url]
                            ];
                        } else {
                            $metaDataPart['type'] = 'MentionText';
                            $metaDataPart['mention_text_object_guid'] = $url;
                            $metaDataPart['mention_text_object_type'] = $mentionType;
                        }
                    }

                    $metaDataParts[] = $metaDataPart;

                    // ثبت متادیتاهای تو در تو
                    $mt2Parts = $this->toMetadata2(trim($content));
                    foreach ($mt2Parts as $mt2) {
                        $metaDataParts[] = [
                            'type' => $mt2['type'],
                            'from_index' => $adjustedStart + $mt2['from_index'],
                            'length' => $length_text
                        ];
                    }

                    // حذف markdown از متن و جایگزینی با محتوای خالص
                    $before = substr($currentText, 0, $start);
                    $after = substr($currentText, $start + strlen($fullMatch));
                    $currentText = $before . $content . $after;

                    break;
                }
            }
        }

        // حذف متادیتاهای تکراری
        $metaDataParts = array_values(array_unique(array_map(function($part) {
            return json_encode($part);
        }, $metaDataParts)));

        $metaDataParts = array_map(function($json) {
            return json_decode($json, true);
        }, $metaDataParts);

        $finalText = trim($currentText);

        $result = ['text' => $finalText];

        $metaDataParts = array_filter($metaDataParts, function($part) {
            if ($part['type'] === 'Link') {
                return isset($part['link']['type']) &&
                    $part['link']['type'] === 'hyperlink' &&
                    isset($part['link']['hyperlink_data']['url']) &&
                    !empty($part['link']['hyperlink_data']['url']);
            }
            return true; // سایر انواع رو نگه دار
        });

        $metaDataParts = array_values($metaDataParts);

        if ($metaDataParts) {
            $result['metadata'] = ['meta_data_parts' => $metaDataParts];
        }

        return $result;
    }

}

?>

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
                    else if ($mdType === 'Link') {
                        $url = $matches[9][0] ?? '';
                        $label = $matches[8][0] ?? '';

                        $mentionTypes = ['u' => 'User', 'g' => 'Group', 'c' => 'Channel', 'b' => 'Bot'];
                        $mentionType = $mentionTypes[$url[0]] ?? 'hyperlink';

                        if ($mentionType === 'hyperlink') {
                            $metaDataPart["link_url"] = $url;
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

        $metaDataParts = array_filter($metaDataParts, function($part) {
            if ($part['type'] === 'Link') {
                return !empty($part['link_url']) || isset($part['link']['type']);
            }
            return true; // سایر انواع رو نگه دار
        });

        $metaDataParts = array_values($metaDataParts);

        $result = ['text' => $finalText];

        if ($metaDataParts) {
            $result['metadata'] = ['meta_data_parts' => $metaDataParts];
        }

        return $result;
    }

}

?>

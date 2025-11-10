<?php
namespace Botkaplus;

use Botkaplus\BotClient;

class Message {
    private $bot;
    private $message;
    private $new_message; // پیام خام برای فیلترها

    // فیلدهای پیام
    private $text;
    private $timee;
    private $chat_id;
    private $sender_id;
    private $message_id;
    private $is_edited;
    private $sender_type;
    private $reply_to_message_id;

    // فیلدهای inline
    private $inline_message;
    private $aux_data;
    private $location;

    // فیلدهای پیام ویرایش شده
    public $updated_message;
    public $text_edit;

    public function __construct(BotClient $bot, $rData) {
        $this->bot = $bot;
        $this->message              = $rData->update ?? null;
        $this->new_message          = $this->message->new_message ?? null;
        $this->inline_message       = $rData->inline_message ?? null;
        $this->updated_message      = $this->message->updated_message ?? null;

        // پیام معمولی
        if (isset($this->message->type) && $this->new_message) {
            $this->text                 = $this->new_message->text ?? null;
            $this->timee                = $this->new_message->time ?? null;
            $this->chat_id              = $this->message->chat_id ?? null;
            $this->sender_id            = $this->new_message->sender_id ?? null;
            $this->sender_type          = $this->new_message->sender_type ?? null;
            $this->message_id           = $this->new_message->message_id ?? null;
            $this->is_edited            = $this->new_message->is_edited ?? false;
            $this->reply_to_message_id  = $this->new_message->reply_to_message_id ?? null;
        }else if (isset($this->message->type) && $this->message->type ?? "null" === "UpdatedMessage") { // پیام ویرایش شده
            $this->chat_id              = $this->message->chat_id ?? null;
            $this->message_id           = $this->updated_message->message_id ?? null;
            $this->text_edit            = $this->updated_message->text ?? null;
            $this->timee                = $this->updated_message->time ?? null;
            $this->is_edited            = $this->updated_message->is_edited ?? true;
            $this->sender_type          = $this->updated_message->sender_type ?? null;
            $this->sender_id            = $this->updated_message->sender_id ?? null;
        }else if ($this->inline_message) { // پیام اینلاین
            $this->text                 = $this->inline_message->text ?? null;
            $this->chat_id              = $this->inline_message->chat_id ?? null;
            $this->sender_id            = $this->inline_message->sender_id ?? null;
            $this->message_id           = $this->inline_message->message_id ?? null;
            $this->aux_data             = $this->inline_message->aux_data ?? null;
            $this->location             = $this->inline_message->location ?? null;
        }
    }

    /**
     * ریپلای پیام به کاربر
     *
     * این متد یک پیام متنی به کاربر ریپلای می‌کند.
     *
     * @param string $text متن پیام
     * @return stdClass شیء پاسخ از سرور. موفقیت یا شکست ارسال پیام
     */
    public function replyMessage($text, $parse_mode = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendMessage($this->chat_id, $text, $parse_mode, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyPoll(string $question, array $options, $type = "Regular", $allows_multiple_answers = null, $is_anonymous = true, $correct_option_index = null, $hint = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendPoll($this->chat_id, $question, $options, $type, $allows_multiple_answers, $is_anonymous, $correct_option_index, $hint, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyLocation($latitude, $longitude, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendLocation($this->chat_id, $latitude, $longitude, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyContact($first_name, $last_name, $phone_number, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendContact($this->chat_id, $first_name, $last_name, $phone_number, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Sticker($sticker_id, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendSticker($this->chat_id, $sticker_id, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function getChat() {
        return $this->bot->getChat(chat_id:$this->chat_id);
    }

    public function getFirstName() {
        $chat_info = json_decode($this->bot->getChat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->first_name ?? null;}
    }

    public function getLastName() {
        $chat_info = json_decode($this->bot->getChat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->last_name ?? null;}
    }

    public function getUsername() {
        $chat_info = json_decode($this->bot->getChat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->username ?? null;}
    }

    public function getGroupName() {
        $chat_info = json_decode($this->bot->getChat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Group") {return $chat_info->data->chat->title ?? null;}
    }

    public function getChannelName() {
        $chat_info = json_decode($this->bot->getChat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Channel") {return $chat_info->data->chat->title ?? null;}
    }

    public function deleteMessage($id_message = null) {
        if ($id_message === null) {$id_message = $this->message_id;}
        return $this->bot->deleteMessage($this->chat_id, $id_message);
    }

    // public function replyDeleteMessage

    public function deleteChatKeypad() {
        return $this->bot->deleteChatKeypad($this->chat_id);
    }

    public function replyFileById($file_id, $caption = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->sendFileById($this->chat_id, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyFile(?string $file_path = null, ?string $file_id = null, ?string $file_type = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendFile($this->chat_id, $file_path, $file_id, $file_type, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyImage(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendImage($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Voice(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendVoice($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyMusic(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendMusic($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyGif(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendGif($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function replyVideo(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->sendVideo($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    // public function __toString() {
    //     return json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    //     // return "message: " . json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // }
}
?>


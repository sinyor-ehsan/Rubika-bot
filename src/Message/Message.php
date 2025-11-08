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
    public function reply_Message($text, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Message($this->chat_id, $text, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Poll(string $question, array $options, $type = "Regular", $allows_multiple_answers = null, $is_anonymous = true, $correct_option_index = null, $hint = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Poll($this->chat_id, $question, $options, $type, $allows_multiple_answers, $is_anonymous, $correct_option_index, $hint, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Location($latitude, $longitude, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Location($this->chat_id, $latitude, $longitude, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Contact($first_name, $last_name, $phone_number, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Contact($this->chat_id, $first_name, $last_name, $phone_number, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Sticker($sticker_id, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Sticker($this->chat_id, $sticker_id, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function get_Chat() {
        return $this->bot->get_Chat(chat_id:$this->chat_id);
    }

    public function get_First_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->first_name ?? null;}
    }

    public function get_Last_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->last_name ?? null;}
    }

    public function get_Username() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->username ?? null;}
    }

    public function get_Group_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Group") {return $chat_info->data->chat->title ?? null;}
    }

    public function get_Channel_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Channel") {return $chat_info->data->chat->title ?? null;}
    }

    public function delete_Message($id_message = null) {
        if ($id_message === null) {$id_message = $this->message_id;}
        return $this->bot->delete_Message($this->chat_id, $id_message);
    }

    // public function reply_delete_Message

    public function delete_ChatKeypad() {
        return $this->bot->delete_ChatKeypad($this->chat_id);
    }

    public function reply_File_by_id($file_id, $caption = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_File_by_id($this->chat_id, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_File(?string $file_path = null, ?string $file_id = null, ?string $file_type = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_File($this->chat_id, $file_path, $file_id, $file_type, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Image(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_Image($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Voice(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_Voice($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Music(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_Music($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Gif(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_Gif($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Video(?string $file_path = null, ?string $file_id = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_Video($this->chat_id, $file_path, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    // public function __toString() {
    //     return json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    //     // return "message: " . json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // }
}
?>
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
    public function reply_Message($text, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Message($this->chat_id, $text, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Poll(string $question, array $options) {
        return $this->bot->send_Poll($this->chat_id, $question, $options, $this->message_id);
    }

    public function reply_Location($latitude, $longitude, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Location($this->chat_id, $latitude, $longitude, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Contact($first_name, $last_name, $phone_number, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Contact($this->chat_id, $first_name, $last_name, $phone_number, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_Sticker($sticker_id, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_Sticker($this->chat_id, $sticker_id, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function get_Chat() {
        return $this->bot->get_Chat(chat_id:$this->chat_id);
    }

    public function get_First_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->first_name ?? null;}
    }

    public function get_Last_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->last_name ?? null;}
    }

    public function get_Username() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "User") {return $chat_info->data->chat->username ?? null;}
    }

    public function get_Group_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Group") {return $chat_info->data->chat->title ?? null;}
    }

    public function get_Channel_Name() {
        $chat_info = json_decode($this->bot->get_Chat(chat_id:$this->chat_id));
        if ($chat_info->data->chat->chat_type == "Channel") {return $chat_info->data->chat->title ?? null;}
    }

    public function delete_Message($id_message = null) {
        if ($id_message === null) {$id_message = $this->message_id;}
        return $this->bot->delete_Message($this->chat_id, $id_message);
    }

    // public function reply_delete_Message

    public function delete_ChatKeypad() {
        return $this->bot->delete_ChatKeypad($this->chat_id);
    }

    public function reply_File_by_id($file_id, $caption = null, $inline_keypad = null, $chat_keypad = null, $chat_keypad_type = "New") {
        return $this->bot->send_File_by_id($this->chat_id, $file_id, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    public function reply_File(?string $file_path = null, ?string $file_id = null, ?string $file_type = null, ?string $caption = null, ?array $inline_keypad = null, ?array $chat_keypad = null, string $chat_keypad_type = 'New') {
        return $this->bot->send_File($this->chat_id, $file_path, $file_id, $file_type, $caption, $inline_keypad, $chat_keypad, $chat_keypad_type, $this->message_id);
    }

    // public function __toString() {
    //     return json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    //     // return "message: " . json_encode($this->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // }
}
?>


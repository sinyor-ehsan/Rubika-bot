# 📚 PHP Library for Rubika bot
Botkaplus Library for rubika bots.



# Botkaplus
  <img align="center" width="200" height="200" src="https://rubika.ir/static/images/logo.svg"/>
Botkaplus Library for rubika bots.

باتکاپلاس کتابخانه ای برای بات های روبیکا

# 📦 نصب و راه‌ اندازی

پیش نیاز

· PHP 7.4 or higher
/// · curl enable
/// · token rubika bot

# نصب
 نصب کردن فایل‌های کتابخانه
```php
composer require sinyor-ehsan/botkaplus
```

# شروع

```php
<?php

require "vendor/autoload.php";
use Botkaplus\BotClient;
use Botkaplus\Filters;
use Botkaplus\Message;

$token = "token_bot";

$bot = new BotClient($token);

$bot->onMessage(null, function(BotClient $bot, Message $message) {
        $message->reply_Message("hello from Botkaplus!");
    }
);
$bot->runPolling();

?>
```

# شروع با webHook

```php
<?php

require "vendor/autoload.php";
use Botkaplus\BotClient;
use Botkaplus\Filters;
use Botkaplus\Message;

$token = "token_bot";
$inData = file_get_contents('php://input');
$Data = json_decode($inData);

$bot = new BotClient($token, $Data);

$bot->onMessage(Filters::text("hello"), function(BotClient $bot, Message $message) {
        $message->reply_Message("hello from Botkaplus!");
    }
);
$bot->run();

?>
```

# ارسال اینلاین کیبورد
```php
use Botkaplus\KeypadInline;

$keypad = new KeypadInline();

// ردیف اول
$keypad->addRow([
    KeypadInline::simpleButton("100", "Botkaplus 1")
]);

// ردیف دوم
$keypad->addRow([
    KeypadInline::simpleButton("101", "Botkaplus 2"),
    KeypadInline::simpleButton("101", "Botkaplus 2")
]);

$inline_keypad = $keypad->build();
$message->reply_Message("send inline keypad!", $inline_keypad);
```

# ارسال اینلاین Button
```php
use Botkaplus\KeypadChat;

$chat_keypad = new KeypadChat();

// ردیف اول
$chat_keypad->addRow([
    KeypadChat::simpleButton("100", "Botkaplus 1")
]);

// ردیف دوم
$chat_keypad->addRow([
    KeypadChat::simpleButton("101", "Botkaplus 2"),
    KeypadChat::simpleButton("101", "Botkaplus 3")
]);

$chat_keypad->setResizeKeyboard(true);
$chat_keypad->setOnTimeKeyboard(true);

$chat_keypad = $chat_keypad->build();
$message->reply_Message("send chat keypad!", chat_keypad:$chat_keypad);
```

# ادامه ندادن به هندلرهای بعدی
```php
$bot->stopPropagation()
```

# فیلتر text
```php
$bot->onMessage(Filters::text("hello"), function(BotClient $bot, Message $message){
    $message->reply_Message("hello from Botkaplus!");
});
```

# فیلتر ترکیبی and
```php
$bot->onMessage(Filters::and(Filters::private(), Filters::command("start")), function(BotClient $bot, Message $message){
    $message->reply_Message("hello from Botkaplus to pv!");
});
```
# انواع فیلترها
```php
Filters::text("")
Filters::regex("")
Filters::command("")
Filters::chatId("")
Filters::senderId("")
Filters::buttonId("")
Filters::private()
Filters::group()
Filters::channel()
Filters::or(...)
Filters::and(...)
Filters::not(...)
```
# تنظیم کامندها
```php
$bot->set_Commands([["command" => "start", "description" => "شروع ربات"], ["command" => "help", "description" => "راهنمای ربات"]]);
```

#ارسال نظرسنجی
```php
// chat_id شناسه چت مقصد
// question متن سوال
// options array[string] گزینه های سوال
// type ["Regular", "Quiz"] = "Regular" نوع
// allows_multiple_answers .کاربرد دارد "regular" فقط برای نوع e انتخاب چند گزینه
// is_anonymous باشد، رأی‌دهی ناشناس است و نام رأی‌دهندگان نمایش داده نمی‌شود true اگر 
// correct_option_index "Quiz" ایندکس گزینه درست در حالت 
// hint توضیح نظرسنجی
$bot->send_Poll(chat_id:$bot->chat_id, question:"سوال", options:["one", "two"], type:"Quiz", is_anonymous:false, correct_option_index:"0", hint:"توضیحات")
```

<?php
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/docx_helper.php';

// --- Контакты ---
$organization = sitePostValue("organization");
$emailRaw = siteRawPostValue("email");
$email = htmlspecialchars($emailRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$phone = sitePostValue("phone");

// --- Основные пункты ---
$one = sitePostValue("one");
$two = sitePostValue("two");
$three = sitePostValue("three");
$three_one = sitePostValue("three_one");
$four = sitePostValue("four");
$four_one = sitePostValue("four_one");
$five = sitePostValue("five");
$six = sitePostValue("six");
$seven_one = sitePostValue("seven_one");
$seven_two = sitePostValue("seven_two");
$eight_one = sitePostValue("eight_one");

// --- Дополненные пункты ---
$eight_two   = sitePostValue("eight_two");
$eight_three = sitePostValue("eight_three");
$eight_four  = sitePostValue("eight_four");
$eight_five  = sitePostValue("eight_five");
$eight_six   = sitePostValue("eight_six");
$eight_seven = sitePostValue("eight_seven");
$eight_eight = sitePostValue("eight_eight");
$eight_nine  = sitePostValue("eight_nine");
$eight_ten   = sitePostValue("eight_ten");

$nine_one   = sitePostValue("nine_one");
$nine_two   = sitePostValue("nine_two");
$nine_three = sitePostValue("nine_three");
$nine_four  = sitePostValue("nine_four");
$nine_five  = sitePostValue("nine_five");
$nine_six   = sitePostValue("nine_six");

$ten = sitePostValue("ten");

// --- Подписи ---
$date_field = sitePostValue("date");
$position   = sitePostValue("position");
$fio        = sitePostValue("fio");
$signature  = sitePostValue("signature");

$questionnaireId = siteGenerateQuestionnaireId('ТМ');
$attachmentName = preg_replace('/^ТМ-/u', 'ТДМ-', $questionnaireId) . '.docx';
$subject = "Опросный лист: Тягодутьевая машина {$questionnaireId}";
$dateTime = date("d.m.Y H:i");

// --- HTML-сообщение ---
$message = "
<html>
<head>
<style>
body { font-family: Arial, sans-serif; }
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 10px;
}
th, td {
  border: 1px solid #999;
  padding: 6px 8px;
  vertical-align: top;
  text-align: left;
}
th { background-color: #f2f2f2; text-align: center; }
small { color: #666; font-size: 0.9em; }
</style>
</head>
<body>

<h3>Опросный лист: Тягодутьевая машина</h3>
<p><strong>Идентификатор опросного листа:</strong> {$questionnaireId}</p>
<p><strong>Название организации:</strong> {$organization} <br>
<strong>Email:</strong> {$email} <br>
<strong>Телефон:</strong> {$phone}</p>
</body>
</html>
";

$docxResult = siteCreateTdmDocxFile($questionnaireId, [
    'organization' => $organization,
    'email' => $emailRaw,
    'phone' => $phone,
    'one' => $one,
    'two' => $two,
    'three' => $three,
    'three_one' => $three_one,
    'four' => $four,
    'four_one' => $four_one,
    'five' => $five,
    'six' => $six,
    'seven_one' => $seven_one,
    'seven_two' => $seven_two,
    'eight_one' => $eight_one,
    'eight_two' => $eight_two,
    'eight_three' => $eight_three,
    'eight_four' => $eight_four,
    'eight_five' => $eight_five,
    'eight_six' => $eight_six,
    'eight_seven' => $eight_seven,
    'eight_eight' => $eight_eight,
    'eight_nine' => $eight_nine,
    'eight_ten' => $eight_ten,
    'nine_one' => $nine_one,
    'nine_two' => $nine_two,
    'nine_three' => $nine_three,
    'nine_four' => $nine_four,
    'nine_five' => $nine_five,
    'nine_six' => $nine_six,
    'ten' => $ten,
]);

if (!$docxResult['ok']) {
    error_log('DOCX create failed: ' . $docxResult['error']);
    http_response_code(500);
    echo 'Ошибка при подготовке DOCX-файла';
    exit;
}

// --- Отправка письма ---
$mailResult = siteSendMail(
    $subject,
    $message,
    $emailRaw,
    $organization,
    [[
        'path' => $docxResult['path'],
        'name' => $attachmentName,
        'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'delete_after_send' => true,
    ]]
);

if ($mailResult["ok"]) {
    header("Location: success.html");
    exit;
} else {
    http_response_code(500);
    echo "Ошибка при отправке письма";
}
?>

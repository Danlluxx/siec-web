<?php
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/docx_helper.php';

// данные контактов
$organization = sitePostValue("organization");
$emailRaw = siteRawPostValue("email");
$email = htmlspecialchars($emailRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$phone = sitePostValue("phone");

// параметры из таблицы
$G = sitePostValue("G");
$P1 = sitePostValue("P1");
$P2 = sitePostValue("P2");
$T1 = sitePostValue("T1");
$T2 = sitePostValue("T2");
$Pv = sitePostValue("Pv");
$Tv = sitePostValue("Tv");
$execution = sitePostValue("execution");
$special_requirements = sitePostValue("special_requirements");
$D_in = sitePostValue("D_in");
$D_out = sitePostValue("D_out");
$D_cw = sitePostValue("D_cw");
$conn_type = sitePostValue("conn_type");
$supply_type = sitePostValue("supply_type");

if ($supply_type === '') {
    $supply_type = 'не указано';
}

// комплектация
$kit = [];
$kits = [
    "kit_inlet_gate" => "Задвижка на входе",
    "kit_outlet_gate" => "Задвижка на выходе",
    "kit_electro_drive_gate" => "Электропривод на задвижках",
    "kit_manual_drive_gate" => "Ручной привод на задвижке",
    "kit_electro_drive_ctrl" => "Электропривод на регулирующих клапанах",
    "kit_pneumo_drive_ctrl" => "Пневмопривод на регулирующих клапанах",
    "kit_check_valve" => "Обратный клапан",
    "kit_drainage" => "Дренажная система",
    "kit_automation" => "Автоматика и КИП"
];
foreach ($kits as $field => $label) {
    if (!empty($_POST[$field])) {
        $kit[] = $label;
    }
}

$kit_str = !empty($kit) ? implode(", ", $kit) : "не выбрано";

$dateTime = date("d.m.Y H:i");

$questionnaireId = siteGenerateQuestionnaireId('РОУ');
$attachmentName = preg_replace('/^РОУ-/u', 'ROU-', $questionnaireId) . '.docx';
$subject = "Опросный лист: Редукционно-охладительная установка {$questionnaireId}";

$message = "
<html>
<head>
<style>
body { font-family: Arial, sans-serif; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; text-align: left; }
th { background-color: #f2f2f2; text-align: center; }
</style>
</head>
<body>

<h3>Опросный лист: Редукционно-охладительная установка</h3>
<p><strong>Идентификатор опросного листа:</strong> {$questionnaireId}</p>
<p><strong>Название организации:</strong> {$organization} <br>
<strong>Email:</strong> {$email} <br>
<strong>Телефон:</strong> {$phone}</p>
</body>
</html>
";

$docxResult = siteCreateRouDocxFile($questionnaireId, [
    'organization' => $organization,
    'email' => $emailRaw,
    'phone' => $phone,
    'G' => $G,
    'P1' => $P1,
    'P2' => $P2,
    'T1' => $T1,
    'T2' => $T2,
    'Pv' => $Pv,
    'Tv' => $Tv,
    'execution' => $execution,
    'special_requirements' => $special_requirements,
    'D_in' => $D_in,
    'D_out' => $D_out,
    'D_cw' => $D_cw,
    'conn_type' => $conn_type,
    'supply_type' => $supply_type,
    'kit_inlet_gate' => !empty($_POST['kit_inlet_gate']),
    'kit_outlet_gate' => !empty($_POST['kit_outlet_gate']),
    'kit_electro_drive_gate' => !empty($_POST['kit_electro_drive_gate']),
    'kit_manual_drive_gate' => !empty($_POST['kit_manual_drive_gate']),
    'kit_electro_drive_ctrl' => !empty($_POST['kit_electro_drive_ctrl']),
    'kit_pneumo_drive_ctrl' => !empty($_POST['kit_pneumo_drive_ctrl']),
    'kit_check_valve' => !empty($_POST['kit_check_valve']),
    'kit_drainage' => !empty($_POST['kit_drainage']),
    'kit_automation' => !empty($_POST['kit_automation']),
]);

if (!$docxResult['ok']) {
    error_log('DOCX create failed: ' . $docxResult['error']);
    http_response_code(500);
    echo 'Ошибка при подготовке DOCX-файла';
    exit;
}

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

<?php
/**
 * Obsługa formularza kontaktowego – wykładarka.pl
 * Test: wejdź w przeglądarce na https://twoja-domena.pl/mail.php
 */

header('Content-Type: application/json; charset=utf-8');

// --- Konfiguracja ---
define('MAIL_TO', 'biuro@spolex.com');
define('MAIL_FROM', 'biuro@spolex.com'); // użyj istniejącej skrzynki Spolex

// Szybki test: otwórz mail.php w przeglądarce – powinien zwrócić JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(array(
        'ok'      => true,
        'message' => 'mail.php działa. PHP ' . phpversion(),
    ));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'message' => 'Niedozwolona metoda żądania.'));
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    echo json_encode(array('ok' => true, 'message' => 'Dziękujemy — odezwiemy się wkrótce.'));
    exit;
}

function field($key) {
    return isset($_POST[$key]) ? trim(strip_tags($_POST[$key])) : '';
}

$imie    = field('imie');
$firma   = field('firma');
$email   = field('email');
$telefon = field('telefon');
$produkt = field('produkt');

$errors = array();

if ($imie === '') {
    $errors[] = 'Podaj imię i nazwisko.';
}
if ($firma === '') {
    $errors[] = 'Podaj nazwę firmy.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Podaj poprawny adres e-mail.';
}
if ($produkt === '') {
    $errors[] = 'Opisz produkt do pakowania.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => implode(' ', $errors)));
    exit;
}

$subject = 'Zapytanie z wykładarka.pl – ' . $firma;

$body  = "Nowe zapytanie ze strony wykładarka.pl\r\n\r\n";
$body .= "Imię i nazwisko: {$imie}\r\n";
$body .= "Firma: {$firma}\r\n";
$body .= "E-mail: {$email}\r\n";
$body .= "Telefon: " . ($telefon !== '' ? $telefon : '—') . "\r\n\r\n";
$body .= "Opis produktu:\r\n{$produkt}\r\n\r\n";
$body .= "---\r\n";
$body .= 'Data: ' . date('Y-m-d H:i:s') . "\r\n";
$body .= 'IP: ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'nieznane') . "\r\n";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$headers  = 'From: ' . MAIL_FROM . "\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail(MAIL_TO, $encodedSubject, $body, $headers);

if ($sent) {
    echo json_encode(array('ok' => true, 'message' => 'Dziękujemy — odezwiemy się wkrótce.'));
} else {
    http_response_code(500);
    echo json_encode(array(
        'ok'      => false,
        'message' => 'Serwer nie wysłał maila. Zadzwoń: 22 351 71 91 lub napisz na biuro@spolex.com.',
    ));
}

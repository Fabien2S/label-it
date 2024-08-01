<?php
require __DIR__ . "/vendor/autoload.php";

use GuzzleHttp\Client;
use LabelIT\LabelGenerator;
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Component\Dotenv\Dotenv;

set_time_limit(60);

// Load environment file
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . "/.env");
putenv('GDFONTPATH=' . __DIR__ . "/fonts/");

// Setup api client
$client = new Client([
    "base_uri" => $_ENV["APP_API_ENDPOINT"],
    "headers" => [
        "Accept" => "application/json",
        "Content-Type" => "application/json",
        "Authorization" => "Bearer " . $_ENV["APP_API_KEY"],
    ]
]);

// Perform asset request
$response = $client->get("hardware/bytag/" . $_GET["tag"]);
$asset = json_decode((string)$response->getBody(), true);

// Generate label
$labelGenerator = new LabelGenerator();
$labelBounds = $labelGenerator->getBounds();

$labelSpacing = 8;

// Barcode C39
$barcodeBounds = $labelBounds->bottomLeft(
    0, 0,
    $labelBounds->width, 20
);
$labelGenerator->addBarcode("C39", $asset["asset_tag"], $barcodeBounds);

// QR Code
$qrCodeSize = $labelBounds->height - $labelSpacing - $barcodeBounds->height;
$qrCodeBounds = $labelBounds->topLeft(
    0, 0,
    $qrCodeSize, $qrCodeSize
);
$labelGenerator->addBarcode("QRCODE,AN", $_ENV["APP_LOOKUP_ENDPOINT"] . $asset["asset_tag"], $qrCodeBounds);

// Text
$textBounds = $labelBounds->topRight(
    0, 0,
    $labelBounds->width - $qrCodeBounds->width - $labelSpacing,
    $labelBounds->height - $barcodeBounds->height - $labelSpacing
)->splitEvenly(1.0, 5.0);

$titleBounds = $textBounds->splitEvenly(2, 1);
$labelGenerator->addText("# " . $asset["asset_tag"], $titleBounds, bold: true, centerVertical: true);
$labelGenerator->addText($asset["purchase_date"]["formatted"], $titleBounds->shift(1, 0), centerVertical: true);

$textBounds = $textBounds->shift(0, 1);
$labelGenerator->addText($asset["manufacturer"]["name"], $textBounds, centerVertical: true);

$textBounds = $textBounds->shift(0, 1);
$labelGenerator->addText($asset["model"]["name"], $textBounds, centerVertical: true);

$textBounds = $textBounds->shift(0, 1);
$labelGenerator->addText("P/N " . $asset["model_number"], $textBounds, centerVertical: true);

$textBounds = $textBounds->shift(0, 1);
$labelGenerator->addText("S/N " . $asset["serial"], $textBounds, centerVertical: true);

// Print label
$connector = new FilePrintConnector($_ENV["APP_PRINTER"]);
$printer = new Printer($connector);
$printer->initialize();
try {
    $graphic = new GdEscposImage();
    $labelImage = $labelGenerator->getImage();
    $graphic->readImageFromGdResource($labelImage);

    $printer->bitImage($graphic);
    $printer->cut();
} catch (Exception $e) {
    $printer->text("Failed to print image");
    $printer->feed();
} finally {
    $printer->close();
}
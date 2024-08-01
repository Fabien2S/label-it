<?php
require __DIR__ . "/vendor/autoload.php";

use Com\Tecnick\Barcode\Barcode;
use GuzzleHttp\Client;
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Component\Dotenv\Dotenv;

// Load environment file
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . "/.env");

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

// Create image
putenv('GDFONTPATH=' . realpath('.'));
$labelScale = 0.541584695394;
$labelFont1 = "LiberationMono-Regular.ttf";
$labelFont2 = "LiberationMono-Bold.ttf";
$labelSize1 = 20 * $labelScale;
$labelSize2 = 26 * $labelScale;

$labelWidth = 673 * $labelScale;
$labelHeight = 300 * $labelScale;
$labelPadding = 0 * $labelScale;
$labelSpacing = 20 * $labelScale;
$label = imagecreate($labelWidth, $labelHeight);
$labelBackground = imagecolorallocate($label, 255, 255, 255);
$labelColor = imagecolorallocate($label, 0, 0, 0);

$barcode = new Barcode();

// Barcode C39
$labelBarcodeWidth = $labelWidth - 2 * $labelPadding;
$labelBarcodeHeight = 40 * $labelScale;
try {
    $labelBarcode = $barcode->getBarcodeObj(
        "C39", $asset["asset_tag"],
        $labelBarcodeWidth,
        $labelBarcodeHeight
    )->getGd();
    imagecopy(
        $label, $labelBarcode,
        $labelPadding,
        $labelHeight - $labelBarcodeHeight - $labelPadding,
        0, 0, $labelBarcodeWidth, $labelBarcodeHeight
    );
    imagedestroy($labelBarcode);
} catch (\Com\Tecnick\Barcode\Exception $e) {
    imagestring($label, 1, $labelPadding, $labelHeight - $labelBarcodeHeight - $labelPadding, "Barcode error", $labelColor);
}

// QR Code
$labelQRCodeSize = $labelHeight - 2 * $labelPadding - $labelSpacing - $labelBarcodeHeight;
try {
    $labelQRCode = $barcode->getBarcodeObj(
        "QRCODE,H", $_ENV["APP_LOOKUP_ENDPOINT"] . $asset["asset_tag"],
        $labelQRCodeSize, $labelQRCodeSize,
        'black',                        // foreground color
        array(0, 0, 0, 0)           // padding (use absolute or negative values as multiplication factors)
    )->getGd();
    imagecopy($label, $labelQRCode, $labelPadding, $labelPadding, 0, 0, $labelQRCodeSize, $labelQRCodeSize);
    imagedestroy($labelQRCode);
} catch (\Com\Tecnick\Barcode\Exception $e) {
    imagestring($label, 1, $labelPadding, $labelPadding, "QRCode error", $labelColor);
}

// Texts
$labelPosition = $labelQRCodeSize + $labelPadding + $labelSpacing;

$titleText = "#" . $asset["asset_tag"];
$titleBounds = imagettfbbox($labelSize2, 0, $labelFont2, $titleText);
imagettftext($label, $labelSize2, 0, $labelPosition, $labelPadding - $titleBounds[7], $labelColor, $labelFont2, "# " . $asset["asset_tag"]);
imagettftext($label, $labelSize2, 0, $labelPosition + $titleBounds[4], $labelPadding - $titleBounds[7], $labelColor, $labelFont1, " - " . $asset["purchase_date"]["formatted"]);

$textHeight = 30 * $labelScale;
imagettftext($label, $labelSize1, 0, $labelPosition, $labelPadding - $titleBounds[7] + 1 * $textHeight, $labelColor, $labelFont1, $asset["manufacturer"]["name"]);
imagettftext($label, $labelSize1, 0, $labelPosition, $labelPadding - $titleBounds[7] + 2 * $textHeight, $labelColor, $labelFont1, $asset["model"]["name"]);
imagettftext($label, $labelSize1, 0, $labelPosition, $labelPadding - $titleBounds[7] + 3 * $textHeight, $labelColor, $labelFont1, "S/N " . $asset["serial"]);
imagettftext($label, $labelSize1, 0, $labelPosition, $labelPadding - $titleBounds[7] + 4 * $textHeight, $labelColor, $labelFont1, "P/N " . $asset["model_number"]);

imagepng($label, "output.png");
imagedestroy($label);

$connector = new FilePrintConnector($_ENV["APP_PRINTER"]);
$printer = new Printer($connector);
$printer->initialize();


//
// $pages = ImagickEscposImage::loadPdf("00001.pdf", (203 * 2.52) * (57 / 80));
// foreach ($pages as $page) {
//     $printer -> bitImage($page);
// }

// $printer->setPrintWidth((203 * 2.52) * (57 / 80)); // (DPI * MaxWidthInch) * (57cm / 80cm)
//
// $printer->setJustification(Printer::JUSTIFY_CENTER);
// $printer->setEmphasis(true);
// $printer->text("# " . $asset["asset_tag"] . " | " . $asset["purchase_date"]["formatted"]);
// $printer->setEmphasis(false);
// $printer->feed();
//
// $printer->initialize();
//
// $printer->text($asset["manufacturer"]["name"]);
// $printer->feed();
// $printer->text($asset["model"]["name"]);
// $printer->feed();
// $printer->text($asset["name"]);
// $printer->feed();
// $printer->text("S/N " . $asset["serial"]);
// $printer->feed();
// $printer->text("P/N " . $asset["model_number"]);
// $printer->feed();
//
// $printer->setBarcodeHeight(8);
// $printer->setBarcodeWidth(3);
// $printer->barcode($asset["asset_tag"]);


// $printer->qrCode("https://inventory.256843.xyz/hardware/" . $asset["asset_tag"]);
// $printer->setPrintLeftMargin(100);
// $printer->setPrintWidth(456);
// $printer->feedReverse(3);
// $printer->text("# " . $asset["asset_tag"] . " | " . $asset["purchase_date"]["formatted"]);
// $printer->feed();

$graphic = new GdEscposImage();
$graphic->readImageFromGdResource($label);
$printer->bitImage($graphic);

$printer->cut();
$printer->close();

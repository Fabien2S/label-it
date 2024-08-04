<?php
require __DIR__ . "/vendor/autoload.php";

use GuzzleHttp\Client;
use LabelIT\Drawing\ImageDrawer;
use LabelIT\Items\Item;
use LabelIT\Labels\StandardLabelGenerator;
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
$item = new Item(
    $asset["asset_tag"],
    $asset["model"]["name"],
    $asset["model_number"],
    $asset["manufacturer"]["name"],
    $asset["serial"],
    DateTimeImmutable::createFromFormat("Y-m-d", $asset["purchase_date"]["date"]),
);

// Generate label
$imageDrawer = new ImageDrawer();

$labelGenerator = new StandardLabelGenerator();
$labelGenerator->generate($imageDrawer, $item);

// Print label
$connector = new FilePrintConnector($_ENV["APP_PRINTER"]);
$printer = new Printer($connector);
$printer->initialize();
try {
    $graphic = new GdEscposImage();
    $labelImage = $imageDrawer->getImage();
    $graphic->readImageFromGdResource($labelImage);
    $printer->bitImage($graphic);
    $printer->cut();
} catch (Exception $e) {
    $printer->text("Failed to print image");
    $printer->feed();
} finally {
    $printer->close();
}
<?php

require __DIR__ . '/vendor/autoload.php'; // Ensure the correct path to autoload.php

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

try {
    // Replace "YOUR_PRINTER_NAME" with the exact name of your thermal printer
    $connector = new WindowsPrintConnector("ZJ-58");

    // Initialize the printer
    $printer = new Printer($connector);

    // Print a simple receipt
    $printer->text("Hay\n");
    $printer->text("This is a test print.\n");
    $printer->text("Printing via ESC/POS.\n");
    $printer->cut(); // Cut the paper

    // Close the printer connection
    $printer->close();

    echo "Printed successfully!";
} catch (Exception $e) {
    echo "Could not print: " . $e->getMessage();
}



<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use thiagoalessio\TesseractOCR\TesseractOCR;

class InvoiceScanner
{
    public function scanInvoice(UploadedFile $file)
    {
        $image = $this->convertToImage($file);
        $text = (new TesseractOCR($image))->run();
        return $this->extractInvoiceData($text);
    }

    protected function extractInvoiceData(string $text): array
    {
        return [
            'invoice_number' => $this->extractInvoiceNumber($text),
            'amount' => $this->extractAmount($text),
            'date' => $this->extractDate($text),
            'vendor_details' => $this->extractVendorDetails($text)
        ];
    }

    protected function extractInvoiceNumber(string $text): ?string
    {
        preg_match('/invoice.?no.?\s*[:# ]+([\w-]+)/i', $text, $matches);
        return $matches[1] ?? null;
    }

    protected function extractAmount(string $text): ?float
    {
        preg_match('/total.?\s*[:$]?\s*([\d,.]+)/i', $text, $matches);
        if (isset($matches[1])) {
            return (float) str_replace(['$', ','], '', $matches[1]);
        }
        return null;
    }

    protected function extractDate(string $text): ?string
    {
        preg_match('/date.?\s*[:# ]+([\d\/\-\.]+)/i', $text, $matches);
        return $matches[1] ?? null;
    }

    protected function extractVendorDetails(string $text): array
    {
        return [
            'name' => $this->extractVendorName($text),
            'tax_id' => $this->extractTaxId($text),
        ];
    }

    protected function extractVendorName(string $text): ?string
    {
        preg_match('/from:?\s*([^\n]+)/i', $text, $matches);
        return $matches[1] ?? null;
    }

    protected function extractTaxId(string $text): ?string
    {
        preg_match('/tax.?id.?\s*[:# ]+([\w\-]+)/i', $text, $matches);
        return $matches[1] ?? null;
    }

    protected function convertToImage(UploadedFile $file): string
    {
        // Implementation depends on whether file is PDF or image
        // For now, assuming it's an image
        return $file->getPathname();
    }
}
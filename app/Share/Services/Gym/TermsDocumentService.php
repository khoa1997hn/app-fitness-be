<?php

namespace App\Share\Services\Gym;

use App\Share\Models\Order;
use RuntimeException;
use setasign\Fpdi\Tfpdf\Fpdi;

class TermsDocumentService
{
    public function path(): string
    {
        return config('gym.terms_document.path');
    }

    /**
     * @return array{page: int, x: float, y: float, width: float, height: float}
     */
    public function signaturePlacement(): array
    {
        return config('gym.terms_document.signature');
    }

    public function isValidSignature(string $dataUrl): bool
    {
        return $this->decodeSignature($dataUrl) !== null;
    }

    /**
     * Chèn chữ ký, họ tên và ngày ký của đơn vào file điều khoản.
     * Ảnh chữ ký giữ nguyên tỉ lệ và căn giữa trong ô chữ ký.
     */
    public function signedCopy(Order $order): string
    {
        $signature = $this->decodeSignature((string) $order->terms_signature);

        if ($signature === null) {
            throw new RuntimeException("Chữ ký của đơn {$order->code} không phải ảnh PNG hợp lệ.");
        }

        $size = getimagesizefromstring($signature);
        $placement = $this->signaturePlacement();
        $imagePath = tempnam(sys_get_temp_dir(), 'kore-signature-');
        file_put_contents($imagePath, $signature);

        try {
            $pdf = new Fpdi;
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($this->path());

            if ($placement['page'] > $pageCount) {
                throw new RuntimeException("File điều khoản chỉ có {$pageCount} trang, không có trang chữ ký {$placement['page']}.");
            }

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $pageSize = $pdf->getTemplateSize($template);
                $pdf->AddPage($pageSize['orientation'], [$pageSize['width'], $pageSize['height']]);
                $pdf->useTemplate($template);

                if ($page === $placement['page']) {
                    $this->placeSignature($pdf, $imagePath, $size, $placement, $pageSize['width'], $pageSize['height']);
                    $this->placeSigner($pdf, $order, $pageSize['width'], $pageSize['height']);
                }
            }

            return $pdf->Output('S');
        } finally {
            @unlink($imagePath);
        }
    }

    /**
     * Font DejaVu của tFPDF hỗ trợ tiếng Việt, font có sẵn của FPDF thì không.
     */
    private function placeSigner(Fpdi $pdf, Order $order, float $pageWidth, float $pageHeight): void
    {
        $lines = array_filter([
            $order->buyer_name ? __('messages.gym_terms_signer_name', ['name' => $order->buyer_name]) : null,
            $order->terms_accepted_at ? __('messages.gym_terms_signed_at', ['date' => $order->terms_accepted_at->format('d/m/Y')]) : null,
        ]);

        if ($lines === []) {
            return;
        }

        $signer = config('gym.terms_document.signer');
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($signer['x'] * $pageWidth, $signer['y'] * $pageHeight);
        $pdf->MultiCell($signer['width'] * $pageWidth, 5, implode("\n", $lines), 0, 'C');
    }

    private function decodeSignature(string $dataUrl): ?string
    {
        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($dataUrl, $prefix)) {
            return null;
        }

        $image = base64_decode(substr($dataUrl, strlen($prefix)), true);
        $size = $image === false ? false : @getimagesizefromstring($image);

        return $size !== false && $size[2] === IMAGETYPE_PNG ? $image : null;
    }

    /**
     * @param  array<int|string, mixed>  $imageSize
     * @param  array{page: int, x: float, y: float, width: float, height: float}  $placement
     */
    private function placeSignature(Fpdi $pdf, string $imagePath, array $imageSize, array $placement, float $pageWidth, float $pageHeight): void
    {
        $boxWidth = $placement['width'] * $pageWidth;
        $boxHeight = $placement['height'] * $pageHeight;
        $scale = min($boxWidth / $imageSize[0], $boxHeight / $imageSize[1]);
        $width = $imageSize[0] * $scale;
        $height = $imageSize[1] * $scale;

        $pdf->Image(
            $imagePath,
            $placement['x'] * $pageWidth + ($boxWidth - $width) / 2,
            $placement['y'] * $pageHeight + ($boxHeight - $height) / 2,
            $width,
            $height,
            'PNG',
        );
    }
}

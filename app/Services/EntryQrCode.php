<?php

namespace App\Services;

use App\Models\Guest;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class EntryQrCode
{
    public function svg(Guest $guest): string
    {
        return (new SvgWriter)->write($this->code($guest))->getString();
    }

    public function dataUri(Guest $guest): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($guest));
    }

    private function code(Guest $guest): QrCode
    {
        return new QrCode(
            data: $guest->entryUrl(),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 24,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(42, 31, 23),
            backgroundColor: new Color(255, 250, 242),
        );
    }
}

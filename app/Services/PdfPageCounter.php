<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfPageCounter
{
    public function count(string $absolutePdfPath): int
    {
        // 1) Fast path: FPDI
        try {
            $fpdi = new Fpdi();
            return (int) $fpdi->setSourceFile($absolutePdfPath);
        } catch (\Throwable $e) {
            // fallback below
        }

        // 2) Ghostscript fallback (most compatible)
        $gsExe = $this->findGhostscriptExecutable();
        if (!$gsExe) {
            return 0;
        }

        // Use a small PostScript snippet to get pdfpagecount
        $psPath = $this->toPostScriptFileString($absolutePdfPath);
        $snippet = "({$psPath}) (r) file runpdfbegin pdfpagecount = quit";

        try {
            $result = Process::run([
                $gsExe,
                '-q',
                '-dNODISPLAY',
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-c',
                $snippet,
            ]);
        } catch (\Throwable $e) {
            return 0;
        }

        if (!$result->successful()) {
            return 0;
        }

        $out = trim($result->output());
        if (!preg_match('/(\d+)/', $out, $m)) {
            return 0;
        }

        return (int) $m[1];
    }

    private function findGhostscriptExecutable(): ?string
    {
        foreach (['gswin64c', 'gswin32c', 'gs'] as $exe) {
            try {
                $result = Process::run([$exe, '-v']);
                if ($result->successful()) {
                    return $exe;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }

    private function toPostScriptFileString(string $path): string
    {
        // Ghostscript PostScript string inside () prefers forward slashes.
        $path = str_replace('\\', '/', $path);

        // Escape PostScript string special chars
        $path = str_replace('\\', '\\\\', $path);
        $path = str_replace('(', '\\(', $path);
        $path = str_replace(')', '\\)', $path);

        return $path;
    }
}

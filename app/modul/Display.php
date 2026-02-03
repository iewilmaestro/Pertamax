<?php

class Display {

    public static bool $noColor   = false;

    /** KONFIGURASI WARNA & SIMBOL **/
    private static array $colorScheme = [
        "success" => [[0,255,0], [0,128,0]],
        "warning" => [[255,0,0], [128,0,0]],
        "debug"   => [[255,255,0], [128,128,0]],
        "info"    => [[0,0,255], [0,0,128]],
        "default" => [[0,128,255], [0,255,255]]
    ];

    private static array $symbols = [
        "success" => "✓",
        "warning" => "!",
        "debug"   => "?",
        "info"    => "i",
        "default" => "›"
    ];


    /** GRADIENT STRING */
    private static function gradient(string $text, array $startColor, array $endColor): string {
        if (self::$noColor) {
            return $text; // langsung teks tanpa warna
        }
        $len = mb_strlen($text, 'UTF-8');
        if ($len === 0) return "";

        $result = "";
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');

            $ratio = $i / max($len-1,1);
            $r = intval($startColor[0] + ($endColor[0] - $startColor[0]) * $ratio);
            $g = intval($startColor[1] + ($endColor[1] - $startColor[1]) * $ratio);
            $b = intval($startColor[2] + ($endColor[2] - $startColor[2]) * $ratio);

            $result .= "\033[38;2;{$r};{$g};{$b}m{$char}\033[0m";
        }
        return $result;
    }

    /** SIMBOL WARNA */
    private static function symbolColor(string $type): string {
        
        $color = self::$colorScheme[$type][0] ?? [200,200,200];
        $symbol = self::$symbols[$type] ?? self::$symbols["default"];
        if (self::$noColor) {
            return $symbol;
        }
        return "\033[38;2;{$color[0]};{$color[1]};{$color[2]}m{$symbol}\033[0m";
    }

    /** CETAK FORMAT RATA */
    static function rata(string $type, string $message): string {
        $len = 8;
        $spaces = $len - mb_strlen($type, 'UTF-8');

        $scheme = self::$colorScheme[$type] ?? self::$colorScheme["default"];
        [$start, $end] = $scheme;

        $symbol = self::symbolColor($type);

        $label = self::gradient($type, $start, $end);
        $value = self::gradient($message, $start, $end);

        return "{$symbol} {$label}" . str_repeat(" ", $spaces) . ":: {$value}";
    }

    /** MENU PILIHAN */
    static function Menu($no, $title, $last = null): void {
        $s = [180, 0, 255];
        $e = [255, 0, 150];

        $left  = self::gradient("-[$no]", $s, $e);
        $title = self::gradient($title, $s, $e);

        if ($last !== null)
            print "$left $title\t" . self::gradient($last, $s, $e) . "\n";
        else
            print "$left $title\n";
    }

    public static function MultiMenu(array $arr, int $split = 2): void {
        $count = count($arr);
        $rows = ceil($count / $split);

        $matrix = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $split; $j++) {
                $index = $i + $j * $rows;
                $matrix[$i][$j] = $arr[$index] ?? '';
            }
        }

        $colWidths = [];
        for ($j = 0; $j < $split; $j++) {
            $max = 0;
            foreach ($matrix as $i => $row) {
                $text = $row[$j] ?? '';
                $no = "-[" . ($i + $j * $rows + 1) . "]";
                $len = strlen($no . ' ' . $text);
                if ($len > $max) $max = $len;
            }
            $colWidths[$j] = $max;
        }

        for ($i = 0; $i < $rows; $i++) {
            $line = '';
            for ($j = 0; $j < $split; $j++) {
                $index = $i + $j * $rows;
                if (!isset($matrix[$i][$j]) || $matrix[$i][$j] === '') continue;

                $no = "-[" . ($index + 1) . "]";
                $text = "{$no} {$matrix[$i][$j]}";

                $text = str_pad($text, $colWidths[$j] + 2); // spasi antar kolom
                $line .= self::gradient($text, [255,140,0], [255,165,0]);
            }
            print $line . "\n";
        }
    }
    static function str_pad_utf8($text, $pad_length, $pad_string = " ", $pad_type = STR_PAD_BOTH) {
        $text_width = mb_strwidth($text, 'UTF-8'); // panjang visual
        if ($pad_length <= $text_width) return $text;

        $total_pad = $pad_length - $text_width;

        switch ($pad_type) {
            case STR_PAD_LEFT:
                $left_pad = str_repeat($pad_string, $total_pad);
                return $left_pad . $text;
            case STR_PAD_RIGHT:
                $right_pad = str_repeat($pad_string, $total_pad);
                return $text . $right_pad;
            case STR_PAD_BOTH:
                $left_pad = str_repeat($pad_string, floor($total_pad / 2));
                $right_pad = str_repeat($pad_string, ceil($total_pad / 2));
                return $left_pad . $text . $right_pad;
        }
    }

    static Function banner(){
        (PHP_OS == "Linux") ? system('clear') : pclose(popen('cls','w'));
        if (defined('host')) {
            $title = parse_url(host, PHP_URL_HOST);
            echo self::gradient("ᯓ★ $title", [0,255,0], [0,128,0]) . PHP_EOL;
        }
        
        $lines = [
            '⎝ 𓆩༺✧༻𓆪 ⎠',
            '⎝╔═╗╔═╗╦═╗╔╦╗╔═╗╔╦╗╔═╗═╗ ╦⎠',
            '꧁╠═╝║╣ ╠╦╝ ║ ╠═╣║║║╠═╣╔╩╦╝꧂',
            '╩  ╚═╝╩╚═ ╩ ╩ ╩╩ ╩╩ ╩╩ ╚═',
            '𝄚𝄚 © iewil '.date('Y').' 𝄚𝄚'
        ];

        foreach ($lines as $line) {
            $padded = self::str_pad_utf8($line, 45);
            echo self::gradient($padded, [255,140,0], [255,165,0]) . PHP_EOL;
        }
        echo self::gradient(self::str_pad_utf8('FREE SCRIPT NOT FOR SALE',45),[255,80,80],[255,0,0]) . PHP_EOL;
        self::line();
        if (defined('reff')) {
            echo self::gradient('register: ',[0,255,0], [0,128,0]) . reff . PHP_EOL;
            self::line();
        }
    }
    /** CLEAR LINE */
    static function clearLine(): void {
        print "\r\033[2K";
    }

    static function cetak(string $key, string $val): void {
        print self::rata($key, $val) . "\n";
    }

    /** TITLE BLOCK GRADIENT */
    static function Title(string $text): void {
        $len = 45;
        $padded = str_pad(strtoupper($text), $len, " ", STR_PAD_BOTH);
        if (self::$noColor) {
            print $padded;
            return;
        }
        $start = [0, 180, 0];
        $end   = [255, 255, 0];

        $out = "";
        $max = strlen($padded) - 1;

        for ($i = 0; $i < strlen($padded); $i++) {
            $ratio = $i / max($max, 1);

            $r = intval($start[0] + ($end[0] - $start[0]) * $ratio);
            $g = intval($start[1] + ($end[1] - $start[1]) * $ratio);
            $b = intval($start[2] + ($end[2] - $start[2]) * $ratio);

            $out .= "\033[48;2;{$r};{$g};{$b}m\033[38;2;0;0;0m{$padded[$i]}";
        }

        print $out . "\033[0m\n";
    }

    /** GARIS */
    static function line(int $len = 45): void {
        if (self::$noColor) {
            print str_repeat("─", $len) . "\n";
            return;
        }
        print "\033[0m" . str_repeat("─", $len) . "\n";
    }

    /** TABEL KEY => VALUE */
    static function table(array $data): void {
        foreach ($data as $k => $v) {
            print self::rata($k, $v) . "\n";
        }
    }

    /** SHORTCUT DEBUG */
    static function debug(string $msg): void {
        print self::rata("debug", $msg) . "\n";
    }

    static function info(string $msg): void {
        print self::rata("info", $msg) . "\n";
    }

    static function Error($msg = ""): void {
        print self::rata("warning", $msg);
    }

    static function sukses(string $msg, bool $newline = true): void {
        $out = self::rata("success", $msg);
        print $newline ? "$out\n" : $out;
    }

    /** INPUT BOX */
    static function Isi(string $msg) {
        if (self::$noColor) {
            print "╭[Input $msg]\n";
            print "╰>";
            return;
        }
        $s = [180, 0, 255];
        $e = [255, 0, 150];

        $text = self::gradient("[Input $msg]", $s, $e);

        print "\033[38;2;{$s[0]};{$s[1]};{$s[2]}m╭\033[0m$text\n";
        print "\033[38;2;{$s[0]};{$s[1]};{$s[2]}m╰\033[0m";
        print "\033[38;2;{$e[0]};{$e[1]};{$e[2]}m> \033[0m";
    }
    static function Tmr(int $seconds) {

        $spinner = ['⠋','⠙','⠹','⠸','⠼','⠴','⠦','⠧','⠇','⠏'];
        $end = microtime(true) + $seconds;
        $i = 0;

        while (($remaining = $end - microtime(true)) > 0) {

            $remaining = (int)$remaining;

            $h = intdiv($remaining, 3600);
            $m = intdiv($remaining % 3600, 60);
            $s = $remaining % 60;

            $timeStr = sprintf("%02d:%02d:%02d", $h, $m, $s);

            // ======================
            // NO COLOR MODE
            // ======================
            if (self::$noColor) {

                $sp = $spinner[$i % count($spinner)];
                printf("\r%s %s", $sp, $timeStr);

                usleep(50000);
                $i++;
                continue;
            }

            // ======================
            // COLOR MODE
            // ======================

            $rainbow = "";
            $len = strlen($timeStr);

            for ($j = 0; $j < $len; $j++) {

                $rev = $len - 1 - $j;

                $r = (50  + ($i*10 + $rev*15)) % 256;
                $g = (100 + ($i*5  + $rev*20)) % 256;
                $b = (150 + ($i*15 + $rev*5 )) % 256;

                $rainbow .= sprintf(
                    "\033[38;2;%d;%d;%dm%s\033[0m",
                    $r, $g, $b, $timeStr[$j]
                );
            }

            $r = ($i*20) % 256;
            $g = ($i*40) % 256;
            $b = ($i*60) % 256;

            $sp = sprintf(
                "\033[38;2;%d;%d;%dm%s\033[0m",
                $r, $g, $b, $spinner[$i % count($spinner)]
            );

            printf("\r%s %s", $sp, $rainbow);

            $i++;
            usleep(50000);
        }

        echo "\r                      \r";
    }
}

?>
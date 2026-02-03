<?php

class App {

    private static array $argv = [];
    private static array $flags = [];

    /* ========================= */
    /* BOOT */
    /* ========================= */

    public static function boot(): void {
        self::$argv = $_SERVER['argv'] ?? [];
        self::parseFlags();
    }

    /* ========================= */
    /* FLAG PARSER */
    /* ========================= */

    private static function parseFlags(): void {
        foreach (self::$argv as $arg) {
            if (str_starts_with($arg, '-')) {
                self::$flags[] = $arg;
            }
        }
    }

    public static function hasFlag(...$names): bool {
        return count(array_intersect(self::$flags, $names)) > 0;
    }

    public static function flags(): array {
        return self::$flags;
    }

    /* ========================= */
    /* DISPLAY CONFIG */
    /* ========================= */

    public static function display(): void {

        $noColor = self::hasFlag('-nocolor','-nc');

        if (!self::isCliTty()) {
            $noColor = true;
        }

        if (class_exists('Display')) {
            Display::$noColor = $noColor;
        }
    }

    private static function isCliTty(): bool {
        return function_exists('stream_isatty')
            ? stream_isatty(STDOUT)
            : (function_exists('posix_isatty')
                ? posix_isatty(STDOUT)
                : true);
    }

    /* ========================= */
    /* ERROR HANDLER */
    /* ========================= */

    public static function error(): void {

        if (self::hasFlag('-d','-debug')) {
            new ShowError();
        }
    }

    /* ========================= */
    /* HELP SYSTEM */
    /* ========================= */

    public static function helper(): void {

        if (!self::hasFlag('-h','-help')) {
            return;
        }

        echo "\n";
        echo "CLI Helper\n";
        echo "==========\n";
        echo "php tes.php [options]\n\n";

        echo "Flags:\n";
        echo "  -h, -help       Tampilkan bantuan\n";
        echo "  -d, -debug      Aktifkan debug & error log\n";
        echo "  -nc, -nocolor   Matikan warna output\n\n";

        exit;
    }

    public static function data() {
        if(!file_exists("data")){
            mkdir("data");
            Display::sukses("successfully created `data` folder");
            Display::line();
        }
    }

}

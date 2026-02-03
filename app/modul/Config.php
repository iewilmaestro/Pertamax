<?php

class Config {

    // ======= Folder & File =======
    public static function configFolder() {
        return "data/" . TITLE;
    }

    public static function configFile() {
        return self::configFolder() . "/data.json";
    }

    // ======= Inisialisasi =======
    private static function init() {
        if (!is_dir(self::configFolder())) {
            mkdir(self::configFolder(), 0777, true);
        }

        if (!file_exists(self::configFile())) {
            file_put_contents(self::configFile(), json_encode([]));
        }
    }

    // ======= Load / Save Raw =======
    private static function loadRaw() {
        self::init();
        $json = file_get_contents(self::configFile());
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private static function saveRaw(array $data) {
        file_put_contents(
            self::configFile(),
            json_encode(array_values($data), JSON_PRETTY_PRINT)
        );
    }

    // ======= SIMPAN MULTI (NEW CONFIG) =======
    public static function simpan(array $keys) {
        $result = [];

        foreach ($keys as $key) {
            isi:
            Display::isi($key);
            $value = trim(readline());

            if ($value === '') {
                Display::Error("value must be not null\n");
                goto isi;
            }

            $result[$key] = $value;
        }

        $all = self::loadRaw();
        $all[] = $result;

        self::saveRaw($all);

        Display::Sukses("Config baru berhasil disimpan");
        return $result;
    }

    // ======= HAPUS MULTI (SELURUH CONFIG ATAU KEY TERTENTU) =======
    public static function hapus($index, $key = null) {
        $all = self::loadRaw();

        if (!isset($all[$index])) {
            Display::Error("Index $index tidak ditemukan\n");
            return false;
        }

        if ($key !== null) {
            if (!isset($all[$index][$key])) {
                Display::Error("Key '$key' tidak ditemukan\n");
                return false;
            }

            unset($all[$index][$key]);
            self::saveRaw($all);
            Display::Sukses("Key '$key' dihapus dari config index $index");
            return true;
        }

        // hapus seluruh config
        unset($all[$index]);
        self::saveRaw($all);
        Display::Sukses("Config index $index dihapus");
        return true;
    }

    // ======= TAMBAH / UPDATE KEY DI INDEX TERENTU =======
    public static function tambahKey($index, $key) {
        $all = self::loadRaw();

        if (!isset($all[$index])) {
            Display::Error("Index $index tidak ditemukan\n");
            return false;
        }

        isi:
        Display::isi($key);
        $value = trim(readline());

        if ($value === '') {
            Display::Error("value must be not null\n");
            goto isi;
        }

        $all[$index][$key] = $value;
        self::saveRaw($all);

        Display::Sukses("Key '$key' berhasil ditambahkan/diupdate di config index $index");
        return true;
    }

    // ======= LOAD ALL CONFIG =======
    public static function load() {
        return self::loadRaw();
    }
    // ======= PICK First CONFIG =======
    public static function pick($key) {
        $r = self::loadRaw();
        foreach($r[0] as $a => $b){
            if($a == $key)return $b;
        }
    }
}
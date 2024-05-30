<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class Status extends Enum
{
    const Aktif = 1;
    const Tidak_Aktif = 2;
    const Dipinjamkan = 3;
    const Dalam_Pemeliharaan = 4;
    const Dalam_Penyimpanan = 5;
    const Dalam_Perbaikan = 6;
    const Dalam_Proses_Peminjaman = 7;
    const Tidak_Layak_Pakai = 8;
}

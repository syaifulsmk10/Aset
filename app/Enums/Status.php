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
    const Dalam_Proses_Peminjaman = 4;
    const Dalam_Proses_Pengembalian = 5;
}

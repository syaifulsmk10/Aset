<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ItemCondition extends Enum
{
    const Baik = 1;
    const Perlu_Perbaikan = 2;
    const Rusak = 3;
    const Dalam_Perbaikan = 4;
    const  Tidak_Aktif = 5;
    const Hilang = 6;
    const Tidak_Layak_Pakai = 7;
}

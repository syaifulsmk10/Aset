<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class StatusApplicant extends Enum
{
    const Belum_Disetujui = 1;
    const Disetujui = 2;
    const Ditolak = 3;
}

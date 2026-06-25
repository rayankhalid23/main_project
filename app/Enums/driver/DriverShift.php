<?php

namespace App\Enums\driver;

enum DriverShift: int
{
    case MORNING = 1;
    case EVENING = 2;
    case BOTH    = 3;

    /**
     * الحصول على المسمى العربي للفترة لعرضه في الـ API ولوحة التحكم
     */
    public function label(): string
    {
        return match($this) {
            self::MORNING => 'صباحي فقط',
            self::EVENING => 'مسائي فقط',
            self::BOTH    => 'الفترتين (صباحي + مسائي)',
        };
    }
}
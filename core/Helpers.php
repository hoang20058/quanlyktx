<?php

declare(strict_types=1);

/**
 * Return Vietnamese description text for a given priority level (1..8).
 */
function getPriorityDescription(int $level): string
{
    $map = [
        1 => 'Bản thân Sinh viên là: Anh hùng lực lượng vũ trang nhân dân, anh hùng lao động, thương binh, bệnh binh...',
        2 => 'Sinh viên là: Con liệt sĩ, con thương binh, con bệnh binh...',
        3 => 'Sinh viên là người dân tộc thiểu số.',
        4 => 'Sinh viên mồ côi cả cha và mẹ.',
        5 => 'Sinh viên là con hộ nghèo, hộ cận nghèo theo quy định.',
        6 => 'Sinh viên có hộ khẩu thường trú tại vùng cao, vùng kinh tế đặc biệt khó khăn.',
        7 => 'Sinh viên có thành tích cá nhân nổi bật.',
        8 => 'Các trường hợp còn lại.',
    ];

    return $map[$level] ?? $map[8];
}

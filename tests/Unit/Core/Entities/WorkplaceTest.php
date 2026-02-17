<?php

use App\Core\Entities\Workplace;

test('Workplace has correct properties', function () {
    $workplace = new Workplace('08:00', '16:00', '16:00');

    expect($workplace->getDefaultShiftStart())->toBe('08:00');
    expect($workplace->getDefaultShiftEnd())->toBe('16:00');
    expect($workplace->getLateStartThreshold())->toBe('16:00');
});

test('Workplace uses default late start threshold', function () {
    $workplace = new Workplace('09:00', '17:00');

    expect($workplace->getLateStartThreshold())->toBe('16:00');
});

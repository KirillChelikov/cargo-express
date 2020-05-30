<?php
declare(strict_types = 1);

namespace CargoExpress;

class DistanceCalculator
{
    // как будто к бд обращаемся за дистанцией или считаем что-то сложное
    public static function calculateDistance($city) {
        if ($city == 'Нью-Йорк') {
            return 250;
        } else if ($city == 'Москва') {
            return 400;
        }
    }

   
}
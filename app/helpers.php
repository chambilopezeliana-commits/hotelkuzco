<?php

if (! function_exists('bs')) {
    function bs($monto): string
    {
        return 'Bs '.number_format((float) $monto, 2, ',', '.');
    }
}

if (! function_exists('anticipo_minimo')) {
    function anticipo_minimo($total): float
    {
        $porcentaje = (float) config('hotel.anticipo_porcentaje', 30);

        return round((float) $total * $porcentaje / 100, 2);
    }
}

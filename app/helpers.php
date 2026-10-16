<?php

if (! function_exists('bs')) {
    function bs($monto): string
    {
        return 'Bs '.number_format((float) $monto, 2, ',', '.');
    }
}

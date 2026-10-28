<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conservation des données invités
    |--------------------------------------------------------------------------
    |
    | Nombre de jours après le début de l'événement avant que les foyers, le
    | livre d'or et les relances ne soient supprimés, et que les contributions
    | ne soient anonymisées. La commande guests:purge applique cette valeur.
    |
    */

    'guest_data_days' => (int) env('GUEST_DATA_RETENTION_DAYS', 90),

];

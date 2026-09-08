<?php
declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'heading' => 'Dashboard',
    'welcome' => 'Willkommen zurück, :name. Du siehst gerade :tenant.',
    'cards' => [
        'server-driven' => [
            'title' => 'Server-getriebene Seiten',
            'body' => 'Dieses Dashboard wird aus einer Lattice-Seitendefinition gerendert.',
        ],
        'tenant-context' => [
            'title' => 'Mandanten-Kontext',
            'body' => 'Routen, Breadcrumbs und Layout werden auf dem Server aufgelöst.',
        ],
        'composable' => [
            'title' => 'Kombinierbare Oberfläche',
            'body' => 'Seiten können Karten, Raster, Formulare, Tabellen und Aktionen kombinieren.',
        ],
        'next-steps' => [
            'title' => 'Nächste Schritte',
            'body' => 'Ersetze diese Beispiel-Kennzahlen mit echter Mandanten-Aktivität, während das Kit wächst.',
        ],
    ],
];

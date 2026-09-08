<?php
declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'heading' => 'Dashboard',
    'welcome' => 'Welcome back, :name. You are viewing :tenant.',
    'cards' => [
        'server-driven' => [
            'title' => 'Server-driven pages',
            'body' => 'This dashboard is rendered from a Lattice page definition.',
        ],
        'tenant-context' => [
            'title' => 'Tenant context',
            'body' => 'Routes, breadcrumbs, and layout are resolved on the server.',
        ],
        'composable' => [
            'title' => 'Composable UI',
            'body' => 'Pages can compose cards, grids, forms, tables, and actions.',
        ],
        'next-steps' => [
            'title' => 'Next steps',
            'body' => 'Replace these starter metrics with real tenant activity as the kit grows.',
        ],
    ],
];

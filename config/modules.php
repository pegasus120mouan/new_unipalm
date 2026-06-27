<?php

return [

    'groups' => [
        'analytics' => [
            'label' => 'Analytiques',
            'modules' => [
                'analytics' => 'Tableau de bord analytiques',
            ],
        ],
        'tickets' => [
            'label' => 'Mes Tickets',
            'modules' => [
                'tickets.index' => 'Tous les tickets',
                'tickets.today' => 'Tickets du jour',
                'tickets.pending' => 'Tickets en attente',
                'tickets.validated' => 'Tickets validés',
                'tickets.verified' => 'Tickets vérifiés',
                'tickets.paid' => 'Tickets payés',
                'tickets.modifications' => 'Modifications de tickets',
                'tickets.search' => 'Recherche avancée',
                'tickets.destroy' => 'Supprimer un ticket',
            ],
        ],
        'finance' => [
            'label' => 'Gestion financière',
            'modules' => [
                'prix-unitaires' => 'Prix unitaire',
                'bordereaux' => 'Bordereaux',
                'financements' => 'Financements',
                'prets' => 'Prêts',
                'usines.amounts' => 'Montants usines',
                'comptes-agents' => 'Comptes agents',
                'comptes-groupes' => 'Comptes groupes',
            ],
        ],
        'utilisateurs' => [
            'label' => 'Gestion des utilisateurs',
            'modules' => [
                'utilisateurs.index' => 'Liste des utilisateurs',
                'utilisateurs.roles' => 'Gestion des rôles',
            ],
        ],
        'gestion' => [
            'label' => 'Gestion',
            'modules' => [
                'groupes' => 'Groupes',
                'agents' => 'Agents',
                'usines' => 'Usines',
                'vehicules' => 'Véhicules',
            ],
        ],
        'ponts' => [
            'label' => 'Gestion des ponts',
            'modules' => [
                'ponts.index' => 'Liste des ponts',
                'ponts.location' => 'Localisation des ponts',
            ],
        ],
        'plantations' => [
            'label' => 'Gestion des plantations',
            'modules' => [
                'plantations.index' => 'Liste des plantations',
                'plantations.collecteurs' => 'Liste des collecteurs',
                'plantations.regions' => 'Liste des régions',
                'plantations.zones' => 'Liste des zones',
            ],
        ],
        'sorties' => [
            'label' => 'Gestion des sorties',
            'modules' => [
                'sorties.demandes' => 'Liste des demandes',
                'sorties.pending' => 'Demandes en attente',
                'sorties.validated' => 'Demandes validées',
                'sorties.diverses' => 'Sorties diverses',
            ],
        ],
        'caisse' => [
            'label' => 'Gestion caisse',
            'modules' => [
                'caisse.solde' => 'Solde de la caisse',
                'caisse.approvisionnement' => 'Approvisionnement',
                'caisse.banques' => 'Banques',
            ],
        ],
        'recus' => [
            'label' => 'Gestion des reçus',
            'modules' => [
                'recus.tickets' => 'Reçu paiement tickets/bordereaux',
                'recus.demandes' => 'Reçu des paiements demandes et diverses',
            ],
        ],
    ],

    'route_map' => [
        'tickets.index' => 'tickets.index',
        'tickets.today' => 'tickets.today',
        'tickets.pending' => 'tickets.pending',
        'tickets.validated' => 'tickets.validated',
        'tickets.verified' => 'tickets.verified',
        'tickets.paid' => 'tickets.paid',
        'tickets.modifications' => 'tickets.modifications',
        'tickets.search' => 'tickets.search',
        'tickets.update' => 'tickets.index',
        'tickets.validate' => 'tickets.pending',
        'tickets.validate-bulk' => 'tickets.pending',
        'tickets.store' => 'tickets.index',
        'tickets.destroy' => 'tickets.destroy',

        'prix-unitaires.index' => 'prix-unitaires',
        'prix-unitaires.store' => 'prix-unitaires',
        'bordereaux.index' => 'bordereaux',
        'bordereaux.preview' => 'bordereaux',
        'bordereaux.store' => 'bordereaux',
        'bordereaux.validate' => 'bordereaux',
        'bordereaux.destroy' => 'bordereaux',
        'bordereaux.pdf' => 'bordereaux',

        'financements.index' => 'financements',
        'financements.show' => 'financements',
        'financements.pdf' => 'financements',
        'financements.store' => 'financements',

        'prets.index' => 'prets',
        'prets.show' => 'prets',
        'prets.store' => 'prets',

        'usines.amounts' => 'usines.amounts',
        'usines.amounts.show' => 'usines.amounts',
        'usines.amounts.payments.pdf' => 'usines.amounts',
        'usines.amounts.payment' => 'usines.amounts',

        'comptes-agents.index' => 'comptes-agents',
        'comptes-agents.show' => 'comptes-agents',
        'comptes-agents.bordereaux.payment' => 'comptes-agents',
        'comptes-agents.transactions.pdf' => 'comptes-agents',

        'comptes-groupes.index' => 'comptes-groupes',
        'comptes-groupes.show' => 'comptes-groupes',
        'comptes-groupes.paiement' => 'comptes-groupes',

        'utilisateurs.index' => 'utilisateurs.index',
        'utilisateurs.store' => 'utilisateurs.index',
        'utilisateurs.show' => 'utilisateurs.index',
        'utilisateurs.update' => 'utilisateurs.index',
        'utilisateurs.password.update' => 'utilisateurs.index',
        'utilisateurs.inline-update' => 'utilisateurs.index',
        'utilisateurs.toggle-statut' => 'utilisateurs.index',
        'utilisateurs.destroy' => 'utilisateurs.index',
        'utilisateurs.photo.edit' => 'utilisateurs.index',
        'utilisateurs.photo.store' => 'utilisateurs.index',
        'utilisateurs.avatar' => 'utilisateurs.index',

        'role-permissions.index' => 'utilisateurs.roles',
        'role-permissions.edit' => 'utilisateurs.roles',
        'role-permissions.update' => 'utilisateurs.roles',

        'groupes.index' => 'groupes',
        'groupes.show' => 'groupes',

        'agents.index' => 'agents',
        'agents.store' => 'agents',
        'agents.show' => 'agents',
        'agents.update' => 'agents',
        'agents.inline-update' => 'agents',
        'agents.destroy' => 'agents',

        'usines.index' => 'usines',
        'usines.store' => 'usines',

        'vehicules.index' => 'vehicules',
        'vehicules.store' => 'vehicules',

        'ponts.index' => 'ponts.index',
        'ponts.location' => 'ponts.location',
        'ponts.store' => 'ponts.index',
        'ponts.update' => 'ponts.index',
        'ponts.destroy' => 'ponts.index',

        'plantations.index' => 'plantations.index',
        'plantations.api' => 'plantations.index',
        'plantations.collecteurs' => 'plantations.collecteurs',
        'plantations.collecteurs.api' => 'plantations.collecteurs',
        'plantations.collecteurs.photo' => 'plantations.collecteurs',
        'plantations.regions' => 'plantations.regions',
        'plantations.regions.api' => 'plantations.regions',
        'plantations.zones' => 'plantations.zones',
        'plantations.zones.api' => 'plantations.zones',
    ],

    'module_routes' => [
        'analytics' => 'tickets.index',
        'tickets.index' => 'tickets.index',
        'tickets.today' => 'tickets.today',
        'tickets.pending' => 'tickets.pending',
        'tickets.validated' => 'tickets.validated',
        'tickets.verified' => 'tickets.verified',
        'tickets.paid' => 'tickets.paid',
        'tickets.modifications' => 'tickets.modifications',
        'tickets.search' => 'tickets.search',
        'prix-unitaires' => 'prix-unitaires.index',
        'bordereaux' => 'bordereaux.index',
        'financements' => 'financements.index',
        'prets' => 'prets.index',
        'usines.amounts' => 'usines.amounts',
        'comptes-agents' => 'comptes-agents.index',
        'comptes-groupes' => 'comptes-groupes.index',
        'utilisateurs.index' => 'utilisateurs.index',
        'utilisateurs.roles' => 'role-permissions.index',
        'groupes' => 'groupes.index',
        'agents' => 'agents.index',
        'usines' => 'usines.index',
        'vehicules' => 'vehicules.index',
        'ponts.index' => 'ponts.index',
        'ponts.location' => 'ponts.location',
        'plantations.index' => 'plantations.index',
        'plantations.collecteurs' => 'plantations.collecteurs',
        'plantations.regions' => 'plantations.regions',
        'plantations.zones' => 'plantations.zones',
    ],

    'defaults' => [
        'admin' => '*',
        'directeur' => [
            'analytics', 'tickets.index', 'tickets.today', 'tickets.pending', 'tickets.validated',
            'tickets.verified', 'tickets.paid', 'tickets.modifications', 'tickets.search', 'tickets.destroy',
            'prix-unitaires', 'bordereaux', 'financements', 'prets', 'usines.amounts',
            'comptes-agents', 'comptes-groupes', 'utilisateurs.index',
            'groupes', 'agents', 'usines', 'vehicules',
        ],
        'validateur' => [
            'analytics', 'tickets.index', 'tickets.today', 'tickets.pending', 'tickets.validated',
            'tickets.verified', 'tickets.modifications', 'tickets.search',
        ],
        'operateur' => [
            'tickets.index', 'tickets.today', 'tickets.search',
        ],
        'caissiere' => [
            'analytics', 'tickets.index', 'tickets.paid', 'tickets.search',
            'prix-unitaires', 'bordereaux', 'financements', 'prets',
            'comptes-agents', 'comptes-groupes', 'usines.amounts',
        ],
    ],

];

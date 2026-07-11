<!DOCTYPE html>

<html lang="fr">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Tableau de bord') - Unipalm</title>



    <link rel="preconnect" href="https://fonts.gstatic.com">

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">



    <link rel="stylesheet" href="{{ asset('assets/vendors/iconly/bold.css') }}">



    <link rel="stylesheet" href="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/unipalm-logo.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/unipalm-sidebar.css') }}">
    @include('layout.partials.favicon')

</head>



<body>

    <div id="app">

        <div id="sidebar" class="active">

            <div class="sidebar-wrapper active">

                <div class="sidebar-header">

                    <div class="d-flex justify-content-between">

                        <div class="logo">

                            <a href="{{ route('tickets.index') }}"><img src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo" srcset=""></a>

                        </div>

                        <div class="toggler">

                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>

                        </div>

                    </div>

                </div>

                <div class="sidebar-menu">

                    <ul class="menu">

                        <li class="sidebar-title">Menu</li>



                        @auth
                        @if ($canModule('analytics'))
                        <li class="sidebar-item {{ request()->routeIs('tickets.index') ? 'active' : '' }}">

                            <a href="{{ route('tickets.index') }}" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--analytics"><i class="bi bi-graph-up"></i></span>

                                <span>Analytiques</span>

                            </a>

                        </li>
                        @endif
                        @endauth



                    <!--    <li class="sidebar-item  has-sub">

                            <a href="#" class='sidebar-link'>

                                <i class="bi bi-stack"></i>

                                <span>Components</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="component-alert.html">Alert</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-badge.html">Badge</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-breadcrumb.html">Breadcrumb</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-button.html">Button</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-card.html">Card</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-carousel.html">Carousel</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-dropdown.html">Dropdown</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-list-group.html">List Group</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-modal.html">Modal</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-navs.html">Navs</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-pagination.html">Pagination</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-progress.html">Progress</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-spinner.html">Spinner</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="component-tooltip.html">Tooltip</a>

                                </li>

                            </ul>

                        </li>-->



                        @auth
                        @if ($canModuleAny(['tickets.index', 'tickets.today', 'tickets.pending', 'tickets.validated', 'tickets.verified', 'tickets.paid', 'tickets.modifications', 'tickets.search']))
                        <li class="sidebar-item has-sub {{ request()->routeIs('tickets.*') && ! request()->routeIs('tickets.index') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--tickets"><i class="bi bi-collection-fill"></i></span>

                                <span>Mes Tickets</span>

                            </a>

                            <ul class="submenu">

                              @if ($canModule('tickets.index'))
                              <li class="submenu-item {{ request()->routeIs('tickets.index') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.index') }}">
                                        <i class="bi bi-list-ul submenu-icon--all"></i>
                                        <span>Tous les tickets</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.today'))
                                <li class="submenu-item {{ request()->routeIs('tickets.today') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.today') }}">
                                        <i class="bi bi-calendar-day submenu-icon--today"></i>
                                        <span>Tickets du Jour</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.pending'))
                                <li class="submenu-item {{ request()->routeIs('tickets.pending') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.pending') }}">
                                        <i class="bi bi-hourglass-split submenu-icon--pending"></i>
                                        <span>Tickets en attente</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.validated'))
                                <li class="submenu-item {{ request()->routeIs('tickets.validated') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.validated') }}">
                                        <i class="bi bi-check-circle submenu-icon--validated"></i>
                                        <span>Tickets Validés</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.verified'))
                                <li class="submenu-item {{ request()->routeIs('tickets.verified') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.verified') }}">
                                        <i class="bi bi-shield-check submenu-icon--verified"></i>
                                        <span>Tickets Vérifiés</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.paid'))
                                <li class="submenu-item {{ request()->routeIs('tickets.paid') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.paid') }}">
                                        <i class="bi bi-cash submenu-icon--paid"></i>
                                        <span>Tickets Payés</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.modifications'))
                                <li class="submenu-item {{ request()->routeIs('tickets.modifications') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.modifications') }}">
                                        <i class="bi bi-pencil-square submenu-icon--edit"></i>
                                        <span>Modifications de tickets</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('tickets.search'))
                                <li class="submenu-item {{ request()->routeIs('tickets.search') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.search') }}">
                                        <i class="bi bi-search submenu-icon--search"></i>
                                        <span>Recherche avancée</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth



                        @auth
                        @if ($canModuleAny(['prix-unitaires', 'bordereaux', 'financements', 'prets', 'usines.amounts', 'comptes-agents', 'comptes-groupes']))
                        <li class="sidebar-item has-sub {{ request()->routeIs(['financements.*', 'prets.*', 'prix-unitaires.*', 'bordereaux.*', 'usines.amounts*', 'comptes-agents.*', 'comptes-groupes.*']) ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--finance"><i class="bi bi-cash-stack"></i></span>

                                <span>
                                    Gestion Financière
                                    @if(($demandesFinancementEnAttenteCount ?? 0) > 0)
                                        <span class="badge bg-danger rounded-pill ms-1">{{ $demandesFinancementEnAttenteCount }}</span>
                                    @endif
                                </span>
                            </a>

                            <ul class="submenu {{ request()->routeIs(['financements.*', 'prets.*', 'prix-unitaires.*', 'bordereaux.*', 'usines.amounts*', 'comptes-agents.*', 'comptes-groupes.*']) ? 'active' : '' }}">

                                @if ($canModule('prix-unitaires'))
                                <li class="submenu-item {{ request()->routeIs('prix-unitaires.*') ? 'active' : '' }}">

                                    <a href="{{ route('prix-unitaires.index') }}">
                                        <i class="bi bi-tag submenu-icon--price"></i>
                                        <span>Prix Unitaire</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('bordereaux'))
                                <li class="submenu-item {{ request()->routeIs('bordereaux.*') ? 'active' : '' }}">

                                    <a href="{{ route('bordereaux.index') }}">
                                        <i class="bi bi-file-earmark-text submenu-icon--bordereau"></i>
                                        <span>Bordereaux</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('financements'))
                                <li class="submenu-item {{ request()->routeIs('financements.*') ? 'active' : '' }}">

                                    <a href="{{ route('financements.index') }}">
                                        <i class="bi bi-cash-stack submenu-icon--financement"></i>
                                        <span>
                                            Financements
                                            @if(($financementsEnAttenteValidationCount ?? 0) > 0)
                                                <span class="badge bg-danger rounded-pill ms-1">{{ $financementsEnAttenteValidationCount }}</span>
                                            @endif
                                        </span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('prets'))
                                <li class="submenu-item {{ request()->routeIs('prets.*') ? 'active' : '' }}">

                                    <a href="{{ route('prets.index') }}">
                                        <i class="bi bi-wallet2 submenu-icon--pret"></i>
                                        <span>Prêts</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('usines.amounts'))
                                <li class="submenu-item {{ request()->routeIs('usines.amounts*') ? 'active' : '' }}">

                                    <a href="{{ route('usines.amounts') }}">
                                        <i class="bi bi-building submenu-icon--usine"></i>
                                        <span>Montants usines</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('comptes-agents'))
                                <li class="submenu-item {{ request()->routeIs('comptes-agents.*') ? 'active' : '' }}">

                                    <a href="{{ route('comptes-agents.index') }}">
                                        <i class="bi bi-person-lines-fill submenu-icon--agent"></i>
                                        <span>Comptes agents</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('comptes-groupes'))
                                <li class="submenu-item {{ request()->routeIs('comptes-groupes.*') ? 'active' : '' }}">

                                    <a href="{{ route('comptes-groupes.index') }}">
                                        <i class="bi bi-people-fill submenu-icon--groupe"></i>
                                        <span>Comptes groupes</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth


                        @auth
                        @if ($canModuleAny(['utilisateurs.index', 'utilisateurs.roles']))
                        <li class="sidebar-item has-sub {{ request()->routeIs(['utilisateurs.*', 'role-permissions.*']) ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--users"><i class="bi bi-shield-lock"></i></span>

                                <span>Gestion des utilisateurs</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs(['utilisateurs.*', 'role-permissions.*']) ? 'active' : '' }}">

                                @if ($canModule('utilisateurs.index'))
                                <li class="submenu-item {{ request()->routeIs('utilisateurs.index') ? 'active' : '' }}">

                                    <a href="{{ route('utilisateurs.index') }}">
                                        <i class="bi bi-people submenu-icon--groupes"></i>
                                        <span>Liste des utilisateurs</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('utilisateurs.roles'))
                                <li class="submenu-item {{ request()->routeIs('role-permissions.*') ? 'active' : '' }}">

                                    <a href="{{ route('role-permissions.index') }}">
                                        <i class="bi bi-shield-check submenu-icon--roles"></i>
                                        <span>Gestion des rôles</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth

                        @auth
                        @if ($canModuleAny(['ponts.index', 'ponts.types', 'ponts.regions', 'ponts.location']))
                        <li class="sidebar-item has-sub {{ request()->routeIs('ponts.*') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--bridges"><i class="bi bi-signpost-split-fill"></i></span>

                                <span>Gestion des ponts</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs('ponts.*') ? 'active' : '' }}">

                                @if ($canModule('ponts.index'))
                                <li class="submenu-item {{ request()->routeIs('ponts.index') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.index') }}">
                                        <i class="bi bi-list-ul submenu-icon--all"></i>
                                        <span>Liste des ponts</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('ponts.types'))
                                <li class="submenu-item {{ request()->routeIs('ponts.types.*') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.types.index') }}">
                                        <i class="bi bi-type submenu-icon--type"></i>
                                        <span>Types de ponts</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('ponts.regions'))
                                <li class="submenu-item {{ request()->routeIs('ponts.regions.*') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.regions.index') }}">
                                        <i class="bi bi-map submenu-icon--regions"></i>
                                        <span>Gestion des régions</span>
                                    </a>

                                </li>
                                <li class="submenu-item {{ request()->routeIs('ponts.departements.*') && ! request()->routeIs('ponts.departements.sous-prefectures') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.departements.index') }}">
                                        <i class="bi bi-layers submenu-icon--regions"></i>
                                        <span>Départements</span>
                                    </a>

                                </li>
                                <li class="submenu-item {{ request()->routeIs('ponts.sous-prefectures.*') || request()->routeIs('ponts.departements.sous-prefectures') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.sous-prefectures.index') }}">
                                        <i class="bi bi-grid submenu-icon--regions"></i>
                                        <span>Sous-préfectures</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('ponts.villages.*') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.villages.index') }}">
                                        <i class="bi bi-house submenu-icon--regions"></i>
                                        <span>Villages</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('ponts.location'))
                                <li class="submenu-item {{ request()->routeIs('ponts.location') ? 'active' : '' }}">

                                    <a href="{{ route('ponts.location') }}">
                                        <i class="bi bi-geo-alt submenu-icon--location"></i>
                                        <span>Localisation des ponts</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif

                        @if ($canModuleAny(['plantations.index', 'plantations.collecteurs', 'plantations.regions', 'plantations.zones']))
                        <li class="sidebar-item has-sub {{ request()->routeIs('plantations.*') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--plantations"><i class="bi bi-tree-fill"></i></span>

                                <span>Gestion des plantations</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs('plantations.*') ? 'active' : '' }}">

                                @if ($canModule('plantations.index'))
                                <li class="submenu-item {{ request()->routeIs('plantations.index') ? 'active' : '' }}">

                                    <a href="{{ route('plantations.index') }}">
                                        <i class="bi bi-list-ul submenu-icon--all"></i>
                                        <span>Liste des plantations</span>
                                    </a>

                                </li>
                                @endif
                                @if ($canModule('plantations.collecteurs'))
                                <li class="submenu-item {{ request()->routeIs('plantations.collecteurs') ? 'active' : '' }}">

                                    <a href="{{ route('plantations.collecteurs') }}">
                                        <span>Liste des collecteurs</span>
                                    </a>

                                </li>
                                @endif
                                @if ($canModule('plantations.regions'))
                                <li class="submenu-item {{ request()->routeIs('plantations.regions') ? 'active' : '' }}">

                                    <a href="{{ route('plantations.regions') }}">
                                        <span>Liste des régions</span>
                                    </a>

                                </li>
                                @endif
                                @if ($canModule('plantations.zones'))
                                <li class="submenu-item {{ request()->routeIs('plantations.zones') ? 'active' : '' }}">

                                    <a href="{{ route('plantations.zones') }}">
                                        <span>Liste des zones</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth
                       

                        @auth
                        @if ($canModuleAny(['groupes', 'agents', 'commis', 'usines', 'vehicules']))
                        <li class="sidebar-item has-sub {{ request()->routeIs(['groupes.*', 'agents.*', 'vehicules.*']) || request()->routeIs('usines.index') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--gestion"><i class="bi bi-grid-3x3-gap-fill"></i></span>

                                <span>Gestions</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs(['groupes.*', 'agents.*', 'vehicules.*']) || request()->routeIs('usines.index') ? 'active' : '' }}">

                                @if ($canModule('groupes'))
                                <li class="submenu-item {{ request()->routeIs('groupes.*') ? 'active' : '' }}">

                                    <a href="{{ route('groupes.index') }}">
                                        <i class="bi bi-people-fill submenu-icon--groupes"></i>
                                        <span>Groupes</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('agents'))
                                <li class="submenu-item {{ request()->routeIs('agents.*') ? 'active' : '' }}">

                                    <a href="{{ route('agents.index') }}">
                                        <i class="bi bi-person-lines-fill submenu-icon--agents"></i>
                                        <span>Agents</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('commis'))
                                <li class="submenu-item {{ request()->routeIs('commis.*') ? 'active' : '' }}">

                                    <a href="{{ route('commis.index') }}">
                                        <i class="bi bi-person-badge submenu-icon--commis"></i>
                                        <span>Commis</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('usines'))
                                <li class="submenu-item {{ request()->routeIs('usines.index') ? 'active' : '' }}">

                                    <a href="{{ route('usines.index') }}">
                                        <i class="bi bi-building submenu-icon--usines"></i>
                                        <span>Usines</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('vehicules'))
                                <li class="submenu-item {{ request()->routeIs('vehicules.*') ? 'active' : '' }}">

                                    <a href="{{ route('vehicules.index') }}">
                                        <i class="bi bi-truck submenu-icon--vehicules"></i>
                                        <span>Vehicules</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth

                        @auth
                        @if ($canModuleAny(['sorties.demandes', 'sorties.pending', 'sorties.validated', 'sorties.diverses']))
                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--sorties"><i class="bi bi-box-arrow-right"></i></span>

                                <span>Gestion des sorties</span>

                            </a>

                            <ul class="submenu ">

                                @if ($canModule('sorties.demandes'))
                                <li class="submenu-item {{ request()->routeIs('sorties.demandes.*') ? 'active' : '' }}">

                                    <a href="{{ route('sorties.demandes.index') }}">
                                        <i class="bi bi-list-ul submenu-icon--sorties-list"></i>
                                        <span>Liste des demandes</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('sorties.pending'))
                                <li class="submenu-item {{ request()->routeIs('sorties.pending.*') ? 'active' : '' }}">

                                    <a href="{{ route('sorties.pending.index') }}">
                                        <i class="bi bi-hourglass-split submenu-icon--sorties-pending"></i>
                                        <span>Demandes en attente</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('sorties.diverses'))
                                <li class="submenu-item {{ request()->routeIs('sorties.diverses.*') ? 'active' : '' }}">

                                    <a href="{{ route('sorties.diverses.index') }}">
                                        <i class="bi bi-cash-stack submenu-icon--sorties-diverses"></i>
                                        <span>Sorties Diverses</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif

                        @if ($canModule('caisse.banques'))
                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--banques"><i class="bi bi-building"></i></span>

                                <span>Gestion banques</span>

                            </a>

                            <ul class="submenu ">

                                @if ($canModule('caisse.banques'))
                                <li class="submenu-item {{ request()->routeIs('caisse.banques.index', 'caisse.banques.show') ? 'active' : '' }}">

                                    <a href="{{ route('caisse.banques.index') }}">
                                        <i class="bi bi-building submenu-icon--caisse-banques"></i>
                                        <span>Liste des banques</span>
                                    </a>

                                </li>
                                @endif
                                @if ($canModule('caisse.banques'))
                                <li class="submenu-item {{ request()->routeIs('caisse.banques.approvisionnement-caisse.*') ? 'active' : '' }}">

                                    <a href="{{ route('caisse.banques.approvisionnement-caisse.index') }}">
                                        <i class="bi bi-cash-stack submenu-icon--caisse-banques"></i>
                                        <span>Approvisionnement caisse</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif


                        @if ($canModuleAny(['caisse.solde', 'caisse.approvisionnement', 'caisse.paiements']))
                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--caisse"><i class="bi bi-wallet-fill"></i></span>

                                <span>Gestion caisse</span>

                            </a>

                            <ul class="submenu ">

                                @if ($canModule('caisse.solde'))
                                <li class="submenu-item {{ request()->routeIs('caisse.solde.*') ? 'active' : '' }}">

                                    <a href="{{ route('caisse.solde.index') }}">
                                        <i class="bi bi-wallet2 submenu-icon--caisse-solde"></i>
                                        <span>Solde de la caisse</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('caisse.paiements'))
                                <li class="submenu-item {{ request()->routeIs('caisse.paiements.*') ? 'active' : '' }}">

                                    <a href="{{ route('caisse.paiements.index') }}">
                                        <i class="bi bi-credit-card submenu-icon--caisse-paiements"></i>
                                        <span>Effectuer un paiement</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif


                        @if ($canModuleAny(['recus.tickets', 'recus.demandes']))
                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--recus"><i class="bi bi-receipt"></i></span>

                                <span>Gestion des reçus</span>

                            </a>

                            <ul class="submenu ">

                                @if ($canModule('recus.tickets'))
                                <li class="submenu-item {{ request()->routeIs('recus.tickets.*') ? 'active' : '' }}">

                                    <a href="{{ route('recus.tickets.index') }}">
                                        <i class="bi bi-receipt-cutoff submenu-icon--recus-tickets"></i>
                                        <span>Recu paiement tickets/bordereaux</span>
                                    </a>

                                </li>
                                @endif

                                @if ($canModule('recus.demandes'))
                                <li class="submenu-item ">

                                    <a href="ui-icons-fontawesome.html">
                                        <i class="bi bi-file-earmark-text submenu-icon--recus-demandes"></i>
                                        <span>Recu des paiements demandes et diverses</span>
                                    </a>

                                </li>
                                @endif

                            </ul>

                        </li>
                        @endif
                        @endauth


                    </ul>

                </div>

                <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>

            </div>

        </div>

        <div id="main">

            <header class="mb-3">

                <div class="d-flex justify-content-between align-items-center w-100">

                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        <a href="#" class="burger-btn d-block d-xl-none">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        @auth
                        @if ($canModuleAny(['tickets.index', 'tickets.today', 'tickets.pending', 'tickets.validated', 'tickets.verified', 'tickets.paid', 'tickets.modifications', 'tickets.search']))
                        <a href="{{ route('tickets.index') }}"
                            class="btn btn-sm {{ request()->routeIs('tickets.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-collection-fill"></i> Tickets
                        </a>
                        @endif
                        @endauth
                    </div>

                    <div class="d-flex align-items-center gap-3">

                        <span class="text-muted">

                            {{ auth()->user()->full_name }}

                            <small class="text-secondary">({{ auth()->user()->role }})</small>

                        </span>

                        <form method="POST" action="{{ route('logout') }}">

                            @csrf

                            <button type="submit" class="btn btn-outline-danger btn-sm">

                                <i class="bi bi-box-arrow-right"></i> Déconnexion

                            </button>

                        </form>

                    </div>

                </div>

            </header>



            @yield('page-heading')



            @auth

                @if (! request()->routeIs([
                    'usines.amounts',
                    'usines.amounts.show',
                    'comptes-groupes.*',
                    'utilisateurs.*',
                    'role-permissions.*',
                    'plantations.*',
                    'ponts.*',
                ]))
                    @include('tickets.partials.stats-cards')
                @endif

            @endauth



            <div class="page-content">

                @yield('content')

            </div>

        </div>

    </div>

    <script src="{{ asset('assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>



    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.js') }}"></script>

    <script src="{{ asset('assets/js/pages/dashboard.js') }}"></script>



    <script src="{{ asset('assets/js/main.js') }}"></script>

    @stack('scripts')

</body>



</html>


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



                        <li class="sidebar-item {{ request()->routeIs('tickets.index') ? 'active' : '' }}">

                            <a href="{{ route('tickets.index') }}" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--analytics"><i class="bi bi-graph-up"></i></span>

                                <span>Analytiques</span>

                            </a>

                        </li>



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



                        <li class="sidebar-item has-sub {{ request()->routeIs('tickets.*') && ! request()->routeIs('tickets.index') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--tickets"><i class="bi bi-collection-fill"></i></span>

                                <span>Mes Tickets</span>

                            </a>

                            <ul class="submenu">

                              <li class="submenu-item {{ request()->routeIs('tickets.index') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.index') }}">
                                        <i class="bi bi-list-ul submenu-icon--all"></i>
                                        <span>Tous les tickets</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.today') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.today') }}">
                                        <i class="bi bi-calendar-day submenu-icon--today"></i>
                                        <span>Tickets du Jour</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.pending') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.pending') }}">
                                        <i class="bi bi-hourglass-split submenu-icon--pending"></i>
                                        <span>Tickets en attente</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.validated') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.validated') }}">
                                        <i class="bi bi-check-circle submenu-icon--validated"></i>
                                        <span>Tickets Validés</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.paid') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.paid') }}">
                                        <i class="bi bi-cash submenu-icon--paid"></i>
                                        <span>Tickets Payés</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.modifications') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.modifications') }}">
                                        <i class="bi bi-pencil-square submenu-icon--edit"></i>
                                        <span>Modifications de tickets</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('tickets.search') ? 'active' : '' }}">

                                    <a href="{{ route('tickets.search') }}">
                                        <i class="bi bi-search submenu-icon--search"></i>
                                        <span>Recherche avancée</span>
                                    </a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="extra-component-divider.html">
                                        <i class="bi bi-people submenu-icon--group"></i>
                                        <span>Recherche par groupe</span>
                                    </a>

                                </li>

                            </ul>

                        </li>



                        <li class="sidebar-item has-sub {{ request()->routeIs(['financements.*', 'prets.*', 'prix-unitaires.*', 'bordereaux.*', 'usines.amounts*', 'comptes-agents.*', 'comptes-groupes.*']) ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--finance"><i class="bi bi-cash-stack"></i></span>

                                <span>Gestion Financière</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs(['financements.*', 'prets.*', 'prix-unitaires.*', 'bordereaux.*', 'usines.amounts*', 'comptes-agents.*', 'comptes-groupes.*']) ? 'active' : '' }}">

                                <li class="submenu-item {{ request()->routeIs('prix-unitaires.*') ? 'active' : '' }}">

                                    <a href="{{ route('prix-unitaires.index') }}">
                                        <i class="bi bi-tag submenu-icon--price"></i>
                                        <span>Prix Unitaire</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('bordereaux.*') ? 'active' : '' }}">

                                    <a href="{{ route('bordereaux.index') }}">
                                        <i class="bi bi-file-earmark-text submenu-icon--bordereau"></i>
                                        <span>Bordereaux</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('financements.*') ? 'active' : '' }}">

                                    <a href="{{ route('financements.index') }}">
                                        <i class="bi bi-cash-stack submenu-icon--financement"></i>
                                        <span>Financements</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('prets.*') ? 'active' : '' }}">

                                    <a href="{{ route('prets.index') }}">
                                        <i class="bi bi-wallet2 submenu-icon--pret"></i>
                                        <span>Prêts</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('usines.amounts*') ? 'active' : '' }}">

                                    <a href="{{ route('usines.amounts') }}">
                                        <i class="bi bi-building submenu-icon--usine"></i>
                                        <span>Montants usines</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('comptes-agents.*') ? 'active' : '' }}">

                                    <a href="{{ route('comptes-agents.index') }}">
                                        <i class="bi bi-person-lines-fill submenu-icon--agent"></i>
                                        <span>Comptes agents</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('comptes-groupes.*') ? 'active' : '' }}">

                                    <a href="{{ route('comptes-groupes.index') }}">
                                        <i class="bi bi-people-fill submenu-icon--groupe"></i>
                                        <span>Comptes groupes</span>
                                    </a>

                                </li>

                            </ul>

                        </li>


                        <li class="sidebar-item has-sub {{ request()->routeIs('utilisateurs.*') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--users"><i class="bi bi-shield-lock"></i></span>

                                <span>Gestion des utilisateurs</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs('utilisateurs.*') ? 'active' : '' }}">

                                <li class="submenu-item {{ request()->routeIs('utilisateurs.index') ? 'active' : '' }}">

                                    <a href="{{ route('utilisateurs.index') }}">
                                        <i class="bi bi-people submenu-icon--groupes"></i>
                                        <span>Liste des utilisateurs</span>
                                    </a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="error-404.html">Liste des admins</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="error-500.html">Gestion des rôles</a>

                                </li>

                            </ul>

                        </li>

                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--bridges"><i class="bi bi-signpost-split-fill"></i></span>

                                <span>Gestion des ponts</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="error-403.html">Liste des ponts</a>

                                </li>



                                <li class="submenu-item ">

                                    <a href="error-500.html">Localisation des ponts</a>

                                </li>

                            </ul>

                        </li>

                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--plantations"><i class="bi bi-tree-fill"></i></span>

                                <span>Gestion des plantations</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="error-403.html">Liste des plantations</a>

                                </li>
                                <li class="submenu-item ">

                                    <a href="error-500.html">Liste des collecteurs</a>

                                </li>
                                <li class="submenu-item ">

                                    <a href="error-500.html">Liste des régions</a>

                                </li>
                                <li class="submenu-item ">

                                    <a href="error-500.html">Liste des zones</a>

                                </li>

                            </ul>

                        </li>
                       

                        <li class="sidebar-item has-sub {{ request()->routeIs(['groupes.*', 'agents.*', 'vehicules.*']) || request()->routeIs('usines.index') ? 'active' : '' }}">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--gestion"><i class="bi bi-grid-3x3-gap-fill"></i></span>

                                <span>Gestion</span>

                            </a>

                            <ul class="submenu {{ request()->routeIs(['groupes.*', 'agents.*', 'vehicules.*']) || request()->routeIs('usines.index') ? 'active' : '' }}">

                                <li class="submenu-item {{ request()->routeIs('groupes.*') ? 'active' : '' }}">

                                    <a href="{{ route('groupes.index') }}">
                                        <i class="bi bi-people-fill submenu-icon--groupes"></i>
                                        <span>Groupes</span>
                                    </a>

                                </li>

                                <li class="submenu-item {{ request()->routeIs('agents.*') ? 'active' : '' }}">

                                    <a href="{{ route('agents.index') }}">
                                        <i class="bi bi-person-lines-fill submenu-icon--agents"></i>
                                        <span>Agents</span>
                                    </a>

                                </li>
                                <li class="submenu-item {{ request()->routeIs('usines.index') ? 'active' : '' }}">

                                    <a href="{{ route('usines.index') }}">
                                        <i class="bi bi-building submenu-icon--usines"></i>
                                        <span>Usines</span>
                                    </a>

                                </li>
                                <li class="submenu-item {{ request()->routeIs('vehicules.*') ? 'active' : '' }}">

                                    <a href="{{ route('vehicules.index') }}">
                                        <i class="bi bi-truck submenu-icon--vehicules"></i>
                                        <span>Vehicules</span>
                                    </a>

                                </li>

                            </ul>

                        </li>






                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--sorties"><i class="bi bi-box-arrow-right"></i></span>

                                <span>Gestion des sorties</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="form-editor-quill.html">Liste des demandes</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="form-editor-ckeditor.html">Demandes en attente</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="form-editor-summernote.html">Demandes validées</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="form-editor-tinymce.html">Sorties Diverses</a>

                                </li>

                            </ul>

                        </li>
                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--caisse"><i class="bi bi-wallet-fill"></i></span>

                                <span>Gestion caisse</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="ui-widgets-chatbox.html">Solde de la caisse</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="ui-widgets-pricing.html">Approvisionnement</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="ui-widgets-todolist.html">Banques</a>

                                </li>

                            </ul>

                        </li>



                        <li class="sidebar-item has-sub">

                            <a href="#" class='sidebar-link'>

                                <span class="sidebar-icon sidebar-icon--recus"><i class="bi bi-receipt"></i></span>

                                <span>Gestion des reçus</span>

                            </a>

                            <ul class="submenu ">

                                <li class="submenu-item ">

                                    <a href="ui-icons-bootstrap-icons.html">Recu paiement tickets/bordereaux</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="ui-icons-fontawesome.html">Recu des paiements demandes et diverses</a>

                                </li>

                                <li class="submenu-item ">

                                    <a href="ui-icons-dripicons.html">Dripicons</a>

                                </li>

                            </ul>

                        </li>


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

                        <a href="{{ route('tickets.index') }}"
                            class="btn btn-sm {{ request()->routeIs('tickets.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-collection-fill"></i> Tickets
                        </a>
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

